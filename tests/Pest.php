<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        CurrentSchool::flush();
        $this->seed(RoleAndPermissionSeeder::class);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Create a user with the given role inside a school (a new school when none is given).
 */
function schoolUser(UserRole $role, ?School $school = null): User
{
    $school ??= School::factory()->create();

    return User::factory()->create([
        'school_id' => $school->id,
        'role' => $role,
    ]);
}
