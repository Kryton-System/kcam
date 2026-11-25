# KCAM docs

## Index

| File | Purpose |
|---|---|
| idea.md | Product idea and scope |
| dev-plan.md | Stack, architecture, schema, sprints, security rules, catch-up status (section 13) |
| ui-ux-ai-qa-plan.md | UI/UX, AI and QA gates |
| decisions/ | Architecture decision records |
| agents/ | Agent notes (folder exists but is empty; git does not track empty folders) |
| getting-started.md | Requirements, first-time setup, environment keys, commands, tests, troubleshooting |
| architecture.md | Directory layout, domain modules, request lifecycle, route groups and gates, tenancy, module switches |
| database.md | Table groups, money in poisha, soft deletes, unique keys, receipt numbering, adding a migration |
| deployment.md | Production checklist, environment values, deploy steps, private uploads, backups, HTTPS |
| decisions/0001-money-as-integer-poisha.md | ADR: money stored as integer poisha |
| decisions/0002-fail-closed-school-scope.md | ADR: school scope that fails closed |
| decisions/0003-single-school-install-mode.md | ADR: one school per install by default |
| decisions/0004-session-locale-bangla-default.md | ADR: session language, Bangla as the install default |
| decisions/0005-private-student-documents.md | ADR: student photos and NID scans stored privately |

## Local setup

1. Start the database container: `docker start kcam-mysql` (MySQL 8.0 on 127.0.0.1:3317).
2. Databases: `kcam` for dev, `kcam_test` for tests (forced in `phpunit.xml`). Credentials live only in `.env`.
3. `php artisan migrate` then `php artisan db:seed` (the demo seeder is not yet safe to re-run).
4. Run tests: `php artisan test --compact`. Format: `vendor/bin/pint --format agent`.

## Deployment model

One install per school (`KCAM_MODE=single`). Set up with `php artisan kcam:install`; modules toggled in `config/kcam.php`. `KCAM_MODE=multi` keeps the hosted multi-school behavior (tests run in this mode).

## Conventions

- Tenancy: `school_id` global scope fails closed; use `CurrentSchool::runAs` outside HTTP.
- Money: integer poisha via `App\Support\Money`; percent discounts in basis points.
- Input: Form Requests with `NormalizesInput`; search via `Search::contains`.
- Style: no em or en dashes, TDD, 80%+ coverage.

## Code navigation

CodeGraph index lives in `.codegraph/` (local only). Rebuild with `codegraph init -y` or `codegraph sync`.

## Product and module docs

Each page covers one product area or cross-cutting concern: what it does, the routes and permissions it uses, its business rules, and its known gaps.

- [Roles and permissions](roles-and-permissions.md): roles, the permission matrix, and which routes each permission guards
- [Security and privacy](security-and-privacy.md): authentication, rate limits, headers, personal data handling
- [Dashboards](dashboards.md): the role-based home pages and their figures
- [Students](students.md): admission, profiles, guardians, photos and NID scans
- [Portal](portal.md): the read-only parent portal
- [Fees and payments](fees-and-payments.md): fee structures, invoices, discounts, collection and receipts
- [Accounts](accounts.md): expenses, the monthly summary and the daily cash book
- [Attendance](attendance.md): daily attendance and offline sync
- [Exams](exams.md): exams and mark entry
- [Notices](notices.md): notices to audiences, and the SMS status
- [Staff and payroll](staff-and-payroll.md): staff records, payslips and payment
- [Transport](transport.md): vehicles, routes and student assignments
- [Inventory](inventory.md): items, stock movements and low stock
- [Localization](localization.md): English and Bangla, language switching and formatting
- [Design system](design-system.md): components, tokens and layout rules
- [Testing](testing.md): the test suite, its database and its conventions
