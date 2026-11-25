# Getting started

This page takes a developer from a fresh clone to a running KCAM install on a local machine. It lists the requirements, the setup steps in order, the environment keys that matter, the commands defined in `composer.json` and `package.json`, how to run the tests, and common fixes. For production, read [Deployment](deployment.md). For the code layout, read [Architecture](architecture.md).

## Requirements

| Requirement | Version | Where it is defined |
|---|---|---|
| PHP | `^8.3` in `composer.json`. The project targets PHP 8.4 (root README). | `composer.json` (`require.php`) |
| Composer | 2.x | |
| Laravel | 13. `^13.17` in `composer.json`, `v13.43.0` in `composer.lock`. | `composer.json`, `composer.lock` |
| Node.js | `^20.19.0 \|\| >=22.12.0` (required by Vite 8) | `vite` in `package.json` (`^8.0.0`) |
| MySQL | 8 (target). Connection charset `utf8mb4`, collation `utf8mb4_unicode_ci`. | `config/database.php` |
| Docker | Optional. Used only for the local MySQL container in `run.sh` and the root README. | `run.sh` |

The root README says "Node 20+". Vite 8 does not run on Node 20.0 to 20.18, so use 20.19 or newer, or 22.12 or newer.

## First-time setup

Run these from the project root, in this order:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Set the `DB_*` values in `.env` (see [Environment keys](#environment-keys)). Then create the two databases and grant the application user access to both. Use the same user name as `DB_USERNAME`:

```sql
CREATE DATABASE kcam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE kcam_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON kcam.* TO 'kcam'@'%';
GRANT ALL PRIVILEGES ON kcam_test.* TO 'kcam'@'%';
```

Restrict the host part (`'%'`) in any environment other than a throwaway container. Then migrate and create the school:

```bash
php artisan migrate
php artisan kcam:install
composer run dev
```

`php artisan kcam:install` prompts for the school name, the school phone (`01XXXXXXXXX`), the owner name, the owner email and the owner password. Each value also has an option (`--name`, `--name-bn`, `--phone`, `--address`, `--owner-name`, `--owner-email`, `--owner-password`). Prefer the password prompt: a password passed as an option stays in your shell history.

The command refuses to run when a school already exists, so it runs once per install. It first runs `RoleAndPermissionSeeder` outside any transaction. Then `SetupSchoolAction` (`app/Domain/School/Actions/SetupSchoolAction.php`) creates, in one transaction:

- the school profile with BDT currency and the `Asia/Dhaka` value stored in its settings,
- the owner account with role `owner`,
- the current academic year (1 January to 31 December of the current year),
- eight standard classes (Play to Class 5), each with sections A and B,
- three default fee heads: Monthly Tuition, Exam Fee and Session Fee.

### Sign in

Sign in with the owner email, or with the owner mobile number in `01XXXXXXXXX` form. `kcam:install` does not ask for a separate owner mobile, so `SetupSchoolAction` uses the school phone number. The login form also accepts Bangla digits and a `+88` prefix, because `AuthenticatedSessionController::store` normalizes the login value with `NormalizesInputHelper::canonicalMobile` before lookup (the same rule as `canonicalMobile` in `app/Http/Requests/Concerns/NormalizesInput.php`).

### Demo data (local and testing only)

```bash
php artisan db:seed
```

`DatabaseSeeder` runs `RoleAndPermissionSeeder` and then `DemoSchoolSeeder`. The demo seeder creates a demo school with one account per role (owner, teacher, accountant, parent), five sample students and sample invoices and receipts. It only runs when `APP_ENV` is `local` or `testing`. Otherwise it logs a warning and returns. The demo accounts share one password, which is defined in `database/seeders/DemoSchoolSeeder.php`.

Do not run the demo seeder on a database where `kcam:install` already created a school. The seeder always adds a school, so you end up with two schools. In single mode the school lookup then returns nothing and fails closed. The seeder is also not safe to run twice: the second run adds another school row and then fails on the duplicate user email. Recreate the local `kcam` database before seeding again.

## Environment keys

Only names and purposes are listed. Copy `.env.example` to `.env` and set your own values. Never commit `.env` (it is in `.gitignore`).

| Key | Purpose | Notes |
|---|---|---|
| `APP_ENV` | Environment: `local`, `testing` or `production`. | Controls the demo seeder and the CSP mode (report-only only in `local`). The breached password rule applies only in production. Lazy loading is blocked outside production. |
| `APP_DEBUG` | Shows stack traces in error pages. | `.env.example` sets it to true. It must be false in production. |
| `APP_URL` | Base URL for absolute links. | `.env.example` uses `http://localhost:8000`. |
| `APP_KEY` | Encryption key for cookies and sessions. | Set by `php artisan key:generate`. Changing it logs everyone out. |
| `APP_LOCALE` | Default language for guests and new sessions. | Config default `en`. Bangla-first installs set `bn`. See [Localization](localization.md). |
| `APP_FALLBACK_LOCALE` | Language used when a key is missing. | `en` |
| `APP_PREVIOUS_KEYS` | Old keys kept for rotation. | `config/app.php` |
| `KCAM_MODE` | `single` (one school per install) or `multi` (hosted, many schools). | Default `single` (`config/kcam.php`). |
| `KCAM_MODULE_STUDENTS`, `_ATTENDANCE`, `_FEES`, `_EXAMS`, `_NOTICES`, `_PORTAL`, `_ACCOUNTS`, `_INVENTORY`, `_PAYROLL`, `_TRANSPORT` | Switch a module on or off. | Default on. These keys are not in `.env.example`. |
| `DB_CONNECTION` | Database driver. | `mysql` in `.env.example`. The config default is `sqlite` when unset. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection. | `DB_PORT` is `3306` in `.env.example`. The root README container maps host port `3317`. |
| `DB_CHARSET`, `DB_COLLATION` | Connection charset and collation. | Defaults `utf8mb4` and `utf8mb4_unicode_ci`. |
| `SESSION_DRIVER` | Where sessions live. | `database` in `.env.example`. Needs the `sessions` table. |
| `SESSION_LIFETIME` | Session lifetime in minutes. | `120` in `.env.example`. |
| `SESSION_SECURE_COOKIE` | Adds the `Secure` flag to the session cookie. | Not set in `.env.example`. Set `true` when served over HTTPS. |
| `SESSION_ENCRYPT` | Encrypts session payloads. | `false` in `.env.example`. |
| `CACHE_STORE` | Cache backend. | `database` (needs the `cache` and `cache_locks` tables). |
| `QUEUE_CONNECTION` | Queue backend. | `database` (needs the `jobs` table). No job classes exist yet. |
| `FILESYSTEM_DISK` | Default disk. | `local`, which is `storage/app/private`. Student photos and NID scans use it. See [Deployment](deployment.md#storage-and-private-uploads). |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL` | Logging. | `stack` with `single` (`storage/logs/laravel.log`) and `debug` in `.env.example`. |
| `MAIL_MAILER` | Mail transport. | `log` in `.env.example`. The code has no mailable classes. |

## Commands

These come from the `scripts` section of `composer.json` and `package.json`, plus `run.sh`.

| Command | What it does |
|---|---|
| `composer run setup` | `composer install`, copies `.env` if missing, `key:generate`, `migrate --force`, `npm install --ignore-scripts`, `npm run build`. It does not create a school, so run `kcam:install` afterwards. |
| `composer run dev` | Runs `php artisan dev`: `serve` (127.0.0.1:8000), `queue:listen`, `pail` (the log viewer from `laravel/pail`) and `npm run dev`. |
| `composer run test` | `config:clear`, then `php artisan test`. |
| `npm run dev` | Vite dev server only. |
| `npm run build` | Vite production build into `public/build`. |
| `./run.sh` | Starts the `kcam-mysql` container if Docker has it, runs `migrate --force`, then `composer run dev`. |
| `vendor/bin/pint --format agent` | Code style fixer (Laravel Pint). |
| `php artisan kcam:install` | One-time school setup. See above. |

## Running tests

```bash
php artisan test --compact
php artisan test --compact tests/Feature/FeeCollectionTest.php
php artisan test --compact --filter="cannot see another schools students"
```

`phpunit.xml` sets `APP_ENV=testing`, `KCAM_MODE=multi`, `DB_CONNECTION=mysql`, `DB_DATABASE=kcam_test`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync` and `MAIL_MAILER=array`. The tests therefore run in multi mode against `kcam_test`.

`LocaleSwitchTest` expects Bangla to be the default, but `phpunit.xml` does not set `APP_LOCALE`. Those tests pass only when your `.env` sets `APP_LOCALE=bn`.

Feature tests use `RefreshDatabase` (`tests/Pest.php`), so the test database is migrated and rebuilt. Never point a test run at the development database. Each feature test seeds `RoleAndPermissionSeeder` first.

The suite is split into `tests/Feature` (authorization, tenancy, money, validation, portal, modules) and `tests/Unit` (Bangla digits, date and locale helpers). See [Testing](testing.md) for the strategy. No coverage command is defined in `composer.json`, and the repository has no CI workflow (no `.github` folder).

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `Unable to locate file in Vite manifest` | `public/build` is missing. It is in `.gitignore`, so a fresh clone does not have it. | Run `npm run build`, or run `npm run dev` next to `php artisan serve`. |
| Saving a student photo or NID scan fails with a filesystem error | The PHP user cannot write to `storage/app/private`. | Make `storage` and `bootstrap/cache` writable by the web server user, for example `chmod -R ug+rwX storage bootstrap/cache`. |
| `No current school is set. ...` (`MissingSchoolContext`) | A tenant query ran with no user and no `CurrentSchool::runAs()`, or single mode found zero or two `schools` rows. | Run `kcam:install` on an empty database. In jobs, commands or webhooks, wrap the work in `CurrentSchool::runAs($schoolId, ...)`. |
| `This install already has a school.` | `kcam:install` ran before. | Expected. The command runs once per install. |
| SQLSTATE connection errors | Container not running, wrong `DB_PORT`, or wrong credentials. | `docker start kcam-mysql`, match `DB_PORT` to the host port, then check with `php artisan migrate:status`. `/health` returns 503 when the database is unreachable. |
| Tests fail with access denied on `kcam_test` | The user has no grant on `kcam_test`. | Run the `GRANT` statement above. |
| Changes in `.env` have no effect | Configuration is cached. | `php artisan config:clear` |
| Page stays in English | `APP_LOCALE` is `en`, or the session has `en`. | Use the language switcher, or set `APP_LOCALE=bn` and run `config:clear`. |
| Login says too many attempts | Five failed attempts per login key (`RateLimiter` in `AuthenticatedSessionController`). | Wait for the timeout shown on the page. |

## Limitations

- `.env.example` has no `KCAM_*` keys. Module and mode defaults come from `config/kcam.php`.
- `kcam:install` accepts `--owner-password` as an option, so the password can end up in shell history. The command itself says to prefer the prompt.
- `db:seed` and `kcam:install` both create a school. The project has no guard that stops running both on one database.
- The demo seeder is not idempotent (see above).
- The root README says Node 20+. That is too low for Vite 8 (see Requirements).
- The root README container example maps host port 3317, while `.env.example` uses 3306. The two must match.
- The repository has no CI workflow, no `phpstan.neon` (Larastan is installed but not configured) and no coverage command.
- `database/database.sqlite` exists in the working tree. It is ignored by git and not used by the MySQL setup.
