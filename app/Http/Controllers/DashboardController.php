<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Accounts\Models\Expense;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Exams\Models\Exam;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Notices\Models\Notice;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\SchoolClass;
use App\Domain\Staff\Models\Staff;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\StudentStatus;
use App\Support\Enums\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role-based home page. Each role sees only the figures its job needs: teachers never see money totals,
 * accountants see the money side, and the owner sees the whole school.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Parent) {
            if (config('kcam.modules.portal')) {
                return redirect()->route('portal.index');
            }

            // Parents never see school-wide finance totals, even when the portal is switched off.
            abort(403, __('dashboard.flash.portal_off'));
        }

        $school = $user->school;
        $academicYear = $school?->currentAcademicYear();
        $common = ['school' => $school, 'academicYear' => $academicYear];

        return match ($user->role) {
            UserRole::Accountant => view('dashboard-accountant', $common + $this->accountantFigures()),
            UserRole::Teacher => view('dashboard-teacher', $common + $this->teacherFigures($academicYear)),
            default => view('dashboard', $common + $this->ownerFigures($academicYear)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function ownerFigures(?AcademicYear $academicYear): array
    {
        $attendance = $this->attendanceToday();

        return [
            'totalStudents' => Student::count(),
            'activeStudents' => Student::where('status', StudentStatus::Active)->count(),
            'collectedThisMonth' => $this->collectedBetween(now()->startOfMonth(), now()->endOfMonth()),
            'outstandingDues' => $this->outstandingDues(),
            'expensesThisMonth' => $this->expensesBetween(now()->startOfMonth(), now()->endOfMonth()),
            'staffActive' => Staff::where('status', 'active')->count(),
            'staffTotal' => Staff::count(),
            'attendance' => $attendance,
            'upcomingExams' => $this->upcomingExams($academicYear),
            'recentPayments' => $this->recentPayments(5),
            'recentNotices' => Notice::latest('created_at')->limit(3)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountantFigures(): array
    {
        return [
            'collectedToday' => $this->collectedBetween(now()->startOfDay(), now()->endOfDay()),
            'paymentsToday' => $this->paymentsCountBetween(now()->startOfDay(), now()->endOfDay()),
            'collectedThisMonth' => $this->collectedBetween(now()->startOfMonth(), now()->endOfMonth()),
            'outstandingDues' => $this->outstandingDues(),
            'expensesThisMonth' => $this->expensesBetween(now()->startOfMonth(), now()->endOfMonth()),
            'recentPayments' => $this->recentPayments(8),
            'recentReceipts' => Receipt::with('payment.invoice.student')->latest('id')->limit(8)->get(),
            'recentExpenses' => Expense::active()->with('category')->latest('spent_on')->limit(5)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function teacherFigures(?AcademicYear $academicYear): array
    {
        $attendance = $this->attendanceToday();

        return [
            'activeStudents' => Student::where('status', StudentStatus::Active)->count(),
            'attendance' => $attendance,
            'classRows' => $this->classRows($academicYear),
            'upcomingExams' => $this->upcomingExams($academicYear),
            'recentNotices' => Notice::latest('created_at')->limit(3)->get(),
            'recentStudents' => Student::with('currentEnrollment.schoolClass')->latest('id')->limit(5)->get(),
        ];
    }

    private function collectedBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) Payment::where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');
    }

    private function paymentsCountBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return Payment::where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$from, $to])
            ->count();
    }

    private function outstandingDues(): int
    {
        return (int) Invoice::where('due', '>', 0)->sum('due');
    }

    private function expensesBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) Expense::active()
            ->whereBetween('spent_on', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->sum('amount');
    }

    /**
     * @return Collection<int, Payment>
     */
    private function recentPayments(int $limit): Collection
    {
        return Payment::with(['invoice.student.currentEnrollment', 'collector', 'receipt'])
            ->where('status', PaymentStatus::Completed)
            ->latest('paid_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Today's marks: counts per status and the share of marked students who were present.
     *
     * @return array{present: int, absent: int, late: int, excused: int, marked: int, rate: float}
     */
    private function attendanceToday(): array
    {
        $counts = AttendanceRecord::whereDate('date', now()->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($counts[AttendanceStatus::Present->value] ?? 0);
        $marked = (int) $counts->sum();

        return [
            'present' => $present,
            'absent' => (int) ($counts[AttendanceStatus::Absent->value] ?? 0),
            'late' => (int) ($counts[AttendanceStatus::Late->value] ?? 0),
            'excused' => (int) ($counts[AttendanceStatus::Excused->value] ?? 0),
            'marked' => $marked,
            'rate' => $marked > 0 ? round($present / $marked * 100, 1) : 0.0,
        ];
    }

    /**
     * Per class: enrolled students and how many were marked present or absent today.
     *
     * @return list<array{name: string, students: int, present: int, absent: int, marked: int}>
     */
    private function classRows(?AcademicYear $academicYear): array
    {
        if ($academicYear === null) {
            return [];
        }

        $enrolled = Enrollment::where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->selectRaw('class_id, count(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        $marks = AttendanceRecord::whereDate('attendance_records.date', now()->toDateString())
            ->join('enrollments', 'enrollments.id', '=', 'attendance_records.enrollment_id')
            ->where('attendance_records.school_id', $academicYear->school_id)
            ->selectRaw('enrollments.class_id, attendance_records.status, count(*) as total')
            ->groupBy('enrollments.class_id', 'attendance_records.status')
            ->get();

        return SchoolClass::orderBy('order')->get()->map(function (SchoolClass $class) use ($enrolled, $marks): array {
            $classMarks = $marks->where('class_id', $class->id);

            return [
                'name' => $class->name_bn ?? $class->name,
                'students' => (int) ($enrolled[$class->id] ?? 0),
                'present' => (int) $classMarks->where('status', AttendanceStatus::Present->value)->sum('total'),
                'absent' => (int) $classMarks->where('status', AttendanceStatus::Absent->value)->sum('total'),
                'marked' => (int) $classMarks->sum('total'),
            ];
        })->values()->all();
    }

    /**
     * @return Collection<int, Exam>
     */
    private function upcomingExams(?AcademicYear $academicYear): Collection
    {
        return Exam::query()
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->where('starts_on', '>=', now()->toDateString())
            ->orderBy('starts_on')
            ->limit(3)
            ->get();
    }
}
