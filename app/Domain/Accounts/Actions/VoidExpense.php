<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Actions;

use App\Domain\Accounts\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VoidExpense
{
    public function execute(Expense $expense, string $reason, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $reason, $actor): Expense {
            $locked = Expense::lockForUpdate()->findOrFail($expense->id);

            if ($locked->isVoided()) {
                throw new InvalidArgumentException('This expense is already voided.');
            }

            $locked->update([
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
            ]);

            activity()
                ->performedOn($locked)
                ->causedBy($actor)
                ->withProperties(['amount_poisha' => $locked->amount, 'reason' => $reason])
                ->log('expense_voided');

            return $locked;
        });
    }
}
