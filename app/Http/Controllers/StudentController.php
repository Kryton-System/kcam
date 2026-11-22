<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Actions\AdmitStudentAction;
use App\Domain\Student\Models\Student;
use App\Http\Requests\IndexStudentsRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Support\Helpers\Search;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
