<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Exams\Models\Mark;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Notices\Models\Notice;
use App\Domain\Payments\Models\Receipt;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Read only parent portal. A parent is linked to children through the guardian
 * mobile number matching the user's mobile (same school, enforced by tenancy).
 */
class PortalController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.index', [
            'children' => $this->linkedChildren($request->user())
                ->with(['currentEnrollment.schoolClass', 'currentEnrollment.section'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Request $request, Student $student): View
    {
        abort_unless($this->linkedChildren($request->user())->whereKey($student->id)->exists(), 404);

        $enrollment = $student->currentEnrollment()->with(['schoolClass', 'section'])->first();

        return view('portal.show', [
            'student' => $student,
            'enrollment' => $enrollment,
            'attendance' => $this->attendanceSummary($enrollment?->id),
            'invoices' => Invoice::where('student_id', $student->id)->orderByDesc('month')->limit(12)->get(),
            'totalDue' => (int) Invoice::where('student_id', $student->id)->sum('due'),
            'receipts' => Receipt::whereHas('payment.invoice', fn (Builder $q) => $q->where('student_id', $student->id))
                ->with('payment')->latest()->limit(10)->get(),
            'results' => $this->publishedResults($enrollment?->id),
            'notices' => $this->noticesFor($enrollment?->class_id),
        ]);
    }

    /**
     * Children whose guardian mobile equals the parent's mobile.
     *
     * @return Builder<Student>
     */
    private function linkedChildren(User $user): Builder
    {
        $mobile = (string) $user->mobile;

        return Student::query()->whereHas('guardians', fn (Builder $q) => $q->where('mobile', $mobile))
            ->when($mobile === '', fn (Builder $q) => $q->whereRaw('1 = 0'));
    }

    /**
     * @return array<string, int>
     */
    private function attendanceSummary(?int $enrollmentId): array
    {
        $counts = $enrollmentId === null ? collect() : AttendanceRecord::where('enrollment_id', $enrollmentId)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $summary = [];
        foreach (AttendanceStatus::cases() as $status) {
            $summary[$status->value] = (int) ($counts[$status->value] ?? 0);
        }
        $summary['total'] = array_sum($summary);

        return $summary;
    }

    /**
     * Marks grouped by exam, published exams only.
     *
     * @return Collection<int, Collection<int, Mark>>
     */
    private function publishedResults(?int $enrollmentId): Collection
    {
        if ($enrollmentId === null) {
            return collect();
        }

        return Mark::where('enrollment_id', $enrollmentId)
            ->whereHas('exam', fn (Builder $q) => $q->whereNotNull('published_at'))
            ->with(['exam', 'subject'])->get()->groupBy('exam_id');
    }

    /**
     * Sent notices meant for parents or for the child's class.
     *
     * @return Collection<int, Notice>
     */
    private function noticesFor(?int $classId): Collection
    {
        return Notice::whereNotNull('sent_at')
            ->where(function (Builder $q) use ($classId): void {
                $q->whereIn('audience', ['all', 'parents']);
                if ($classId !== null) {
                    $q->orWhere(fn (Builder $c) => $c->where('audience', 'class')->where('target_class_id', $classId));
                }
            })->latest('sent_at')->limit(10)->get();
    }
}
