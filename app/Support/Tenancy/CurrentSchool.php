<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * The school the current unit of work belongs to.
 *
 * Web requests resolve it from the logged in user. Queue jobs, scheduled commands and
 * webhooks have no user, so they must set it explicitly with runAs(). Platform wide
 * work (super admin) must opt in with runAsPlatform().
 */
final class CurrentSchool
{
    private static ?int $schoolId = null;

    private static bool $platform = false;

    public static function id(): ?int
    {
        if (self::$schoolId !== null) {
            return self::$schoolId;
        }

        $schoolId = Auth::user()?->school_id;

        return $schoolId === null ? null : (int) $schoolId;
    }

    public static function isPlatform(): bool
    {
        return self::$platform;
    }

    /**
     * Run the callback scoped to one school, then restore the previous context.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function runAs(int $schoolId, Closure $callback): mixed
    {
        $previousId = self::$schoolId;
        $previousPlatform = self::$platform;

        self::$schoolId = $schoolId;
        self::$platform = false;

        try {
            return $callback();
        } finally {
            self::$schoolId = $previousId;
            self::$platform = $previousPlatform;
        }
    }

    /**
     * Run the callback across all schools. Greppable on purpose: review every use.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function runAsPlatform(Closure $callback): mixed
    {
        $previousId = self::$schoolId;
        $previousPlatform = self::$platform;

        self::$schoolId = null;
        self::$platform = true;

        try {
            return $callback();
        } finally {
            self::$schoolId = $previousId;
            self::$platform = $previousPlatform;
        }
    }
}
