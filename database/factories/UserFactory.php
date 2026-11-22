<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => '01'.fake()->numerify('#########'),
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Owner role state.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Owner,
        ]);
    }

    /**
     * Teacher role state.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Teacher,
        ]);
    }

    /**
     * Accountant role state.
     */
    public function accountant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Accountant,
        ]);
    }

    /**
     * Parent role state.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Parent,
        ]);
    }

    /**
     * Super admin state.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => null,
            'role' => UserRole::SuperAdmin,
        ]);
    }
}
