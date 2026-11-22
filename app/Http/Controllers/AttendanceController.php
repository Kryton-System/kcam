<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Actions\RecordAttendanceAction;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Models\Enrollment;
use App\Http\Requests\IndexAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Support\Enums\AttendanceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Display the attendance register grid.
     */
    public function index(IndexAttendanceRequest $request): View
    {
        $filters = $request->validated();
        $academicYear = $request->user()->school?->currentAcademicYear();
        $classes = SchoolClass::with('sections')->orderBy('order')->get();

        $selectedClassId = isset($filters['class_id']) ? (int) $filters['class_id'] : $classes->first()?->id;
        $selectedSectionId = isset($filters['section_id']) ? (int) $filters['section_id'] : null;
        $date = $filters['date'] ?? now()->toDateString();

        $enrollments = collect();
        $attendanceMap = [];

        if ($selectedClassId && $academicYear) {
            $query = Enrollment::with(['student'])
                ->where('academic_year_id', $academicYear->id)
                ->where('class_id', $selectedClassId)
                ->where('status', 'active');

            if ($selectedSectionId) {
                $query->where('section_id', $selectedSectionId);
            }

            $enrollments = $query->orderBy('roll_no')->get();

            $existingRecords = AttendanceRecord::whereIn('enrollment_id', $enrollments->pluck('id'))
                ->whereDate('date', $date)
                ->get();

            foreach ($existingRecords as $record) {
                $attendanceMap[$record->enrollment_id] = $record->status->value;
            }
        }

        return view('attendance.index', [
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'selectedSectionId' => $selectedSectionId,
            'date' => $date,
            'enrollments' => $enrollments,
            'attendanceMap' => $attendanceMap,
        ]);
    }

    /**
     * Store daily attendance records in bulk. Enrollments of other schools are never found
     * because the query goes through the tenant scope.
     */
    public function store(StoreAttendanceRequest $request, RecordAttendanceAction $recordAction): RedirectResponse
    {
        $validated = $request->validated();
        $attendance = (array) $validated['attendance'];
        $enrollments = Enrollment::whereIn('id', array_keys($attendance))->get()->keyBy('id');

        foreach ($attendance as $enrollmentId => $status) {
            $enrollment = $enrollments->get($enrollmentId);

            if ($enrollment) {
                $recordAction->execute($enrollment, (string) $validated['date'], AttendanceStatus::from($status), $request->user());
            }
        }

        return back()->with('success', 'হাজিরা সফলভাবে সংরক্ষণ করা হয়েছে।');
    }
}
