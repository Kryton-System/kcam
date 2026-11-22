<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use App\Support\Tenancy\MissingSchoolContext;
use App\Support\Traits\BelongsToSchool;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

test('querying a tenant model with no school context fails closed', function (): void {
    Student::query()->count();
})->throws(MissingSchoolContext::class);

test('a user without a school cannot read tenant data', function (): void {
    $superAdmin = User::factory()->superAdmin()->create(['school_id' => null]);

    $this->actingAs($superAdmin);

    Student::query()->count();
})->throws(MissingSchoolContext::class);

test('runAs scopes queries to the given school and restores the previous context', function (): void {
    $one = School::factory()->create();
    $two = School::factory()->create();
    Student::factory()->create(['school_id' => $one->id]);
    Student::factory()->count(2)->create(['school_id' => $two->id]);

    expect(CurrentSchool::runAs($one->id, fn (): int => Student::count()))->toBe(1)
        ->and(CurrentSchool::runAs($two->id, fn (): int => Student::count()))->toBe(2)
        ->and(CurrentSchool::id())->toBeNull();
});

test('runAs restores the context even when the callback throws', function (): void {
    $school = School::factory()->create();

    try {
        CurrentSchool::runAs($school->id, function (): void {
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(CurrentSchool::id())->toBeNull();
});

test('runAsPlatform is the explicit escape hatch that sees every school', function (): void {
    Student::factory()->count(2)->create();

    expect(CurrentSchool::runAsPlatform(fn (): int => Student::count()))->toBe(2);

    expect(fn () => Student::count())->toThrow(MissingSchoolContext::class);
});

test('a logged in user with a school is scoped without extra setup', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    Student::factory()->create(['school_id' => $school->id]);
    Student::factory()->create(['school_id' => $other->id]);

    $this->actingAs(schoolUser(UserRole::Owner, $school));

    expect(Student::count())->toBe(1);
});

test('every model with a school_id column uses BelongsToSchool', function (): void {
    // User is deliberately excluded: it is the identity table used to resolve the school.
    $files = collect(File::allFiles(app_path('Domain')))
        ->filter(fn ($file): bool => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR))
        ->map(fn ($file): string => 'App\\'.str_replace(
            ['/', '.php'],
            ['\\', ''],
            substr($file->getPathname(), strlen(app_path()) + 1),
        ));

    expect($files)->not->toBeEmpty();

    $unscoped = [];

    foreach ($files as $class) {
        $table = (new $class)->getTable();

        if (Schema::hasColumn($table, 'school_id') && ! in_array(BelongsToSchool::class, class_uses_recursive($class), true)) {
            $unscoped[] = $class;
        }
    }

    expect($unscoped)->toBe([]);
});
