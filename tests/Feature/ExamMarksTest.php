<?php

declare(strict_types=1);

use App\Domain\Exams\Actions\GradeMarks;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\Mark;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Subject;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;

function examSetup(School $school): array
{
    return CurrentSchool::runAs($school->id, function () use ($school): array {
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'class_id' => $class->id, 'full_marks' => 100, 'pass_marks' => 33]);
        $exam = Exam::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $enrollment = Enrollment::factory()->create([
            'school_id' => $school->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'class_id' => $class->id, 'status' => 'active',
        ]);

        return compact('exam', 'subject', 'enrollment', 'student');
    });
}

test('grade scale follows the GPA table', function (): void {
    expect((new GradeMarks)->for(85, 100))->toBe(['A+', 5.0])
        ->and((new GradeMarks)->for(33, 100))->toBe(['D', 1.0])
        ->and((new GradeMarks)->for(20, 100))->toBe(['F', 0.0])
        ->and((new GradeMarks)->for(8, 10))->toBe(['A+', 5.0]);
});

test('teacher sees the marks grid for a subject', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    ['exam' => $exam, 'subject' => $subject, 'student' => $student] = examSetup($teacher->school);

    $this->actingAs($teacher)->get(route('exams.marks.edit', [$exam, 'subject_id' => $subject->id]))
        ->assertOk()->assertSee($student->name_bn ?? $student->name);
});

test('teacher saves marks and grades are calculated', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    ['exam' => $exam, 'subject' => $subject, 'enrollment' => $enrollment] = examSetup($teacher->school);

    $this->actingAs($teacher)->put(route('exams.marks.update', $exam), [
        'subject_id' => $subject->id,
        'marks' => [$enrollment->id => '৮৫'],
    ])->assertRedirect();

    $mark = CurrentSchool::runAs($teacher->school_id, fn () => Mark::first());
    expect((float) $mark->marks)->toBe(85.0)->and($mark->grade)->toBe('A+')->and((float) $mark->gp)->toBe(5.0);

    $this->actingAs($teacher)->put(route('exams.marks.update', $exam), [
        'subject_id' => $subject->id,
        'marks' => [$enrollment->id => '40'],
    ]);
    expect(CurrentSchool::runAs($teacher->school_id, fn () => Mark::count()))->toBe(1);
});

test('marks above full marks are rejected', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    ['exam' => $exam, 'subject' => $subject, 'enrollment' => $enrollment] = examSetup($teacher->school);

    $this->actingAs($teacher)->put(route('exams.marks.update', $exam), [
        'subject_id' => $subject->id, 'marks' => [$enrollment->id => '101'],
    ])->assertSessionHasErrors('marks.'.$enrollment->id);
});

test('an enrollment outside the subject class is rejected', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    ['exam' => $exam, 'subject' => $subject] = examSetup($teacher->school);
    $foreign = examSetup(School::factory()->create())['enrollment'];

    $this->actingAs($teacher)->put(route('exams.marks.update', $exam), [
        'subject_id' => $subject->id, 'marks' => [$foreign->id => '50'],
    ])->assertSessionHasErrors();
});

test('published exams cannot be edited', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    ['exam' => $exam, 'subject' => $subject, 'enrollment' => $enrollment] = examSetup($teacher->school);
    CurrentSchool::runAs($teacher->school_id, fn () => $exam->update(['published_at' => now()]));

    $this->actingAs($teacher)->put(route('exams.marks.update', $exam), [
        'subject_id' => $subject->id, 'marks' => [$enrollment->id => '50'],
    ])->assertForbidden();
});

test('accountant cannot enter marks', function (): void {
    $accountant = schoolUser(UserRole::Accountant);
    ['exam' => $exam] = examSetup($accountant->school);

    $this->actingAs($accountant)->get(route('exams.marks.edit', $exam))->assertForbidden();
});

test('another school exam is not found', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    ['exam' => $exam] = examSetup(School::factory()->create());

    $this->actingAs($teacher)->get(route('exams.marks.edit', $exam))->assertNotFound();
});
