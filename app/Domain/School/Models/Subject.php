<?php

declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'name',
        'name_bn',
        'code',
        'full_marks',
        'pass_marks',
        'is_optional',
    ];

    protected function casts(): array
    {
        return [
            'full_marks' => 'integer',
            'pass_marks' => 'integer',
            'is_optional' => 'boolean',
        ];
    }

    protected static function newFactory(): SubjectFactory
    {
        return SubjectFactory::new();
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
