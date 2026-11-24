<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Notices\Models\Notice;
use App\Domain\Payments\Models\Payment;
use App\Domain\Student\Models\Enrollment;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\UserRole;
use App\Support\Helpers\BanglaHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's dashboard.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Parent) {
            if (config('kcam.modules.portal')) {
                return redirect()->route('portal.index');
            }

            // Parents never see school-wide finance totals, even when the portal is switched off.
            abort(403, 'অভিভাবক পোর্টাল বন্ধ আছে / The parent portal is turned off for this school.');
        }

        $school = $user->school;

        $academicYear = $school?->currentAcademicYear();
        $currentMonth = now()->format('Y-m');
        $today = now()->toDateString();

        // 1. Total Enrolled Students
        $totalStudents = $academicYear
            ? Enrollment::where('academic_year_id', $academicYear->id)->where('status', 'active')->count()
            : 0;

        // 2. Today's Attendance
        $presentToday = AttendanceRecord::whereDate('date', $today)
            ->where('status', AttendanceStatus::Present)
            ->count();

        $absentToday = AttendanceRecord::whereDate('date', $today)
            ->where('status', AttendanceStatus::Absent)
            ->count();

        $attendanceRate = $totalStudents > 0
            ? round(($presentToday / $totalStudents) * 100, 1)
            : 0.0;

        // 3. Fee Stats for Current Month
        $monthlyInvoices = Invoice::where('month', $currentMonth)->get();
        $totalInvoiced = (int) $monthlyInvoices->sum('total');
        $totalCollected = (int) $monthlyInvoices->sum('paid');
        $totalDue = (int) $monthlyInvoices->sum('due');

        // 4. Recent Payments
        $recentPayments = Payment::with(['invoice.student.currentEnrollment', 'collector'])
            ->latest('paid_at')
            ->limit(5)
            ->get();

        // 5. Recent Notices
        $recentNotices = Notice::latest('created_at')
            ->limit(3)
            ->get();

        return view('dashboard', [
            'school' => $school,
            'academicYear' => $academicYear,
            'totalStudents' => $totalStudents,
            'totalStudentsBn' => BanglaHelper::toBanglaDigits($totalStudents),
            'presentToday' => $presentToday,
            'presentTodayBn' => BanglaHelper::toBanglaDigits($presentToday),
            'absentToday' => $absentToday,
            'absentTodayBn' => BanglaHelper::toBanglaDigits($absentToday),
            'attendanceRate' => $attendanceRate,
            'attendanceRateBn' => BanglaHelper::toBanglaDigits($attendanceRate),
            'totalCollectedFormatted' => BanglaHelper::formatTaka($totalCollected, true),
            'totalDueFormatted' => BanglaHelper::formatTaka($totalDue, true),
            'recentPayments' => $recentPayments,
            'recentNotices' => $recentNotices,
        ]);
    }
}
