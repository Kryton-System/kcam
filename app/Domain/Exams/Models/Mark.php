<?php

declare(strict_types=1);

namespace App\Domain\Exams\Models;

use App\Domain\School\Models\Subject;
use App\Domain\Student\Models\Enrollment;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\MarkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'exam_id',
        'enrollment_id',
        'subject_id',
        'marks',
        'grade',
        'gp',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'decimal:2',
            'gp' => 'decimal:2',
        ];
    }

    protected static function newFactory(): MarkFactory
    {
        return MarkFactory::new();
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
