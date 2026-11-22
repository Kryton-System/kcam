<?php

declare(strict_types=1);

namespace App\Domain\Student\Models;

use App\Support\Enums\StudentStatus;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToSchool;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_code',
        'name',
        'name_bn',
        'dob',
        'gender',
        'birth_reg_no',
        'photo',
        'blood_group',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'status' => StudentStatus::class,
        ];
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function primaryGuardian(): HasOne
    {
        return $this->hasOne(Guardian::class)->where('is_primary', true);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->where('status', 'active')->latestOfMany();
    }
}
