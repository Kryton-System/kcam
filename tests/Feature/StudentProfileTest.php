<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;

function studentWithGuardian(School $school): Student
{
    return CurrentSchool::runAs($school->id, function () use ($school): Student {
        $student = Student::factory()->create(['school_id' => $school->id, 'name' => 'Rafi Khan']);
        Guardian::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'mobile' => '01711111111',
        ]);

        return $student;
    });
}

test('owner can view a student profile', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $student = studentWithGuardian($owner->school);

    $this->actingAs($owner)->get(route('students.show', $student))
        ->assertOk()
        ->assertSee('Rafi Khan')
        ->assertSee('01711111111');
});

test('student profile of another school is not found', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $other = studentWithGuardian(School::factory()->create());

    $this->actingAs($owner)->get(route('students.show', $other))->assertNotFound();
    $this->actingAs($owner)->get(route('students.edit', $other))->assertNotFound();
});

test('owner can update student and guardian details', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $student = studentWithGuardian($owner->school);

    $this->actingAs($owner)->put(route('students.update', $student), [
        'name' => 'Rafi Updated',
        'gender' => 'male',
        'guardian_name' => 'New Guardian',
        'guardian_relation' => 'mother',
        'guardian_mobile' => '০১৮২২২২২২২২',
    ])->assertRedirect(route('students.show', $student));

    $fresh = CurrentSchool::runAs($owner->school_id, fn () => $student->fresh()->load('primaryGuardian'));
    expect($fresh->name)->toBe('Rafi Updated')
        ->and($fresh->primaryGuardian->mobile)->toBe('01822222222')
        ->and($fresh->primaryGuardian->name)->toBe('New Guardian');
});

test('update rejects a bad mobile number', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $student = studentWithGuardian($owner->school);

    $this->actingAs($owner)->put(route('students.update', $student), [
        'name' => 'X', 'gender' => 'male', 'guardian_name' => 'G',
        'guardian_relation' => 'father', 'guardian_mobile' => '123',
    ])->assertSessionHasErrors('guardian_mobile');
});

test('accountant cannot edit a student', function (): void {
    $accountant = schoolUser(UserRole::Accountant);
    $student = studentWithGuardian($accountant->school);

    $this->actingAs($accountant)->get(route('students.edit', $student))->assertForbidden();
});
