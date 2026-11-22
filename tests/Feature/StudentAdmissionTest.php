<?php

declare(strict_types=1);

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Section;
use App\Domain\Student\Actions\AdmitStudentAction;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\StudentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admit student action creates student, guardian, and enrollment', function (): void {
    $school = School::factory()->create();
    $owner = User::factory()->owner()->create(['school_id' => $school->id]);
    $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);
    $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);

    $this->actingAs($owner);

    $action = app(AdmitStudentAction::class);

    $student = $action->execute([
        'school_id' => $school->id,
        'name' => 'Sabbir Hossain',
        'name_bn' => 'সাব্বির হোসেন',
        'dob' => '2019-02-10',
        'gender' => 'male',
        'blood_group' => 'A+',
        'guardian_name' => 'Belal Hossain',
        'guardian_name_bn' => 'বেলাল হোসেন',
        'guardian_relation' => 'father',
        'guardian_mobile' => '01799887766',
        'academic_year_id' => $academicYear->id,
        'class_id' => $class->id,
        'section_id' => $section->id,
        'roll_no' => 12,
    ]);

    expect($student)->toBeInstanceOf(Student::class)
        ->and($student->name)->toBe('Sabbir Hossain')
        ->and($student->status)->toBe(StudentStatus::Active)
        ->and($student->student_code)->toStartWith('STU-');

    // Verify guardian
    $guardian = $student->primaryGuardian;
    expect($guardian)->not->toBeNull()
        ->and($guardian->name)->toBe('Belal Hossain')
        ->and($guardian->mobile)->toBe('01799887766');

    // Verify enrollment
    $enrollment = $student->currentEnrollment;
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->class_id)->toBe($class->id)
        ->and($enrollment->section_id)->toBe($section->id)
        ->and($enrollment->roll_no)->toBe(12);
});
