<?php

declare(strict_types=1);

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\Religion;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

/**
 * Admits one student through the real form and returns the owner and the saved student.
 *
 * @return array{0: User, 1: Student}
 */
function admittedForEdit(): array
{
    $school = School::factory()->create();
    AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);
    $owner = schoolUser(UserRole::Owner, $school);

    test()->actingAs($owner)->post(route('students.store'), [
        'name' => 'Sabbir Hossain',
        'gender' => 'male',
        'birth_reg_no' => '20191234567890123',
        'religion' => 'islam',
        'address' => 'House 12, Road 4, Dhanmondi, Dhaka',
        'photo' => UploadedFile::fake()->image('student.jpg', 300, 300),
        'guardian_name' => 'Belal Hossain',
        'guardian_relation' => 'father',
        'guardian_mobile' => '01799887766',
        'guardian_nid' => '1234567890123',
        'guardian_nid_front' => UploadedFile::fake()->image('nid-front.jpg'),
        'guardian_nid_back' => UploadedFile::fake()->create('nid-back.pdf', 120, 'application/pdf'),
        'class_id' => $class->id,
    ])->assertRedirect(route('students.index'));

    return [$owner, CurrentSchool::runAs($school->id, fn (): Student => Student::with('primaryGuardian')->firstOrFail())];
}

test('edit form shows the Bangladesh admission fields with the current values', function (): void {
    [$owner, $student] = admittedForEdit();

    $this->actingAs($owner)->get(route('students.edit', $student))
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="birth_reg_no"', false)
        ->assertSee('value="20191234567890123"', false)
        ->assertSee('name="religion"', false)
        ->assertSee('name="address"', false)
        ->assertSee('Dhanmondi')
        ->assertSee('name="photo"', false)
        ->assertSee('name="guardian_nid"', false)
        ->assertSee('value="1234567890123"', false)
        ->assertSee('name="guardian_nid_front"', false)
        ->assertSee('name="guardian_nid_back"', false);
});

test('updating a student saves BDRIS number, religion, address and guardian NID', function (): void {
    [$owner, $student] = admittedForEdit();

    $this->actingAs($owner)->put(route('students.update', $student), [
        'name' => 'Sabbir Hossain',
        'gender' => 'male',
        'birth_reg_no' => '২০১৯৯৮৭৬৫৪৩২১০৯৮৭',
        'religion' => 'hinduism',
        'address' => 'House 7, Gulshan 2, Dhaka',
        'guardian_name' => 'Belal Hossain',
        'guardian_relation' => 'father',
        'guardian_mobile' => '01799887766',
        'guardian_nid' => '9876543210',
    ])->assertRedirect(route('students.show', $student));

    $fresh = CurrentSchool::runAs($owner->school_id, fn (): Student => Student::with('primaryGuardian')->findOrFail($student->id));

    expect($fresh->birth_reg_no)->toBe('20199876543210987')
        ->and($fresh->religion)->toBe(Religion::Hinduism)
        ->and($fresh->address)->toContain('Gulshan')
        ->and($fresh->primaryGuardian->nid)->toBe('9876543210');
});

test('uploading a new photo replaces the old file and removes it from storage', function (): void {
    [$owner, $student] = admittedForEdit();
    $oldPhoto = $student->photo;

    $this->actingAs($owner)->put(route('students.update', $student), [
        ...editPayload($student),
        'photo' => UploadedFile::fake()->image('new.jpg', 300, 300),
    ])->assertRedirect(route('students.show', $student));

    $fresh = CurrentSchool::runAs($owner->school_id, fn (): Student => Student::findOrFail($student->id));

    expect($fresh->photo)->not->toBe($oldPhoto)
        ->and($fresh->photo)->toStartWith("students/{$owner->school_id}/photo/");
    Storage::disk('local')->assertMissing($oldPhoto);
    Storage::disk('local')->assertExists($fresh->photo);
});

test('updating without new files keeps the existing photo and NID scans', function (): void {
    [$owner, $student] = admittedForEdit();
    $before = CurrentSchool::runAs($owner->school_id, fn (): Student => Student::with('primaryGuardian')->findOrFail($student->id));

    $this->actingAs($owner)->put(route('students.update', $student), editPayload($student))
        ->assertRedirect(route('students.show', $student));

    $after = CurrentSchool::runAs($owner->school_id, fn (): Student => Student::with('primaryGuardian')->findOrFail($student->id));

    expect($after->photo)->toBe($before->photo)
        ->and($after->primaryGuardian->nid_front_path)->toBe($before->primaryGuardian->nid_front_path)
        ->and($after->primaryGuardian->nid_back_path)->toBe($before->primaryGuardian->nid_back_path);
    Storage::disk('local')->assertExists($before->photo);
});

test('replacing the guardian NID back scan stores the new file and removes the old one', function (): void {
    [$owner, $student] = admittedForEdit();
    $oldBack = CurrentSchool::runAs($owner->school_id, fn (): ?string => $student->primaryGuardian()->first()->nid_back_path);

    $this->actingAs($owner)->put(route('students.update', $student), [
        ...editPayload($student),
        'guardian_nid_back' => UploadedFile::fake()->create('back.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('students.show', $student));

    $newBack = CurrentSchool::runAs($owner->school_id, fn (): ?string => $student->primaryGuardian()->first()->nid_back_path);

    expect($newBack)->not->toBe($oldBack);
    Storage::disk('local')->assertMissing($oldBack);
    Storage::disk('local')->assertExists($newBack);
});

test('BDRIS number of this student can be kept, but another student cannot take it', function (): void {
    [$owner, $student] = admittedForEdit();
    $this->actingAs($owner)->post(route('students.store'), [
        'name' => 'Second child',
        'gender' => 'female',
        'birth_reg_no' => '20190000000000001',
        'guardian_name' => 'Other Guardian',
        'guardian_relation' => 'mother',
        'guardian_mobile' => '01711223344',
        'class_id' => SchoolClass::where('school_id', $owner->school_id)->firstOrFail()->id,
    ])->assertRedirect(route('students.index'));

    $this->actingAs($owner)->put(route('students.update', $student), editPayload($student))
        ->assertSessionDoesntHaveErrors('birth_reg_no');

    $this->actingAs($owner)->put(route('students.update', $student), editPayload($student, [
        'birth_reg_no' => '20190000000000001',
    ]))->assertSessionHasErrors('birth_reg_no');
});

test('guardian NID number must be 10, 13 or 17 digits on update', function (): void {
    [$owner, $student] = admittedForEdit();

    $this->actingAs($owner)->put(route('students.update', $student), editPayload($student, [
        'guardian_nid' => '12345',
    ]))->assertSessionHasErrors('guardian_nid');
});

test('student list search finds a student by BDRIS number', function (): void {
    [$owner] = admittedForEdit();

    $this->actingAs($owner)->get(route('students.index', ['search' => '20191234567890123']))
        ->assertOk()
        ->assertSee('Sabbir Hossain');

    $this->actingAs($owner)->get(route('students.index', ['search' => '99999999999999999']))
        ->assertOk()
        ->assertDontSee('Sabbir Hossain');
});

/**
 * Values a valid edit form submits, with optional overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function editPayload(Student $student, array $overrides = []): array
{
    return array_merge([
        'name' => 'Sabbir Hossain',
        'gender' => 'male',
        'birth_reg_no' => '20191234567890123',
        'religion' => 'islam',
        'address' => 'House 12, Road 4, Dhanmondi, Dhaka',
        'guardian_name' => 'Belal Hossain',
        'guardian_relation' => 'father',
        'guardian_mobile' => '01799887766',
        'guardian_nid' => '1234567890123',
    ], $overrides);
}
