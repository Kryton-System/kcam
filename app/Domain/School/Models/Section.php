<?php

declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'name',
        'name_bn',
    ];

    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
