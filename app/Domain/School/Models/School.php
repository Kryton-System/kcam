<?php

declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'name_bn',
        'logo',
        'eiin',
        'phone',
        'email',
        'address',
        'address_bn',
        'plan_id',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $flush = static fn () => CurrentSchool::flush();

        static::saved($flush);
        static::deleted($flush);
    }

    protected static function newFactory(): SchoolFactory
    {
        return SchoolFactory::new();
    }

    // ---- Relationships ----

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function currentAcademicYear(): ?AcademicYear
    {
        return $this->academicYears()->where('is_current', true)->first();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
