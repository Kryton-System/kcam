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
 * @return array{0: User, 1: SchoolClass}
 */
function bdAdmissionContext(?School $school = null): array
{
    $school ??= School::factory()->create();
    AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);

    return [schoolUser(UserRole::Owner, $school), $class];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bdAdmissionPayload(SchoolClass $class, array $overrides = []): array
{
    return array_merge([
        'name' => 'Sabbir Hossain',
        'name_bn' => 'সাব্বির হোসেন',
        'dob' => '2019-02-10',
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
    ], $overrides);
}

function bdAdmittedStudent(int $schoolId): Student
{
    return CurrentSchool::runAs($schoolId, fn (): Student => Student::with('primaryGuardian')->firstOrFail());
}

test('admission form asks for BDRIS birth registration number, photo, religion, address and guardian NID', function (): void {
    [$owner] = bdAdmissionContext();

    $this->actingAs($owner)->get(route('students.create'))
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="birth_reg_no"', false)
        ->assertSee('name="photo"', false)
        ->assertSee('name="religion"', false)
        ->assertSee('name="address"', false)
        ->assertSee('name="guardian_nid"', false)
        ->assertSee('name="guardian_nid_front"', false)
        ->assertSee('name="guardian_nid_back"', false);
});

test('admitting a student stores BDRIS number, photo, religion and guardian NID documents', function (): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class))
        ->assertRedirect(route('students.index'));

    $student = bdAdmittedStudent($owner->school_id);

    expect($student->birth_reg_no)->toBe('20191234567890123')
        ->and($student->religion)->toBe(Religion::Islam)
        ->and($student->address)->toContain('Dhanmondi')
        ->and($student->photo)->toStartWith("students/{$owner->school_id}/photo/");

    Storage::disk('local')->assertExists($student->photo);

    $guardian = $student->primaryGuardian;

    expect($guardian->nid)->toBe('1234567890123')
        ->and($guardian->nid_front_path)->toStartWith("students/{$owner->school_id}/guardian-nid/")
        ->and($guardian->nid_back_path)->toStartWith("students/{$owner->school_id}/guardian-nid/");

    Storage::disk('local')->assertExists($guardian->nid_front_path);
    Storage::disk('local')->assertExists($guardian->nid_back_path);
});

test('admission works without the optional BDRIS number, photo and NID documents', function (): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, [
            'birth_reg_no' => null,
            'photo' => null,
            'guardian_nid' => null,
            'guardian_nid_front' => null,
            'guardian_nid_back' => null,
        ]))
        ->assertRedirect(route('students.index'));

    $student = bdAdmittedStudent($owner->school_id);

    expect($student->birth_reg_no)->toBeNull()
        ->and($student->photo)->toBeNull()
        ->and($student->primaryGuardian->nid_front_path)->toBeNull();
});

test('BDRIS birth registration number must be exactly 17 digits', function (string $value): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, ['birth_reg_no' => $value]))
        ->assertSessionHasErrors('birth_reg_no');
})->with(['12345', '2019123456789012', '201912345678901234', 'ABCDEFGHIJKLMNOPQ']);

test('Bangla digits in the BDRIS number are converted to English digits', function (): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, ['birth_reg_no' => '২০১৯১২৩৪৫৬৭৮৯০১২৩']))
        ->assertRedirect(route('students.index'));

    expect(bdAdmittedStudent($owner->school_id)->birth_reg_no)->toBe('20191234567890123');
});

test('BDRIS number cannot be registered twice in the same school', function (): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)->post(route('students.store'), bdAdmissionPayload($class))
        ->assertRedirect(route('students.index'));

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, ['name' => 'Another Child']))
        ->assertSessionHasErrors('birth_reg_no');
});

test('BDRIS number may repeat across different schools', function (): void {
    [$owner, $class] = bdAdmissionContext();
    [$otherOwner, $otherClass] = bdAdmissionContext();

    $this->actingAs($owner)->post(route('students.store'), bdAdmissionPayload($class))
        ->assertRedirect(route('students.index'));

    $this->actingAs($otherOwner)->post(route('students.store'), bdAdmissionPayload($otherClass))
        ->assertSessionDoesntHaveErrors('birth_reg_no')
        ->assertRedirect(route('students.index'));
});

test('guardian NID number must be 10, 13 or 17 digits', function (string $value): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, ['guardian_nid' => $value]))
        ->assertSessionHasErrors('guardian_nid');
})->with(['12345', '123456789', '123456789012', '1234567890123456']);

test('guardian NID number of 10 digits is accepted', function (): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, ['guardian_nid' => '1234567890']))
        ->assertRedirect(route('students.index'));

    expect(bdAdmittedStudent($owner->school_id)->primaryGuardian->nid)->toBe('1234567890');
});

test('student photo must be an image under 2 MB', function (UploadedFile $photo): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, ['photo' => $photo]))
        ->assertSessionHasErrors('photo');
})->with([
    'pdf document' => fn () => UploadedFile::fake()->create('photo.pdf', 100, 'application/pdf'),
    'image over 2 MB' => fn () => UploadedFile::fake()->image('big.jpg')->size(3000),
]);

test('guardian NID documents must be an image or PDF under 2 MB', function (string $field, UploadedFile $file): void {
    [$owner, $class] = bdAdmissionContext();

    $this->actingAs($owner)
        ->post(route('students.store'), bdAdmissionPayload($class, [$field => $file]))
        ->assertSessionHasErrors($field);
})->with([
    'front is a text file' => ['guardian_nid_front', fn () => UploadedFile::fake()->create('nid.txt', 10, 'text/plain')],
    'back is over 2 MB' => ['guardian_nid_back', fn () => UploadedFile::fake()->create('nid.pdf', 3000, 'application/pdf')],
]);

test('student photo and guardian NID documents are served only to the owning school', function (): void {
    [$owner, $class] = bdAdmissionContext();
    $this->actingAs($owner)->post(route('students.store'), bdAdmissionPayload($class));
    $student = bdAdmittedStudent($owner->school_id);

    $this->actingAs($owner)->get(route('students.photo', $student))->assertOk();
    $this->actingAs($owner)->get(route('students.guardian-nid', [$student, 'front']))->assertOk();
    $this->actingAs($owner)->get(route('students.guardian-nid', [$student, 'back']))->assertOk();

    $outsider = schoolUser(UserRole::Owner);
    $this->actingAs($outsider)->get(route('students.photo', $student))->assertNotFound();
    $this->actingAs($outsider)->get(route('students.guardian-nid', [$student, 'front']))->assertNotFound();
});

test('guests cannot open student photos or guardian NID documents', function (): void {
    [$owner, $class] = bdAdmissionContext();
    $this->actingAs($owner)->post(route('students.store'), bdAdmissionPayload($class));
    $student = bdAdmittedStudent($owner->school_id);

    app('auth')->forgetGuards();

    $this->get(route('students.photo', $student))->assertRedirect(route('login'));
    $this->get(route('students.guardian-nid', [$student, 'front']))->assertRedirect(route('login'));
});

test('guardian NID side must be front or back', function (): void {
    [$owner, $class] = bdAdmissionContext();
    $this->actingAs($owner)->post(route('students.store'), bdAdmissionPayload($class));
    $student = bdAdmittedStudent($owner->school_id);

    $this->actingAs($owner)->get('/students/'.$student->id.'/guardian-nid/either')->assertNotFound();
});

test('student profile shows BDRIS number, religion and the photo', function (): void {
    [$owner, $class] = bdAdmissionContext();
    $this->actingAs($owner)->post(route('students.store'), bdAdmissionPayload($class));
    $student = bdAdmittedStudent($owner->school_id);

    $this->actingAs($owner)->get(route('students.show', $student))
        ->assertOk()
        ->assertSee('20191234567890123')
        ->assertSee('ইসলাম')
        ->assertSee(route('students.photo', $student), false);
});
