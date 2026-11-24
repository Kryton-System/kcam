<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use App\Models\User;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: an expense is never edited or deleted, only voided with a reason.
 */
class Expense extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id', 'expense_category_id', 'spent_on', 'amount', 'payee', 'note',
        'recorded_by', 'voided_at', 'voided_by', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'amount' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExpenseFactory
    {
        return ExpenseFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
