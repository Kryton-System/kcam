<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Actions\AdmitStudentAction;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Http\Requests\IndexStudentsRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Support\Enums\StudentStatus;
use App\Support\Helpers\Search;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            Search::contains($query, ['name', 'name_bn', 'student_code', 'birth_reg_no'], $filters['search']);
        }

        if (! empty($filters['class_id'])) {
            $query->whereHas('currentEnrollment', function ($q) use ($filters): void {
                $q->where('class_id', (int) $filters['class_id']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status'] === 'active' ? '=' : '!=', StudentStatus::Active);
        }

        match ($filters['sort'] ?? 'name') {
            'code' => $query->orderBy('student_code'),
            'newest' => $query->latest('id'),
            default => $query->orderBy('name'),
        };

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
            ...Arr::except($request->validated(), ['photo', 'guardian_nid_front', 'guardian_nid_back']),
            'photo' => $this->storeUpload($request->file('photo'), $school->id, 'photo'),
            'guardian_nid_front_path' => $this->storeUpload($request->file('guardian_nid_front'), $school->id, 'guardian-nid'),
            'guardian_nid_back_path' => $this->storeUpload($request->file('guardian_nid_back'), $school->id, 'guardian-nid'),
            'school_id' => $school->id,
            'academic_year_id' => $school->currentAcademicYear()?->id,
        ]);

        return redirect()->route('students.index')->with('success', __('students.flash.admitted'));
    }

    /**
     * Student photo. Served through the app (private disk) so only users of the owning school can fetch it.
     */
    public function photo(Student $student): StreamedResponse
    {
        abort_if($student->photo === null, 404);

        return Storage::disk('local')->response($student->photo);
    }

    /**
     * Guardian NID scan (front or back). Served through the app for the same reason as the photo.
     */
    public function guardianNid(Student $student, string $side): StreamedResponse
    {
        $guardian = $student->primaryGuardian;
        $path = $side === 'front' ? $guardian?->nid_front_path : $guardian?->nid_back_path;

        abort_if($path === null, 404);

        return Storage::disk('local')->response($path);
    }

    private function storeUpload(?UploadedFile $file, int $schoolId, string $folder): ?string
    {
        return $file?->store("students/{$schoolId}/{$folder}", 'local');
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
        $guardian = $student->primaryGuardian;
        $uploads = $this->storeReplacementUploads($request, $student->school_id);
        $replacedPaths = $this->replacedPaths($uploads, $student, $guardian);

        DB::transaction(function () use ($student, $data, $uploads): void {
            $student->update([
                ...Arr::only($data, ['name', 'name_bn', 'dob', 'gender', 'blood_group', 'birth_reg_no', 'religion', 'address']),
                ...array_filter(['photo' => $uploads['photo']], fn (?string $path): bool => $path !== null),
            ]);

            $student->guardians()->updateOrCreate(
                ['is_primary' => true],
                [
                    'school_id' => $student->school_id,
                    'name' => $data['guardian_name'],
                    'relation' => $data['guardian_relation'],
                    'mobile' => $data['guardian_mobile'],
                    'nid' => $data['guardian_nid'] ?? null,
                    ...array_filter([
                        'nid_front_path' => $uploads['guardian_nid_front_path'],
                        'nid_back_path' => $uploads['guardian_nid_back_path'],
                    ], fn (?string $path): bool => $path !== null),
                ],
            );
        });

        // Old files are removed only after the new records are saved, so a failed save never loses a document.
        Storage::disk('local')->delete($replacedPaths);

        return redirect()->route('students.show', $student)->with('success', __('students.flash.updated'));
    }

    /**
     * @return array{photo: ?string, guardian_nid_front_path: ?string, guardian_nid_back_path: ?string}
     */
    private function storeReplacementUploads(UpdateStudentRequest $request, int $schoolId): array
    {
        return [
            'photo' => $this->storeUpload($request->file('photo'), $schoolId, 'photo'),
            'guardian_nid_front_path' => $this->storeUpload($request->file('guardian_nid_front'), $schoolId, 'guardian-nid'),
            'guardian_nid_back_path' => $this->storeUpload($request->file('guardian_nid_back'), $schoolId, 'guardian-nid'),
        ];
    }

    /**
     * Files that a new upload makes obsolete.
     *
     * @param  array{photo: ?string, guardian_nid_front_path: ?string, guardian_nid_back_path: ?string}  $uploads
     * @return list<string>
     */
    private function replacedPaths(array $uploads, Student $student, ?Guardian $guardian): array
    {
        return array_values(array_filter([
            $uploads['photo'] !== null ? $student->photo : null,
            $uploads['guardian_nid_front_path'] !== null ? $guardian?->nid_front_path : null,
            $uploads['guardian_nid_back_path'] !== null ? $guardian?->nid_back_path : null,
        ]));
    }
}
