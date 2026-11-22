<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Domain\School\Models\School;
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

    private static ?int $soleSchoolId = null;

    public static function id(): ?int
    {
        if (self::$schoolId !== null) {
            return self::$schoolId;
        }

        $schoolId = Auth::user()?->school_id;

        if ($schoolId !== null) {
            return (int) $schoolId;
        }

        return config('kcam.mode') === 'single' ? self::soleSchoolId() : null;
    }

    /**
     * Forget the cached single school (after installing or deleting a school).
     */
    public static function flush(): void
    {
        self::$soleSchoolId = null;
    }

    /**
     * The one school of a single school install. Null when there is none yet or, to fail
     * closed, when more than one exists.
     */
    private static function soleSchoolId(): ?int
    {
        if (self::$soleSchoolId !== null) {
            return self::$soleSchoolId;
        }

        $ids = School::query()->limit(2)->pluck('id');

        if ($ids->count() !== 1) {
            return null;
        }

        return self::$soleSchoolId = (int) $ids->first();
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
