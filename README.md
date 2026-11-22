# KCAM

**Kindergarten Cloud Academic Management**: Bangla-first school management for small Bangladeshi kindergartens and primary schools (50 to 200 students). Your whole school in your phone.

Most small schools still run on paper: handwritten registers, cash receipt books, notebook dues. KCAM gives the owner, teachers, accountants and parents one place for students, attendance, fees, exams and notices.

## Features

| Module | Status |
|---|---|
| Authentication, school registration, roles and permissions | Done |
| Student admission and records | Done |
| Attendance | Done |
| Fees: monthly invoices, discounts, collection, numbered receipts | Done |
| Exams (listing) | Basic |
| Notices with SMS | Basic |
| Parent portal, payroll, transport, inventory, accounts | Planned |

Full scope: [docs/idea.md](docs/idea.md). Architecture, schema and sprint plan: [docs/dev-plan.md](docs/dev-plan.md).

## Tech stack

Laravel 13, PHP 8.4, MySQL 8, Livewire 3, Alpine.js, Tailwind CSS 4, Vite, Pest 4, spatie/laravel-permission, spatie/laravel-activitylog, Laravel Pint, Larastan.

## Deployment model

KCAM is sold as a package: **one install per school**, configured for that school (`KCAM_MODE=single`, the default). The school is resolved automatically and public registration is closed. Create it once with `php artisan kcam:install`. Switch modules on or off per customer in `config/kcam.php` (env `KCAM_MODULE_EXAMS=false`, and so on). A shared hosted install is still possible with `KCAM_MODE=multi`.

## Design principles

- **Tenant isolation by school.** Every tenant table carries `school_id`, enforced by a global scope that fails closed. Outside HTTP (jobs, seeders) work runs inside `CurrentSchool::runAs()`.
- **Money is integer poisha.** Never floats. Use `App\Support\Money`; percent discounts are basis points.
- **Trustworthy receipts.** Gapless serials from a locked sequence, and idempotent collection via a client UUID.
- **Secure by default.** Per-route `can:` authorization, Form Request validation with input normalization (Bangla digits, canonical mobile numbers), escaped output, security headers, rate-limited auth.

## Getting started

Requirements: PHP 8.4, Composer, Node 20+, Docker (for MySQL).

```bash
git clone git@github.com:Kryton-System/kcam.git
cd kcam
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Start MySQL 8 (any MySQL 8 server works; adjust `.env` to match):

```bash
docker run -d --name kcam-mysql -p 127.0.0.1:3317:3306 \
  -e MYSQL_ROOT_PASSWORD=change-me -e MYSQL_USER=kcam -e MYSQL_PASSWORD=change-me \
  -e MYSQL_DATABASE=kcam -v kcam-mysql-data:/var/lib/mysql mysql:8.0
```

Create the test database `kcam_test` and grant the `kcam` user access to it, set `DB_PORT`, `DB_USERNAME` and `DB_PASSWORD` in `.env`, then:

```bash
php artisan migrate
php artisan kcam:install      # creates the school and owner (single mode)
composer run dev

# local demo data instead: php artisan db:seed
```

The demo seeder only runs in `local` and `testing` and creates a demo school with sample accounts (shortcut buttons appear on the local login page). It is not yet safe to run twice.

## Testing and quality

```bash
php artisan test --compact        # Pest suite, runs against the kcam_test MySQL database
vendor/bin/pint --format agent    # code style
```

`phpunit.xml` forces `DB_DATABASE=kcam_test`, so tests never touch your dev data. The project targets 80%+ coverage and test-first development.

## Project layout

```
app/Domain/        Business logic by module (School, Student, Fees, Attendance ...)
app/Http/          Controllers, Form Requests, middleware
app/Support/       Tenancy context, Money, enums, input helpers
database/          Migrations, factories, seeders
resources/views/   Blade views and components
tests/Feature/     Authorization, tenancy, validation, money integrity tests
docs/              Idea, development plan, QA plan, decisions
```

## Roadmap

Open items are tracked in section 13 of [docs/dev-plan.md](docs/dev-plan.md): CI workflow, owner email or mobile verification, CSP nonce, Larastan config, guardian pivot, payment allocations, attendance sessions and a parallel row-lock test.

## Contributing

Private project of Kryton System. Use Conventional Commits (`feat`, `fix`, `docs` ...), write a failing test first, and run Pint and the test suite before opening a pull request.

## License

Proprietary. All rights reserved.
