<?php

declare(strict_types=1);

namespace App\Domain\Fees\Models;

use App\Domain\Student\Models\Student;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\FeeDiscountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDiscount extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'fee_head_id',
        'type',
        'value',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    protected static function newFactory(): FeeDiscountFactory
    {
        return FeeDiscountFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }
}
