<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use RuntimeException;

/**
 * Thrown when a tenant model is queried while no school is known.
 *
 * Failing closed is deliberate: silently returning every school's rows is a data leak.
 */
final class MissingSchoolContext extends RuntimeException
{
    public static function forQuery(): self
    {
        return new self(
            'No current school is set. Authenticate a school user, or wrap the work in '
            .'CurrentSchool::runAs($schoolId, ...) (jobs, commands, webhooks) or '
            .'CurrentSchool::runAsPlatform(...) (super admin).'
        );
    }
}
