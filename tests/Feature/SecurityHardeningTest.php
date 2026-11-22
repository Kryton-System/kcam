<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\UserRole;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

test('flash messages are JavaScript escaped inside the toast', function (): void {
    $this->actingAs(schoolUser(UserRole::Owner))
        ->withSession(['success' => "x');alert(1);//"])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('x'.chr(92).'u0027', false)
        ->assertDontSee('x&#039;);alert(1)', false)
        ->assertDontSee("x');alert(1)", false);
});

test('security headers are sent on every response', function (): void {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->has('Content-Security-Policy') || $response->headers->has('Content-Security-Policy-Report-Only'))
        ->toBeTrue();
});

test('hsts is only sent over https', function (): void {
    $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
});

test('health endpoint does not reveal the environment', function (): void {
    $this->get('/health')->assertOk()->assertJsonMissingPath('environment');
});

test('registration is rate limited', function (): void {
    foreach (range(1, 5) as $ignored) {
        $this->post('/register', []);
    }

    $this->post('/register', [])->assertStatus(429);
});

test('registered owners are not auto verified', function (): void {
    $this->post('/register', [
        'name' => 'Green Valley',
        'phone' => '01711223344',
        'owner_name' => 'Owner One',
        'owner_email' => 'owner@example.com',
        'owner_password' => 'Str0ng-Passw0rd!x',
        'owner_password_confirmation' => 'Str0ng-Passw0rd!x',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'owner@example.com')->first()->email_verified_at)->toBeNull();
});

test('the registration phone number must be unique and well formed', function (): void {
    schoolUser(UserRole::Owner)->forceFill(['mobile' => '01711223344'])->save();

    $payload = [
        'name' => 'Green Valley',
        'phone' => '01711223344',
        'owner_name' => 'Owner Two',
        'owner_email' => 'two@example.com',
        'owner_password' => 'Str0ng-Passw0rd!x',
        'owner_password_confirmation' => 'Str0ng-Passw0rd!x',
    ];

    $this->post('/register', $payload)->assertSessionHasErrors(['phone']);
    $this->post('/register', array_merge($payload, ['phone' => '123']))->assertSessionHasErrors(['phone']);
});

test('role and school_id cannot be mass assigned onto a user', function (): void {
    $school = School::factory()->create();

    $user = User::create([
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'whatever-123',
        'school_id' => $school->id,
        'role' => UserRole::SuperAdmin,
    ]);

    expect($user->fresh()->school_id)->toBeNull()
        ->and($user->fresh()->role)->toBe(UserRole::Parent);
});

test('a user created without a role gets the least privileged role', function (): void {
    $user = User::create(['name' => 'N', 'email' => 'n@example.com', 'password' => 'x-12345678']);

    expect($user->fresh()->role)->toBe(UserRole::Parent);
});

test('mobile numbers are unique across users', function (): void {
    schoolUser(UserRole::Teacher)->forceFill(['mobile' => '01700000001'])->save();

    expect(fn () => schoolUser(UserRole::Teacher)->forceFill(['mobile' => '01700000001'])->save())
        ->toThrow(QueryException::class);
});

test('demo login shortcuts only exist in the local environment', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');
    $this->get('/login')->assertOk()->assertDontSee('owner@kcam.test');

    app()->detectEnvironment(fn (): string => 'local');
    $this->get('/login')->assertOk()->assertSee('owner@kcam.test');
});

test('the demo seeder refuses to run outside local and testing', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    // Run the seeder directly: db:seed itself would ask for confirmation in production.
    app(DemoSchoolSeeder::class)->run();

    expect(User::where('email', 'owner@kcam.test')->exists())->toBeFalse();
});

test('failed logins give the same message for unknown and known accounts', function (): void {
    $known = schoolUser(UserRole::Owner);
    $known->forceFill(['email' => 'known@example.com', 'password' => Hash::make('right-password')])->save();

    $this->from('/login')->post('/login', ['login' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('login');
    $unknownMessage = session('errors')->get('login');

    $this->from('/login')->post('/login', ['login' => 'known@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('login');

    expect(session('errors')->get('login'))->toBe($unknownMessage);
});

test('the service worker never precaches or caches authenticated pages', function (): void {
    $worker = file_get_contents(public_path('sw.js'));

    expect($worker)
        ->not->toContain("'/',")
        ->toContain('/build/');
});

test('the missing module pages render', function (string $uri, UserRole $role): void {
    $this->actingAs(schoolUser($role))->get($uri)->assertOk();
})->with([
    ['/attendance', UserRole::Teacher],
    ['/fees', UserRole::Accountant],
    ['/exams', UserRole::Owner],
    ['/notices', UserRole::Owner],
]);
