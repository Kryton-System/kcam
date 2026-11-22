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
     * Get the Bangla label for this role.
     */
    public function labelBn(): string
    {
        return match ($this) {
            self::Owner => 'মালিক',
            self::Teacher => 'শিক্ষক',
            self::Accountant => 'হিসাবরক্ষক',
            self::Parent => 'অভিভাবক',
            self::SuperAdmin => 'সুপার অ্যাডমিন',
        };
    }

    /**
     * Get the English label for this role.
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Teacher => 'Teacher',
            self::Accountant => 'Accountant',
            self::Parent => 'Parent',
            self::SuperAdmin => 'Super Admin',
        };
    }

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
}
