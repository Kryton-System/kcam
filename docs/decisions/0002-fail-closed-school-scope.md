# 0002. Fail-closed school scope

**Status:** Accepted

## Context

Every school's data lives in the same tables, marked by `school_id`. One query without a school condition would show one school's students, invoices or receipts to another school. Developers add queries every week, and some code runs without a logged-in user: queued jobs, console commands, webhooks and the demo seeder. The school must never be guessed for those.

## Decision

- Tenant models use the `BelongsToSchool` trait. It adds a global scope named `school` that filters every query by `CurrentSchool::id()`.
- When no school is known and the request is not platform work, the scope throws `MissingSchoolContext` instead of returning every school's rows. The exception message names the two ways out: authenticate a school user, or wrap the work in `CurrentSchool::runAs()`.
- On create, the trait fills `school_id` from `CurrentSchool::id()` when the attribute is empty.
- Work outside HTTP sets the school with `CurrentSchool::runAs($schoolId, Closure)`. Cross-school work must call `CurrentSchool::runAsPlatform(Closure)`. The call is greppable on purpose. `DemoSchoolSeeder` is the only seeder that uses it, and two feature tests do too.
- Inside actions, `withoutGlobalScope('school')` is used only where the action filters by a `school_id` it has already resolved, for example `CollectFeeAction` (from the locked invoice), `GenerateMonthlyInvoicesAction`, `AdmitStudentAction` and `RecordAttendanceAction`.

Code evidence:

- `app/Support/Traits/BelongsToSchool.php` (global scope, `creating` hook, `scopeForSchool`)
- `app/Support/Tenancy/CurrentSchool.php` (`id`, `runAs`, `runAsPlatform`, `isPlatform`)
- `app/Support/Tenancy/MissingSchoolContext.php`
- `app/Domain/Fees/Actions/CollectFeeAction.php` (`runAs` around the transaction)
- `database/seeders/DemoSchoolSeeder.php` (`runAsPlatform`)
- `tests/Feature/TenantIsolationTest.php`, `tests/Feature/TenantContextTest.php` and `tests/Feature/InputValidationTest.php` (the last two use `runAsPlatform`)

## Consequences

- A missing school is a loud error, not a silent data leak. Tests cover cross-school reads and automatic `school_id` assignment.
- Developers working outside HTTP must wrap the work in `runAs()`. Forgetting it produces `MissingSchoolContext`, which is intended.
- Every `withoutGlobalScope('school')` call is an exception to the rule and needs a review.
- Model `App\Models\User` is not scoped by the trait. Login looks users up by email or mobile across schools, which is intended because both are unique for the install.
- The school scope does not replace authorization. Permissions (`can:` middleware) still decide what a user may do inside their school (see [Roles and permissions](../roles-and-permissions.md)).
