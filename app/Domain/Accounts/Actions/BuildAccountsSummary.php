<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Actions;

use App\Domain\Accounts\Models\Expense;
use App\Domain\Payments\Models\Payment;
use App\Support\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

/**
 * Income (completed payments) minus expenses (not voided). All amounts are poisha.
 */
class BuildAccountsSummary
{
    /**
     * @return array{income: int, expenses: int, net: int, categories: list<array{name: string, total: int}>}
     */
    public function forMonth(CarbonImmutable $month): array
    {
        return $this->forRange($month->startOfMonth(), $month->endOfMonth());
    }

    /**
     * @return array{income: int, expenses: int, net: int, categories: list<array{name: string, total: int}>}
     */
    public function forRange(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $income = (int) Payment::where('status', PaymentStatus::Completed->value)
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('amount');

        $categories = Expense::active()
            ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
            ->with('category')
            ->get()
            ->groupBy('expense_category_id')
            ->map(fn ($rows) => [
                'name' => $rows->first()->category->label(),
                'total' => (int) $rows->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $expenses = array_sum(array_column($categories, 'total'));

        return [
            'income' => $income,
            'expenses' => $expenses,
            'net' => $income - $expenses,
            'categories' => $categories,
        ];
    }
}
