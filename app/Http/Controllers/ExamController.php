<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Exams\Actions\GradeMarks;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\Mark;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Subject;
use App\Domain\Student\Models\Enrollment;
use App\Http\Requests\UpdateMarksRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Display a listing of exams.
     */
    public function index(Request $request): View
    {
        $school = $request->user()->school;
        $academicYear = $school?->currentAcademicYear();

        $exams = $academicYear
            ? Exam::where('academic_year_id', $academicYear->id)->latest()->get()
            : collect();

        $classes = SchoolClass::orderBy('order')->get();

        return view('exams.index', [
            'exams' => $exams,
            'classes' => $classes,
        ]);
    }

    public function editMarks(Request $request, Exam $exam): View
    {
        $subjects = Subject::with('schoolClass')->orderBy('class_id')->orderBy('name')->get();
        $subject = $subjects->firstWhere('id', (int) $request->query('subject_id'));
        $rows = collect();

        if ($subject) {
            $existing = Mark::where('exam_id', $exam->id)->where('subject_id', $subject->id)->pluck('marks', 'enrollment_id');
            $rows = Enrollment::with('student')
                ->where('class_id', $subject->class_id)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('status', 'active')
                ->orderBy('roll_no')->get()
                ->each(fn ($e) => $e->setAttribute('current_marks', $existing[$e->id] ?? null));
        }

        return view('exams.marks', compact('exam', 'subjects', 'subject', 'rows'));
    }

    public function updateMarks(UpdateMarksRequest $request, Exam $exam, GradeMarks $grader): RedirectResponse
    {
        $subject = Subject::findOrFail($request->integer('subject_id'));

        DB::transaction(function () use ($request, $exam, $subject, $grader): void {
            foreach ($request->validated('marks') as $enrollmentId => $value) {
                if ($value === null || $value === '') {
                    Mark::where(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'enrollment_id' => $enrollmentId])->delete();

                    continue;
                }

                [$grade, $gp] = $grader->for((float) $value, $subject->full_marks);

                Mark::updateOrCreate(
                    ['exam_id' => $exam->id, 'subject_id' => $subject->id, 'enrollment_id' => (int) $enrollmentId],
                    ['school_id' => $exam->school_id, 'marks' => $value, 'grade' => $grade, 'gp' => $gp],
                );
            }
        });

        return redirect()->route('exams.marks.edit', [$exam, 'subject_id' => $subject->id])
            ->with('success', 'নম্বর সংরক্ষিত হয়েছে। / Marks saved.');
    }
}
