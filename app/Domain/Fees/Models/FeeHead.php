<?php

declare(strict_types=1);

namespace App\Domain\Fees\Models;

use App\Support\Enums\FeeHeadType;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\FeeHeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeHead extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'name_bn',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => FeeHeadType::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): FeeHeadFactory
    {
        return FeeHeadFactory::new();
    }

    public function structures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }
}
