<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * User roles in the KCAM system.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Teacher = 'teacher';
    case Accountant = 'accountant';
    case Parent = 'parent';
    case SuperAdmin = 'super_admin';

    /**
     * Get all school-level roles (excludes super admin).
     *
     * @return array<self>
     */
    public static function schoolRoles(): array
    {
        return [
            self::Owner,
            self::Teacher,
            self::Accountant,
            self::Parent,
        ];
    }

    public function label(): string
    {
        return __('enums.roles.'.$this->value);
    }
}
