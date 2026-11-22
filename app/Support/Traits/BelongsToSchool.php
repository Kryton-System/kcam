<?php

declare(strict_types=1);

namespace App\Support\Traits;

use App\Domain\School\Models\School;
use App\Support\Tenancy\CurrentSchool;
use App\Support\Tenancy\MissingSchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToSchool
 *
 * Multi-tenant isolation via school_id. Queries are scoped to the current school and
 * school_id is filled on creation. The scope fails closed: with no school context it
 * throws instead of returning every school's rows.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder): void {
            $schoolId = CurrentSchool::id();

            if ($schoolId !== null) {
                $builder->where($builder->getModel()->getTable().'.school_id', $schoolId);

                return;
            }

            if (! CurrentSchool::isPlatform()) {
                throw MissingSchoolContext::forQuery();
            }
        });

        static::creating(function (Model $model): void {
            if (! $model->school_id && CurrentSchool::id() !== null) {
                $model->school_id = CurrentSchool::id();
            }
        });
    }

    /**
     * Relationship to the owning school.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Scope to a specific school (bypasses the global scope for admin use).
     */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->withoutGlobalScope('school')->where(
            $this->getTable().'.school_id',
            $schoolId
        );
    }
}
