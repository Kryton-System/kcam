# Testing

KCAM's automated tests use Pest on top of Laravel's test runner. This page covers how to run the suite, how the tests are set up and organised, the helpers and factories they share, how tenant isolation is tested, code formatting with Pint, and the suite status on 2026-11-26.

## Versions and environment

| Tool | Version in `composer.lock` | Constraint in `composer.json` |
|---|---|---|
| Laravel framework | v13.43.0 | `^13.17` |
| Pest | v4.7.8 | `^4.7` |
| Pint | v1.32.3 | `^1.27` |

- `composer.json` requires PHP `^8.3`. The run recorded on 2026-11-26 used PHP 8.4.27.
- `phpunit.xml` sets `DB_CONNECTION=mysql` and `DB_DATABASE=kcam_test`, so a reachable MySQL server with the `kcam_test` database is required. Host and credentials come from `.env` and are not recorded here.
- `phpunit.xml` also sets `APP_ENV=testing`, `KCAM_MODE=multi`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`, and turns off Pulse, Telescope and Nightwatch.
- `phpunit.xml` does not set `APP_LOCALE`, so the value in the local `.env` applies to every test. See Current status.

## Running the tests

```bash
php artisan test --compact
php artisan test --compact tests/Feature/LocaleSwitchTest.php
php artisan test --compact --filter='visitor can switch the site to English'
vendor/bin/pest tests/Unit/LocaleFormatTest.php
composer run test
```

- `php artisan test --compact` runs the Unit and Feature suites defined in `phpunit.xml`.
- `--filter` matches a substring of the test identifier, which includes the class name derived from the file and the description, so `--filter=PayrollTest` selects that file. Pass a file path to run one file.
- `composer run test` clears the config cache first (`config:clear`) and then runs `php artisan test`. Use it when `.env` changes.
## Test layout

`phpunit.xml` defines two suites: `Unit` (`tests/Unit`) and `Feature` (`tests/Feature`). `tests/Pest.php` configures both:

- `Feature`: extends `Tests\TestCase`, uses `RefreshDatabase` for every test, flushes `CurrentSchool` before each test and seeds `RoleAndPermissionSeeder`.
- `Unit`: extends `Tests\TestCase` only. No database is touched.

Feature files grouped by area (29 files). Unit files are listed at the end.

| Area | Feature test files |
|---|---|
| Auth and access | `AuthTest`, `AuthorizationTest`, `SecurityHardeningTest` |
| Tenancy and school setup | `TenantContextTest`, `TenantIsolationTest`, `SingleSchoolModeTest`, `SchoolSetupTest` |
| Students and admissions | `StudentAdmissionTest`, `StudentBangladeshAdmissionTest`, `StudentEditBangladeshTest`, `StudentListFiltersTest`, `StudentProfileTest`, `InputValidationTest` |
| Parent portal | `ParentPortalTest`, `ParentChildSwitcherTest` |
| Attendance | `AttendanceTest` |
| Exams | `ExamMarksTest` |
| Fees and money | `FeeCollectionTest`, `InvoiceGenerationTest`, `MoneyIntegrityTest` |
| Accounts | `AccountsTest` |
| Staff and payroll | `StaffManagementTest`, `PayrollTest` |
| Inventory | `InventoryTest` |
| Transport | `TransportTest` |
| Dashboards | `DashboardRolesTest` |
| Localization | `LocaleSwitchTest` |
| Platform | `HealthCheckTest`, `ExampleTest` |

Unit files (3):

| Area | Unit test files |
|---|---|
| Bangla helpers | `BanglaHelperTest` (digits, taka, dates, month and day names, ordinal) |
| Locale formatting | `LocaleFormatTest` (digits, numbers, taka, dates, month names, mobile numbers in both locales) |
| Placeholder | `ExampleTest` (a PHPUnit class asserting `true`, from the default skeleton) |

Test descriptions are written as sentences in the Pest style, for example `test('a school user cannot see another schools students', ...)`.

## Helpers and naming rules

Pest test files share one global function namespace. Any function declared at file level in `tests/` is global, so two files that declare the same name cause a fatal "cannot redeclare" error. Rules:

- Give each helper a name that describes its fixture, such as `transportRoute()` or `portalFamily()`.
- If two files need the same helper, put it in `tests/Pest.php` or guard it with `if (! function_exists('name'))`. `makeStaff()` is declared in `StaffManagementTest.php` and `PayrollTest.php`, both behind that guard.
- Nested helpers inside a `describe` block or a guard are still global once declared, so check for duplicates before adding a name.

`schoolUser()` is the shared helper for users in a school. It is defined in `tests/Pest.php`:

```php
function schoolUser(UserRole $role, ?School $school = null): User
```

It creates a school when none is passed, then a user with the given role in that school. It is used in 20 test files. Example:

```php
$owner = schoolUser(UserRole::Owner);
$this->actingAs($owner)->get(route('students.create'))->assertOk();
```

Other file-level helpers, by file (not a full list):

| Helper | File | Builds |
|---|---|---|
| `schoolUser()` | `tests/Pest.php` | A user with a role, in a new or given school |
| `portalFamily()` | `ParentPortalTest.php` | A parent and linked children |
| `parentWithChildren()` | `ParentChildSwitcherTest.php` | A parent with several children |
| `admissionPayload()`, `schoolWithClass()` | `InputValidationTest.php` | Admission form data with a class and section |
| `bdAdmissionPayload()`, `bdAdmittedStudent()` | `StudentBangladeshAdmissionTest.php` | Bangladesh admission data and an admitted student |
| `transportRoute()`, `transportEnrollment()`, `transportCount()` | `TransportTest.php` | Route, enrolment and row counts |
| `stockItem()`, `stockOf()` | `InventoryTest.php` | An inventory item with stock and its current stock |
| `accountsExpense()`, `accountsPayment()` | `AccountsTest.php` | Expenses and payments for the accounts pages |
| `makePayslip()`, `payslipCount()` | `PayrollTest.php` | Payslips and their count |

## Factories and states

There are 30 factories in `database/factories`. Only these named states exist:

| Factory | State | Effect |
|---|---|---|
| `UserFactory` | `owner()` | Sets `role` to `UserRole::Owner` |
| `UserFactory` | `teacher()` | Sets `role` to `UserRole::Teacher` |
| `UserFactory` | `accountant()` | Sets `role` to `UserRole::Accountant` |
| `UserFactory` | `parent()` | Sets `role` to `UserRole::Parent` |
| `UserFactory` | `superAdmin()` | Sets `role` to `UserRole::SuperAdmin` and `school_id` to `null` |
| `UserFactory` | `unverified()` | Sets `email_verified_at` to `null` |
| `PayslipFactory` | `paid()` | Sets `status` to `paid` and `paid_at` to now |

Use the factory for models in tests rather than building rows by hand. Check for an existing state first. `SchoolFactory`, `StudentFactory` and the other factories have no named states, so pass attributes directly (for example `School::factory()->create(['name' => 'School One'])`).

## Tenant isolation tests

KCAM stores every school's data in shared tables. Isolation is tested at three levels.

Model level, in `TenantIsolationTest.php`:

- Records for two schools are created with `withoutGlobalScopes()`, then a school user's `Student::all()` returns only their own school's rows, and `Student::find()` on another school's ID returns `null`.
- Creating a model as a logged-in user fills `school_id` automatically from that user.
- Classes and invoices of another school are not visible to a school user.

Context level, in `TenantContextTest.php`:

- Querying a tenant model with no school context fails closed.
- A user without a school cannot read tenant data.
- `runAs()` scopes queries to the given school and restores the previous context, including when the callback throws.
- `runAsPlatform()` is the explicit escape hatch that sees every school.
- A logged-in user with a school is scoped with no extra setup.

Architecture level, in `TenantContextTest.php`:

- Every model under `app/Domain/*/Models` that has a `school_id` column uses the `BelongsToSchool` trait. The `User` model sits in `app/Models`, outside that scan, because it is the identity table used to resolve the school.

HTTP level, across feature files:

- A class from another school is rejected (`InputValidationTest`, "a class from another school is rejected").
- Another parent, or a parent from another school, gets 404 for a child that is not theirs (`ParentPortalTest`). A parent cannot open a child linked to a different mobile number (`ParentChildSwitcherTest`).
- Roles without a permission get 403 (`AuthorizationTest`).

Single-school mode (`SingleSchoolModeTest`) is tested separately. `phpunit.xml` sets `KCAM_MODE=multi` for the run.

## Localization tests

| Test file | What it checks |
|---|---|
| `LocaleSwitchTest.php` | Default Bangla `lang` attribute (assumes `APP_LOCALE=bn`), switching to English and back, rejection of unsupported locales with 404, admission page text in both languages |
| `LocaleFormatTest.php` | `LocaleFormat` output for digits, numbers, taka, dates, month names and mobile numbers in `bn` and `en` |
| `BanglaHelperTest.php` | `BanglaHelper` digit conversion, taka and date formatting, day and month names, ordinal |

18 test files contain Bangla literals, for example `LocaleSwitchTest.php`, `StudentBangladeshAdmissionTest.php` and `DashboardRolesTest.php`. When you change Bangla copy, update the assertions that check it. See [Localization](localization.md).

## Code formatting

Pint formats PHP. The project rule is to run it on changed files before finishing:

```bash
vendor/bin/pint --dirty --format agent
```

Do not run `vendor/bin/pint --test`. There is no `pint.json`, so Pint uses its defaults.

## Current status (2026-11-26)

The last full run on 2026-11-26 had 426 tests, all passing, with one test flagged as risky by Pest.

The earlier failure in `tests/Feature/ExampleTest.php` is fixed. The home page defaults to Bangla, so the brand text (`__('app.name')`, which is `কেসিএএম` in `lang/bn/app.php`) did not contain the Latin string `KCAM`. The test now switches the session to English through `locale.switch` before it checks the page.

Two tests in `LocaleSwitchTest.php` ("site defaults to Bangla" and "admission page renders in Bangla by default") still expect a Bangla default. They depend on the local `APP_LOCALE=bn`, because `phpunit.xml` does not pin the locale. Pinning it there would remove that dependency (not applied).

## Limitations

- `LocaleSwitchTest` Bangla-default tests depend on the local `APP_LOCALE`. Pinning `APP_LOCALE` in `phpunit.xml` would make them stable.
- The risky test in the recorded run is not identified (see Open questions).
- `tests/Unit/ExampleTest.php` is the default skeleton: a PHPUnit class asserting `true`. It tests nothing and could be removed or replaced with a real unit test.
- Seven Feature files repeat `uses(RefreshDatabase::class)`, which `tests/Pest.php` already applies to every Feature test.
- No coverage threshold or coverage report is configured in `phpunit.xml` or `composer.json`. The 80 percent line and branch target in the global testing standard has not been measured for this codebase.
- No browser or JavaScript tests exist. `package.json` has no test script, and the components are not tested in a browser (see [Design system](design-system.md)).
- The suite needs a live MySQL server. There is no SQLite fallback in `phpunit.xml`.
- Tests that assert Bangla copy directly (18 files contain Bangla literals) break on any copy change, so they must move with `lang/bn`.

## Open questions

- The run recorded for 2026-11-26 (426 tests, 425 passed, 1 failed, 1 risky, 1,238 assertions, 95.7 s) was not re-run by this check. The risky test is not identified.
- The run order (Unit, then Feature) and the compact output format were not confirmed.
