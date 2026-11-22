<?php

declare(strict_types=1);

namespace App\Domain\Fees\Models;

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\SchoolClass;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\FeeStructureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'fee_head_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    protected static function newFactory(): FeeStructureFactory
    {
        return FeeStructureFactory::new();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }
}
