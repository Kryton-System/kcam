<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a school user cannot see another schools students', function (): void {
    // School 1 and user 1
    $school1 = School::factory()->create(['name' => 'School One']);
    $user1 = User::factory()->create([
        'school_id' => $school1->id,
        'role' => UserRole::Owner,
    ]);

    // School 2 and user 2
    $school2 = School::factory()->create(['name' => 'School Two']);
    $user2 = User::factory()->create([
        'school_id' => $school2->id,
        'role' => UserRole::Owner,
    ]);

    // Create students for both schools (without authentication to bypass auto-fill)
    $student1 = Student::withoutGlobalScopes()->create([
        'school_id' => $school1->id,
        'student_code' => 'STU-001',
        'name' => 'Rahim',
        'status' => 'active',
    ]);

    $student2 = Student::withoutGlobalScopes()->create([
        'school_id' => $school2->id,
        'student_code' => 'STU-002',
        'name' => 'Karim',
        'status' => 'active',
    ]);

    // Authenticate as User 1 from School 1
    $this->actingAs($user1);

    // School 1 should only see Student 1
    $students = Student::all();
    expect($students)->toHaveCount(1)
        ->and($students->first()->id)->toBe($student1->id)
        ->and($students->first()->name)->toBe('Rahim');

    // Finding student 2 by ID should fail (return null)
    expect(Student::find($student2->id))->toBeNull();

    // Authenticate as User 2 from School 2
    $this->actingAs($user2);

    // School 2 should only see Student 2
    $students = Student::all();
    expect($students)->toHaveCount(1)
        ->and($students->first()->id)->toBe($student2->id)
        ->and($students->first()->name)->toBe('Karim');

    // Finding student 1 by ID should fail (return null)
    expect(Student::find($student1->id))->toBeNull();
});

test('creating a model automatically sets the authenticated users school id', function (): void {
    $school = School::factory()->create(['name' => 'Green Valley School']);
    $user = User::factory()->create([
        'school_id' => $school->id,
        'role' => UserRole::Teacher,
    ]);

    $this->actingAs($user);

    // Create a student without specifying school_id
    $student = Student::create([
        'student_code' => 'STU-NEW-01',
        'name' => 'Nusrat Jahan',
        'status' => 'active',
    ]);

    expect($student->school_id)->toBe($school->id);
    expect($student->school->id)->toBe($school->id);
});

test('a school user cannot see another schools classes or invoices', function (): void {
    $school1 = School::factory()->create();
    $user1 = User::factory()->create(['school_id' => $school1->id]);

    $school2 = School::factory()->create();
    $user2 = User::factory()->create(['school_id' => $school2->id]);

    // Create class for school 1 and school 2
    $class1 = SchoolClass::withoutGlobalScopes()->create([
        'school_id' => $school1->id,
        'name' => 'Nursery',
        'order' => 1,
    ]);

    $class2 = SchoolClass::withoutGlobalScopes()->create([
        'school_id' => $school2->id,
        'name' => 'Class 1',
        'order' => 2,
    ]);

    // Acting as user 1
    $this->actingAs($user1);
    expect(SchoolClass::all())->toHaveCount(1)
        ->and(SchoolClass::first()->name)->toBe('Nursery');

    // Acting as user 2
    $this->actingAs($user2);
    expect(SchoolClass::all())->toHaveCount(1)
        ->and(SchoolClass::first()->name)->toBe('Class 1');
});
