<?php

declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Domain\Student\Models\Enrollment;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'starts_on',
        'ends_on',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    protected static function newFactory(): AcademicYearFactory
    {
        return AcademicYearFactory::new();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
