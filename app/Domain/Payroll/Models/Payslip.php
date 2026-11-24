<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Staff\Models\Staff;
use App\Models\User;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\PayslipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use BelongsToSchool;
    use HasFactory;
    use SoftDeletes;

    public const PENDING = 'pending';

    public const PAID = 'paid';

    protected $fillable = [
        'school_id',
        'staff_id',
        'month',
        'basic',
        'deductions',
        'advances',
        'net',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'integer',
            'deductions' => 'integer',
            'advances' => 'integer',
            'net' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PayslipFactory
    {
        return PayslipFactory::new();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }
}
