# 0001. Money as integer poisha

**Status:** Accepted

## Context

KCAM records school fees, payroll, expenses and fee discounts. Receipts and dues must add up exactly, and parents and the accountant compare them with paper books. Binary floating point cannot represent most decimal amounts exactly, so sums and percentage discounts drift by a few paisa. The first schema stored money in `decimal(12,2)` columns (for example `invoices.total`, `payments.amount`, `fee_structures.amount`, `staff.salary_basic`). The money integrity tests need one unit that every calculation uses.

## Decision

- Money is a whole number of poisha (1 taka = 100 poisha), stored in `unsignedBigInteger` columns. No code path uses floats for money.
- `App\Support\Money` is the only parser and formatter:
  - `Money::toPoisha(string $taka)` accepts up to 9 taka digits and up to 2 decimals, and rejects everything else with `InvalidArgumentException`.
  - `Money::toTaka(int $poisha)` formats two decimals for display.
  - `Money::percentOf(int $poisha, int $basisPoints)` applies percentages in basis points (10% is 1000) and rounds half up to a whole poisha.
- Fee discounts store a fixed amount in poisha, or a percentage in basis points, selected by `fee_discounts.type`.
- The conversion migration `2026_11_22_043948_convert_money_to_poisha_and_harden_money_tables.php` multiplied existing values with `ROUND(col * 100)`, and its `down()` reverses the change.

Code evidence:

- `app/Support/Money.php` (parser, formatter, `percentOf`)
- `app/Http/Controllers/FeeController.php` (`Money::toPoisha` on the collect form)
- `app/Domain/Fees/Actions/CollectFeeAction.php` (rejects zero amounts and amounts above the due, logs `amount_poisha`)
- `app/Domain/Fees/Actions/GenerateMonthlyInvoicesAction.php` (percentage and fixed discounts)
- `database/migrations/2026_11_22_043948_convert_money_to_poisha_and_harden_money_tables.php`
- `database/migrations/2026_11_24_053017_create_payslips_table.php` and `2026_11_24_053032_create_transport_assignments_table.php` (new money columns created as integers)
- `tests/Feature/MoneyIntegrityTest.php`

## Consequences

- Sums, comparisons and discounts are exact. Rounding happens in one place (`percentOf`).
- Every new money field must be an integer in poisha. Developers must convert at the edges (forms and displays), and column names should say so when the unit is not obvious.
- Unsigned columns cannot hold negative amounts. Reversals therefore use new rows or void fields (expenses use `voided_at`), not negative values.
- `transport_assignments.monthly_fee` is an `unsignedInteger`, so its maximum is 4,294,967,295 poisha (about 42.9 million taka). The other money columns are `unsignedBigInteger`.
- Reports and exports must format with `Money::toTaka()` or `LocaleFormat::taka()`. Printing a raw poisha value looks wrong by a factor of 100.
