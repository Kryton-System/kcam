<?php

declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'name',
        'name_bn',
        'numeric_name',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'numeric_name' => 'integer',
        ];
    }

    protected static function newFactory(): SchoolClassFactory
    {
        return SchoolClassFactory::new();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'class_id');
    }
}
