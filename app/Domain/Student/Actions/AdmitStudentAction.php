<?php

declare(strict_types=1);

namespace App\Domain\Student\Actions;

use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Support\Enums\StudentStatus;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class AdmitStudentAction
{
    /**
     * Admit a new student into the school with guardian and enrollment.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Student
    {
        $schoolId = (int) ($data['school_id'] ?? CurrentSchool::id());

        return CurrentSchool::runAs($schoolId, fn (): Student => $this->admit($data, $schoolId));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function admit(array $data, int $schoolId): Student
    {
        return DB::transaction(function () use ($data, $schoolId): Student {

            // Generate student code if not supplied: STU-YYYY-XXXXX
            $year = date('Y');
            $studentCode = $data['student_code'] ?? null;
            if (! $studentCode) {
                $lastCount = Student::withoutGlobalScope('school')
                    ->where('school_id', $schoolId)
                    ->count();
                $studentCode = sprintf('STU-%s-%05d', $year, $lastCount + 1);
            }

            // 1. Create Student
            $student = Student::create([
                'school_id' => $schoolId,
                'student_code' => $studentCode,
                'name' => $data['name'],
                'name_bn' => $data['name_bn'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? 'male',
                'birth_reg_no' => $data['birth_reg_no'] ?? null,
                'photo' => $data['photo'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'religion' => $data['religion'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => StudentStatus::Active,
            ]);

            // 2. Create Primary Guardian
            if (! empty($data['guardian_name']) || ! empty($data['guardian_mobile'])) {
                Guardian::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'name' => $data['guardian_name'] ?? 'Primary Guardian',
                    'name_bn' => $data['guardian_name_bn'] ?? null,
                    'relation' => $data['guardian_relation'] ?? 'father',
                    'mobile' => $data['guardian_mobile'] ?? '',
                    'nid' => $data['guardian_nid'] ?? null,
                    'nid_front_path' => $data['guardian_nid_front_path'] ?? null,
                    'nid_back_path' => $data['guardian_nid_back_path'] ?? null,
                    'email' => $data['guardian_email'] ?? null,
                    'is_primary' => true,
                ]);
            }

            // 3. Create Enrollment
            if (! empty($data['academic_year_id']) && ! empty($data['class_id'])) {
                Enrollment::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => (int) $data['academic_year_id'],
                    'class_id' => (int) $data['class_id'],
                    'section_id' => ! empty($data['section_id']) ? (int) $data['section_id'] : null,
                    'roll_no' => ! empty($data['roll_no']) ? (int) $data['roll_no'] : null,
                    'status' => 'active',
                ]);
            }

            return $student->fresh(['guardians', 'enrollments']);
        });
    }
}
