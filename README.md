# KCAM

**Kindergarten Cloud Academic Management**: a Bangla-first school management app for small kindergartens and primary schools in Bangladesh.

Most small schools still keep registers, receipt books and fee dues in notebooks. KCAM puts students, attendance, fees, exams, notices and staff pay in one place, for the owner, teachers, accountants and parents. The interface opens in Bangla and switches to English.

> Private project of Kryton System. See [License](#license).

## Features

| Area | What it does | Status |
|---|---|---|
| Accounts and roles | Sign-in, owner, accountant, teacher and parent roles, checked on every route. Public school sign-up is closed in single-school mode | Available |
| Students | Admission with BDRIS birth registration number, student photo and guardian NID scans, edit, search, filters, profile | Available |
| Attendance | Daily attendance by class and section | Available |
| Fees and payments | Monthly invoices, discounts, collection with duplicate protection, numbered receipts | Available. Invoice generation has no screen or schedule yet |
| Exams | Exam list and marks entry | Basic |
| Notices | Create and show notices in Bangla or English | Available. SMS is not connected |
| Parent portal | Parents see their linked children, with a switcher for siblings | Available. Mobile layout is still being checked |
| Accounts | Expenses and cash book | Available |
| Staff and payroll | Staff records and payslip generation | Available |
| Transport | Routes, vehicles and student assignments with capacity checks | Available |
| Inventory | Items and stock movements | Available |
| Dashboards | Owner, accountant and teacher dashboards built from real records | Available. Super admin dashboard not built |

## Quick start

Requirements:

- PHP 8.3 or newer (the project is developed on PHP 8.4)
- Composer
- Node.js 20.19 or newer, or 22.12 or newer (Vite 8 needs this)
- MySQL 8.0

```bash
git clone https://github.com/Kryton-System/kcam.git
cd kcam
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Set the `DB_*` values in `.env`, then create two MySQL databases: `kcam` for development and `kcam_test` for the test suite (`phpunit.xml` forces the test database). Then:

```bash
php artisan migrate
php artisan kcam:install   # creates the school and its owner account (single-school mode)
npm run dev                # Vite dev server; use "npm run build" for a one-off build
php artisan serve          # second terminal, then open http://127.0.0.1:8000
```

Demo data for local work: `php artisan db:seed`. The seeder runs only in the `local` and `testing` environments and is not yet safe to run twice.

The full setup, Docker example and troubleshooting steps are in [docs/getting-started.md](docs/getting-started.md).

## Configuration

| Variable | Purpose | Default |
|---|---|---|
| `APP_LOCALE` | Default language, `bn` (Bangla) or `en`. The example file ships `en`, so set `bn` for a Bangla-first install | `en` |
| `APP_URL` | Public address of the install | none |
| `DB_*` | MySQL connection | none |
| `KCAM_MODE` | `single` (one school per install) or `multi` (hosted, many schools) | `single` |
| `KCAM_MODULE_*` | Switch a module off, for example `KCAM_MODULE_EXAMS=false`. Modules: STUDENTS, ATTENDANCE, FEES, EXAMS, NOTICES, PORTAL, ACCOUNTS, INVENTORY, PAYROLL, TRANSPORT | `true` |

Never commit `.env`. Production settings are covered in [docs/deployment.md](docs/deployment.md).

## Testing and code style

```bash
php artisan test --compact        # Pest suite on the kcam_test MySQL database
vendor/bin/pint --format agent    # code style (Laravel Pint)
```

At the last full run the suite had 426 tests, all passing.

## Documentation

Start with the [documentation index](docs/README.md). The guides cover:

- Setup and architecture: [getting started](docs/getting-started.md), [architecture](docs/architecture.md), [database](docs/database.md), [deployment](docs/deployment.md)
- Access and security: [roles and permissions](docs/roles-and-permissions.md), [security and privacy](docs/security-and-privacy.md), [dashboards](docs/dashboards.md)
- Modules: [students](docs/students.md), [parent portal](docs/portal.md), [fees and payments](docs/fees-and-payments.md), [accounts](docs/accounts.md), [attendance](docs/attendance.md), [exams](docs/exams.md), [notices](docs/notices.md), [staff and payroll](docs/staff-and-payroll.md), [transport](docs/transport.md), [inventory](docs/inventory.md)
- Front end and quality: [localization](docs/localization.md), [design system](docs/design-system.md), [testing](docs/testing.md)
- Decisions: [decision records](docs/decisions/)

## Design principles

- **Tenant isolation by school.** Every tenant table has a `school_id`, enforced by a global scope that fails closed. Work outside HTTP runs inside `CurrentSchool::runAs()`. See [decision 0002](docs/decisions/0002-fail-closed-school-scope.md).
- **Money is whole poisha.** Amounts are stored as integers, never floats. Percent discounts use basis points. See [decision 0001](docs/decisions/0001-money-as-integer-poisha.md).
- **Trustworthy receipts.** Receipt serials are issued under a row lock, and collection is idempotent through a client UUID.
- **Private student documents.** Photos and guardian NID scans are stored outside the public folder and served only through authorized routes. See [decision 0005](docs/decisions/0005-private-student-documents.md).
- **Secure by default.** Per-route authorization, Form Request validation with input normalization (Bangla digits, canonical mobile numbers), security headers, and throttled sign-in.

## Known limitations

- SMS is not connected. The `sms_logs` table exists, but nothing sends messages yet.
- The super admin dashboard and an activity feed are not built.
- The parent sign-in flow (OTP or first-time setup) is not built yet.
- Some validation and inventory messages still show in English on Bangla pages.
- Fee structure and monthly invoice generation have no screen yet.

Planning notes live in [docs/roadmap.md](docs/roadmap.md), [docs/dev-plan.md](docs/dev-plan.md) and [docs/idea.md](docs/idea.md).

## Project layout

```
app/Domain/        Business logic by module (School, Student, Fees, Attendance ...)
app/Http/          Controllers, Form Requests, middleware
app/Support/       Tenancy context, Money, enums, helpers
database/          Migrations, factories, seeders
resources/views/   Blade views and components
resources/css/     Tailwind CSS theme tokens
lang/              Bangla (bn) and English (en) strings
tests/             Feature and unit tests (Pest)
docs/              Guides, decision records and planning notes
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Report security problems privately as described in [SECURITY.md](SECURITY.md).

## License

Proprietary. All rights reserved. Private project of Kryton System.
