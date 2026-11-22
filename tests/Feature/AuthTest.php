<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function (): void {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('কেসিএএম')
        ->assertSee('লগইন');
});

test('users can authenticate using the login screen with email or phone', function (): void {
    $school = School::factory()->create();
    $user = User::factory()->create([
        'school_id' => $school->id,
        'email' => 'teacher@kcam.test',
        'mobile' => '01711223344',
        'password' => bcrypt('password123'),
        'role' => UserRole::Teacher,
    ]);

    // Test login via email
    $response = $this->post('/login', [
        'login' => 'teacher@kcam.test',
        'password' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');

    // Logout
    $this->post('/logout');
    $this->assertGuest();

    // Test login via mobile
    $response = $this->post('/login', [
        'login' => '01711223344',
        'password' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
});

test('users cannot authenticate with invalid password', function (): void {
    $user = User::factory()->create([
        'email' => 'owner@kcam.test',
        'password' => bcrypt('secret'),
    ]);

    $response = $this->from('/login')->post('/login', [
        'login' => 'owner@kcam.test',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('login');
});

test('school registration wizard registers school and owner', function (): void {
    $response = $this->post('/register', [
        'name' => 'Al-Huda Kindergarten',
        'name_bn' => 'আল-হুদা কিন্ডারগার্টেন',
        'phone' => '01855667788',
        'address' => 'Uttara, Dhaka',
        'address_bn' => 'উত্তরা, ঢাকা',
        'owner_name' => 'Maulana Abdus Sobhan',
        'owner_email' => 'owner@alhuda.test',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');

    $school = School::where('name', 'Al-Huda Kindergarten')->first();
    expect($school)->not->toBeNull();

    $owner = User::where('email', 'owner@alhuda.test')->first();
    expect($owner)->not->toBeNull()
        ->and($owner->school_id)->toBe($school->id)
        ->and($owner->role)->toBe(UserRole::Owner);
});
