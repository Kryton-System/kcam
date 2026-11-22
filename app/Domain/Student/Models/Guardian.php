<?php

declare(strict_types=1);

namespace App\Domain\Student\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'name',
        'name_bn',
        'relation',
        'mobile',
        'nid',
        'email',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    protected static function newFactory(): GuardianFactory
    {
        return GuardianFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
