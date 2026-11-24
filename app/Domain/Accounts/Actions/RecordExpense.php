<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Actions;

use App\Domain\Accounts\Models\Expense;
use App\Models\User;
use InvalidArgumentException;

class RecordExpense
{
    /**
     * @param  array{expense_category_id: int, spent_on: string, payee: string, note?: ?string}  $data
     */
    public function execute(array $data, int $amountPoisha, User $recorder): Expense
    {
        if ($amountPoisha < 1) {
            throw new InvalidArgumentException('Expense amount must be positive.');
        }

        $expense = Expense::create([
            'school_id' => $recorder->school_id,
            'expense_category_id' => $data['expense_category_id'],
            'spent_on' => $data['spent_on'],
            'amount' => $amountPoisha,
            'payee' => $data['payee'],
            'note' => $data['note'] ?? null,
            'recorded_by' => $recorder->id,
        ]);

        activity()
            ->performedOn($expense)
            ->causedBy($recorder)
            ->withProperties(['amount_poisha' => $amountPoisha, 'payee' => $expense->payee])
            ->log('expense_recorded');

        return $expense;
    }
}
