# Accounts

Accounts covers school expenses, a monthly income and expense summary, and a daily cash book. Income comes from completed fee payments recorded in [Fees and payments](fees-and-payments.md). Expenses are append-only: they are never edited or deleted, only voided with a reason. All amounts are whole poisha (1 taka = 100 poisha) and are shown with `LocaleFormat::taka`.

## Screens and routes

Every route is under `/accounts` and the `module:accounts` middleware. The module switch is `config('kcam.modules.accounts')` (environment variable `KCAM_MODULE_ACCOUNTS`). When it is off, these routes return 404.

| Method | URI | Name | Permission | Purpose |
|---|---|---|---|---|
| GET | `/accounts` | `accounts.index` | `view-accounts` | Monthly summary |
| GET | `/accounts/expenses` | `accounts.expenses` | `view-accounts` | Expenses for a month, plus forms |
| POST | `/accounts/expenses` | `accounts.expenses.store` | `manage-expenses` | Record an expense |
| POST | `/accounts/expenses/{expense}/void` | `accounts.expenses.void` | `manage-expenses` | Void an expense with a reason |
| POST | `/accounts/categories` | `accounts.categories.store` | `manage-expenses` | Add an expense category |
| GET | `/accounts/cash-book` | `accounts.cash-book` | `view-accounts` | Daily cash book (printable page) |

Read screens need `view-accounts`. Write routes need `manage-expenses`. The Expenses page hides its forms and void buttons without `manage-expenses`, but the route middleware is what enforces it.

| Role | `view-accounts` | `manage-expenses` |
|---|---|---|
| `super_admin` | Yes | Yes |
| `owner` | Yes | Yes |
| `accountant` | Yes | Yes |
| `teacher` | No | No |
| `parent` | No | No |

See [Roles and permissions](roles-and-permissions.md) for the full matrix.

## Period filters

`App\Http\Requests\AccountsPeriodRequest` is used by all three read screens.

| Input | Format | Used by | Default when absent |
|---|---|---|---|
| `month` | `YYYY-MM` | Summary, Expenses | Current month |
| `date` | `YYYY-MM-DD` | Cash book | Today |

Both are nullable. Bangla digits are converted to ASCII before validation. A malformed value fails validation and does not reach the query.

## Expense categories

`expense_categories` columns: `school_id`, `name` (required, max 100), `name_bn` (optional, max 100), timestamps. `name` is unique per school, enforced by the request rule and by a database unique key on `(school_id, name)`.

The label shown everywhere is `name_bn` when it is set, otherwise `name`.

Categories are added from the Expenses page ("New category" form). There is no edit, rename, deactivate or delete. Neither `SetupSchoolAction` nor the demo seeder creates categories, so a school must add at least one before it can record an expense.

## Expenses

`expenses` columns:

| Column | Type | Notes |
|---|---|---|
| `school_id` | FK | Tenant scope |
| `expense_category_id` | FK to `expense_categories` | Restricted on delete |
| `spent_on` | date | Date of the expense |
| `amount` | unsigned big integer | Poisha |
| `payee` | string, max 150 | Who was paid |
| `note` | string, max 255, nullable | |
| `recorded_by` | FK to `users`, nullable | Set to null if the user is deleted |
| `voided_at` | timestamp, nullable | Set when voided. Null means active. |
| `voided_by` | FK to `users`, nullable | |
| `void_reason` | string, max 255, nullable | |

Indexes: `(school_id, spent_on)` and `(school_id, expense_category_id)`.

### Recording an expense

`StoreExpenseRequest` rules, then `RecordExpense::execute`:

| Field | Rules |
|---|---|
| `expense_category_id` | Required integer, must exist in this school's categories |
| `spent_on` | Required, `YYYY-MM-DD`, not after today |
| `amount` | Required, up to 9 whole digits and up to 2 decimals, not zero. Converted with `Money::toPoisha`. |
| `payee` | Required, max 150 |
| `note` | Optional, max 255 |

`RecordExpense` also rejects an amount below 1 poisha and writes an `expense_recorded` activity entry. Future-dated expenses cannot be recorded.

### Voiding an expense

`VoidExpenseRequest` requires `reason`, 3 to 255 characters. `VoidExpense::execute` runs in a transaction, locks the row and then:

1. Rejects the request if the expense is already voided ("This expense is already voided.", shown on the reason field).
2. Sets `voided_at` = now, `voided_by` = the user, and `void_reason` = the reason.
3. Writes an `expense_voided` activity entry.

Effects:

- A voided expense stays in the month's list, struck through, with its reason shown.
- It is excluded from the summary totals, the category breakdown and the cash book.
- Any past month can be voided. There is no period lock.

To correct a mistake, void the expense with a reason and record a new one. An expense cannot be edited.

### Expenses list

The list shows expenses whose `spent_on` falls in the selected month, newest first (`spent_on`, then `id`), 20 per page. Each row shows date (`Y-m-d`), category, payee, note, amount and status (Active, or Voided with the reason).

## Monthly summary

`AccountsController::index` calls `BuildAccountsSummary::forMonth`, which calls `forRange` from the first to the last day of the month.

| Figure | Formula |
|---|---|
| Fee income | Sum of `payments.amount` where `status` = `completed` and `paid_at` is between 00:00:00 on the first day and 23:59:59 on the last day |
| Expenses | Sum of `expenses.amount` where `voided_at` is null and `spent_on` is in the month |
| Net | Fee income minus expenses. Shown in red when negative. |
| Expense by category | Active expenses grouped by `expense_category_id`, summed, sorted by total (largest first). Each row shows the category label. |

Notes:

- Fee income includes every payment method (cash, bKash, Nagad, Rocket, card, bank transfer). There is no split.
- Income is counted when received. Outstanding dues are not part of the summary (they are on the Fees screen).
- The summary has no breakdown by fee head.
- Dates are read in the application timezone.

## Daily cash book

`AccountsController::cashBook` takes `date` (default today). The page is a standalone HTML document outside the application layout.

| Line | Rule |
|---|---|
| Receipts | Completed payments with `paid_at` on that date, ordered by time. All payment methods. |
| Total in | Sum of those payments |
| Expenses | Active (not voided) expenses with `spent_on` equal to that date, ordered by id |
| Total out | Sum of those expenses |
| Day balance | Total in minus total out |

The page has a date picker and a Back link to the summary. The Print button calls `window.print()`, and print CSS hides the toolbar. The page is for one day only. The day balance is not a running cash balance: nothing carries forward from earlier days and there is no opening balance.

## Reports and exports

| Output | Where | Format |
|---|---|---|
| Monthly summary | `/accounts` | HTML |
| Expenses for a month | `/accounts/expenses` | HTML |
| Daily cash book | `/accounts/cash-book` | HTML, browser print |

There is no CSV, Excel or PDF export and no reports page. The `view-financial-reports` permission is granted in `RoleAndPermissionSeeder` but no route uses it.

## Activity log

Both entries use `spatie/laravel-activitylog`. No screen reads them.

| Event | Subject | Properties |
|---|---|---|
| `expense_recorded` | Expense | `amount_poisha`, `payee` |
| `expense_voided` | Expense | `amount_poisha`, `reason` |

## Testing

Feature tests: `tests/Feature/AccountsTest.php` (the summary is exercised at about line 242). Run with:

```bash
php artisan test tests/Feature/AccountsTest.php
```

See [Testing](testing.md) for the wider setup.

## Limitations

- Income is cash received from fees, not accrual. Receivables and dues are not in the summary.
- The cash book and the summary count non-cash methods (bKash, Nagad, Rocket, card, bank transfer) in income. There is no cash-only figure.
- The cash book's day balance is not a running balance and has no opening balance.
- Voiding has no period lock and no approval step. Any past month can be changed.
- Expenses cannot be edited, cannot be future-dated, and have no attachments, recurring entries or approval workflow.
- Categories cannot be renamed, deactivated or deleted. No default categories are seeded.
- The summary has no breakdown by fee head or payment method.
- No export or reports page. `view-financial-reports` is defined but unused.
- Some error messages are hard-coded English, for example "Expense amount must be positive." and "This expense is already voided." See [Localization](localization.md).
- The cash book page loads a stylesheet from fonts.googleapis.com, so the viewer's browser contacts Google when it opens. See [Security and privacy](security-and-privacy.md).
- The planning docs may describe accounts features that are not built. This page follows the code.
