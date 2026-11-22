<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\School\Models\School;
use App\Support\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'mobile', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Least privilege by default. school_id and role are never mass assignable: set them
     * explicitly (forceFill) in the Actions that create users.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'parent',
    ];

    /**
     * Keep the spatie role in step with the role column, so permissions follow the role.
     */
    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            if (! $user->wasRecentlyCreated && ! $user->wasChanged('role')) {
                return;
            }

            $roleName = $user->role?->value;

            if ($roleName !== null && Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$roleName]);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * School relationship.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Check if user is school owner.
     */
    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    /**
     * Check if user is teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    /**
     * Check if user is accountant.
     */
    public function isAccountant(): bool
    {
        return $this->role === UserRole::Accountant;
    }

    /**
     * Check if user is parent.
     */
    public function isParent(): bool
    {
        return $this->role === UserRole::Parent;
    }
}
