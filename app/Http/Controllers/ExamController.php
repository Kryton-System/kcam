<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Exams\Models\Exam;
use App\Domain\School\Models\SchoolClass;
use Illuminate\Http\Request;
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
}
