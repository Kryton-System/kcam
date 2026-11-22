<?php

declare(strict_types=1);

namespace App\Domain\Exams\Models;

use App\Domain\School\Models\AcademicYear;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'name_bn',
        'starts_on',
        'ends_on',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'published_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExamFactory
    {
        return ExamFactory::new();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
