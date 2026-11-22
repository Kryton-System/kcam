<?php

declare(strict_types=1);

use App\Domain\Fees\Models\Invoice;
use App\Models\User;
use App\Support\Enums\UserRole;

/*
 * Permissions matrix: every role against every protected route.
 * Allowed means "not 403"; forbidden means exactly 403.
 */
dataset('route permissions', [
    'students index' => ['get', '/students', [UserRole::Owner, UserRole::Teacher, UserRole::Accountant], [UserRole::Parent]],
    'students create form' => ['get', '/students/create', [UserRole::Owner], [UserRole::Teacher, UserRole::Accountant, UserRole::Parent]],
    'students store' => ['post', '/students', [UserRole::Owner], [UserRole::Teacher, UserRole::Accountant, UserRole::Parent]],
    'attendance index' => ['get', '/attendance', [UserRole::Owner, UserRole::Teacher], [UserRole::Accountant, UserRole::Parent]],
    'attendance store' => ['post', '/attendance', [UserRole::Owner, UserRole::Teacher], [UserRole::Accountant, UserRole::Parent]],
    'fees index' => ['get', '/fees', [UserRole::Owner, UserRole::Accountant], [UserRole::Teacher, UserRole::Parent]],
    'fees collect' => ['post', '/fees/{invoice}/collect', [UserRole::Owner, UserRole::Accountant], [UserRole::Teacher, UserRole::Parent]],
    'exams index' => ['get', '/exams', [UserRole::Owner, UserRole::Teacher, UserRole::Parent], [UserRole::Accountant]],
    'notices index' => ['get', '/notices', [UserRole::Owner, UserRole::Teacher, UserRole::Accountant, UserRole::Parent], []],
    'notices store' => ['post', '/notices', [UserRole::Owner, UserRole::Teacher], [UserRole::Accountant, UserRole::Parent]],
]);

/**
 * Replace the {invoice} placeholder with a real invoice of the user's school, so the
 * 403 is what we test and not a missing record.
 */
function resolveUri(string $uri, User $user): string
{
    if (! str_contains($uri, '{invoice}')) {
        return $uri;
    }

    $invoice = Invoice::factory()->create(['school_id' => $user->school_id]);

    return str_replace('{invoice}', (string) $invoice->id, $uri);
}

test('roles without the permission get 403', function (string $method, string $uri, array $allowed, array $forbidden): void {
    foreach ($forbidden as $role) {
        $user = schoolUser($role);

        $this->actingAs($user)
            ->{$method}(resolveUri($uri, $user))
            ->assertForbidden();
    }
})->with('route permissions');

test('roles with the permission are not blocked', function (string $method, string $uri, array $allowed, array $forbidden): void {
    foreach ($allowed as $role) {
        $user = schoolUser($role);
        $response = $this->actingAs($user)->{$method}(resolveUri($uri, $user));

        expect($response->status())->not->toBe(403)
            ->and($response->status())->not->toBe(500);
    }
})->with('route permissions');

test('guests are redirected to login for protected routes', function (string $method, string $uri): void {
    $this->{$method}($uri)->assertRedirect('/login');
})->with([
    ['get', '/students'],
    ['post', '/students'],
    ['get', '/attendance'],
    ['get', '/fees'],
    ['get', '/exams'],
    ['get', '/notices'],
    ['get', '/dashboard'],
]);

test('a role column change keeps the permission role in sync', function (): void {
    $user = schoolUser(UserRole::Teacher);

    expect($user->hasRole('teacher'))->toBeTrue()
        ->and($user->can('take-attendance'))->toBeTrue()
        ->and($user->can('collect-fees'))->toBeFalse();

    $user->forceFill(['role' => UserRole::Accountant])->save();
    $user = User::find($user->id);

    expect($user->hasRole('accountant'))->toBeTrue()
        ->and($user->hasRole('teacher'))->toBeFalse()
        ->and($user->can('collect-fees'))->toBeTrue();
});
