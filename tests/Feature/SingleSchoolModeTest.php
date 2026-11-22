<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use App\Support\Tenancy\MissingSchoolContext;
use Database\Seeders\DemoSchoolSeeder;

beforeEach(function (): void {
    config(['kcam.mode' => 'single']);
    CurrentSchool::flush();
});

test('single mode resolves the only school without a logged in user', function (): void {
    $school = School::factory()->create();
    Student::factory()->create(['school_id' => $school->id]);
    CurrentSchool::flush();

    expect(CurrentSchool::id())->toBe($school->id)
        ->and(Student::count())->toBe(1);
});

test('single mode fills school_id on new rows', function (): void {
    $school = School::factory()->create();
    CurrentSchool::flush();

    $student = Student::factory()->make(['school_id' => null]);
    $student->save();

    expect($student->fresh()->school_id)->toBe($school->id);
});

test('single mode fails closed when more than one school exists', function (): void {
    School::factory()->count(2)->create();
    CurrentSchool::flush();

    expect(CurrentSchool::id())->toBeNull()
        ->and(fn () => Student::count())->toThrow(MissingSchoolContext::class);
});

test('single mode has no school before install and fails closed', function (): void {
    expect(CurrentSchool::id())->toBeNull()
        ->and(fn () => Student::count())->toThrow(MissingSchoolContext::class);
});

test('multi mode never guesses a school', function (): void {
    config(['kcam.mode' => 'multi']);
    School::factory()->create();
    CurrentSchool::flush();

    expect(CurrentSchool::id())->toBeNull();
});

test('public school registration is closed in single mode', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

test('public school registration stays open in multi mode', function (): void {
    config(['kcam.mode' => 'multi']);

    $this->get('/register')->assertOk();
});

test('a disabled module is not reachable and not shown in the menu', function (): void {
    $school = School::factory()->create();
    $owner = schoolUser(UserRole::Owner, $school);
    config(['kcam.modules.exams' => false]);

    $this->actingAs($owner)->get('/exams')->assertNotFound();
    $this->actingAs($owner)->get('/dashboard')->assertOk()->assertDontSee(route('exams.index'), false);
});

test('an enabled module is reachable', function (): void {
    $school = School::factory()->create();
    $owner = schoolUser(UserRole::Owner, $school);
    config(['kcam.modules.exams' => true]);

    $this->actingAs($owner)->get('/exams')->assertOk();
});

test('kcam:install creates the school and owner', function (): void {
    $this->artisan('kcam:install', [
        '--name' => 'Rose Bud Kindergarten',
        '--phone' => '01811223344',
        '--owner-name' => 'Tanvir Ahmed',
        '--owner-email' => 'tanvir@rosebud.test',
        '--owner-password' => 'Str0ng-Passw0rd!x',
    ])->assertSuccessful();

    $school = School::firstOrFail();
    $owner = User::where('email', 'tanvir@rosebud.test')->firstOrFail();

    expect($school->name)->toBe('Rose Bud Kindergarten')
        ->and($owner->school_id)->toBe($school->id)
        ->and($owner->role)->toBe(UserRole::Owner)
        ->and($owner->hasRole(UserRole::Owner->value))->toBeTrue();
});

test('kcam:install refuses to run twice', function (): void {
    School::factory()->create();

    $this->artisan('kcam:install', [
        '--name' => 'Second School',
        '--phone' => '01811223344',
        '--owner-name' => 'Someone',
        '--owner-email' => 'someone@example.com',
        '--owner-password' => 'Str0ng-Passw0rd!x',
    ])->assertFailed();

    expect(School::count())->toBe(1);
});

test('kcam:install rejects a weak password', function (): void {
    $this->artisan('kcam:install', [
        '--name' => 'Rose Bud Kindergarten',
        '--phone' => '01811223344',
        '--owner-name' => 'Tanvir Ahmed',
        '--owner-email' => 'tanvir@rosebud.test',
        '--owner-password' => '123',
    ])->assertFailed();

    expect(School::count())->toBe(0);
});

test('the dashboard renders for an owner when recent payments exist', function (): void {
    config(['kcam.mode' => 'multi']);
    app(DemoSchoolSeeder::class)->run();

    $this->actingAs(User::where('email', 'owner@kcam.test')->firstOrFail())
        ->get('/dashboard')
        ->assertOk();
});
