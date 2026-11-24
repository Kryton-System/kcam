<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Actions\AdmitStudentAction;
use App\Domain\Student\Models\Student;
use App\Http\Requests\IndexStudentsRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Support\Helpers\Search;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(IndexStudentsRequest $request): View
    {
        $filters = $request->validated();
        $query = Student::with(['primaryGuardian', 'currentEnrollment.schoolClass', 'currentEnrollment.section']);

        if (! empty($filters['search'])) {
            Search::contains($query, ['name', 'name_bn', 'student_code'], $filters['search']);
        }

        if (! empty($filters['class_id'])) {
            $query->whereHas('currentEnrollment', function ($q) use ($filters): void {
                $q->where('class_id', (int) $filters['class_id']);
            });
        }

        return view('students.index', [
            'students' => $query->paginate(15)->withQueryString(),
            'classes' => SchoolClass::orderBy('order')->get(),
        ]);
    }

    /**
     * Show the form for admitting a new student.
     */
    public function create(Request $request): View
    {
        $school = $request->user()->school;

        return view('students.create', [
            'academicYear' => $school?->currentAcademicYear(),
            'classes' => SchoolClass::with('sections')->orderBy('order')->get(),
        ]);
    }

    /**
     * Store a newly admitted student in storage.
     */
    public function store(StoreStudentRequest $request, AdmitStudentAction $admitAction): RedirectResponse
    {
        $school = $request->user()->school;

        $admitAction->execute([
            ...$request->validated(),
            'school_id' => $school->id,
            'academic_year_id' => $school->currentAcademicYear()?->id,
        ]);

        return redirect()->route('students.index')->with('success', 'শিক্ষার্থী সফলভাবে ভর্তি করা হয়েছে।');
    }

    public function show(Student $student): View
    {
        $student->load(['primaryGuardian', 'currentEnrollment.schoolClass', 'currentEnrollment.section']);

        return view('students.show', ['student' => $student]);
    }

    public function edit(Student $student): View
    {
        $student->load('primaryGuardian');

        return view('students.edit', ['student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($student, $data): void {
            $student->update(Arr::only($data, ['name', 'name_bn', 'dob', 'gender', 'blood_group']));

            $student->guardians()->updateOrCreate(
                ['is_primary' => true],
                [
                    'school_id' => $student->school_id,
                    'name' => $data['guardian_name'],
                    'relation' => $data['guardian_relation'],
                    'mobile' => $data['guardian_mobile'],
                ],
            );
        });

        return redirect()->route('students.show', $student)->with('success', 'তথ্য হালনাগাদ হয়েছে। / Student updated.');
    }
}
