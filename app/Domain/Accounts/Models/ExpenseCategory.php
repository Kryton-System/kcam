<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = ['school_id', 'name', 'name_bn'];

    protected static function newFactory(): ExpenseCategoryFactory
    {
        return ExpenseCategoryFactory::new();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function label(): string
    {
        return $this->name_bn ?: $this->name;
    }
}
