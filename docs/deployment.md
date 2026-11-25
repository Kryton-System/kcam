# Deployment

KCAM is sold as one install per school. Each install runs in `KCAM_MODE=single`, serves one school, and has its own database, private uploads and `.env`. This page is the production checklist, the deploy steps, and the operating notes for backups, storage, sessions and HTTPS. The repository has no deployment automation, no CI workflow and no scheduled tasks, so every step below is manual.

## Production checklist

| # | Check | How to verify |
|---|---|---|
| 1 | `APP_ENV=production`, `APP_DEBUG=false` | `php artisan about` shows the environment and debug mode |
| 2 | `APP_URL` is the final `https://` address | Login page links and redirects use HTTPS |
| 3 | `APP_KEY` is set once and backed up outside the server | `APP_KEY` is present in `.env`, and the copy is in your secret store |
| 4 | `KCAM_MODE=single` and the module switches match the purchased package | `php artisan config:show kcam` prints the mode and each module switch |
| 5 | `DB_CONNECTION=mysql` with a dedicated database user | The connection works and `php artisan migrate:status` shows every migration as Ran |
| 6 | `SESSION_SECURE_COOKIE=true` | The session cookie has the `Secure` flag in the browser |
| 7 | Document root is `public/` | `https://your-host/.env` returns 404, not the file |
| 8 | `public/build` exists (`npm run build` ran) | Pages load without a Vite manifest error |
| 9 | `storage/` and `bootstrap/cache/` are writable by the PHP user | Saving a student with a photo succeeds |
| 10 | `/health` returns 200 and `/up` returns 200 | `curl -s -o /dev/null -w "%{http_code}" https://your-host/health` |
| 11 | One owner account created with `kcam:install` | Sign in with the owner email |
| 12 | Backups scheduled and one restore tested | See [Database backups](#database-backups) |

`php artisan config:show kcam` prints the `kcam` configuration. `php artisan about` prints the environment summary.

## Environment values

Names and purposes only, with the value to use in production. Never commit `.env`, and never paste secrets into tickets or chat.

| Key | Production value | Why |
|---|---|---|
| `APP_ENV` | `production` | Enables the breached password rule, and makes the CSP enforced instead of report-only (`SecurityHeaders`). Lazy loading is blocked in every other environment. |
| `APP_DEBUG` | `false` | Hides stack traces from users. |
| `APP_URL` | Final HTTPS URL | Used for absolute links. |
| `APP_KEY` | Generated once with `php artisan key:generate` | Encrypts cookies. Rotating it logs everyone out. Keep `APP_PREVIOUS_KEYS` when you rotate. |
| `APP_LOCALE` | `bn` for Bangla-first installs | Default language for new sessions. Code default is `en`. See [Localization](localization.md). |
| `KCAM_MODE` | `single` | One school per install. Public registration is closed. |
| `KCAM_MODULE_*` | `true` or `false` per package | Turns modules on or off. See [Architecture](architecture.md#module-switches). |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL 8 with a dedicated user | Grant that user only the install database. Do not use the root account. |
| `SESSION_DRIVER` | `database` | Sessions survive a PHP restart. Needs the `sessions` table. |
| `SESSION_SECURE_COOKIE` | `true` | Sends the session cookie only over HTTPS. It is not set by default. |
| `SESSION_LIFETIME` | `120` (as in `.env.example`) or your policy | Minutes of inactivity before the session ends. |
| `SESSION_ENCRYPT` | Optional `true` | The default is `false`. Enable it if your policy requires encrypted session payloads. |
| `CACHE_STORE` | `database` | Needs the `cache` and `cache_locks` tables. |
| `QUEUE_CONNECTION` | `database` | No job is dispatched by the code today. See [Queues and scheduler](#queues-and-scheduler). |
| `FILESYSTEM_DISK` | `local` | Must stay `local` (`storage/app/private`). Student photos and NID scans depend on it. |
| `LOG_LEVEL` | `warning` or `error` | `.env.example` uses `debug`. Lower verbosity keeps personal data out of logs. |
| `LOG_CHANNEL`, `LOG_STACK` | `stack` and `single` | Writes to `storage/logs/laravel.log`. |
| `MAIL_MAILER` | `log` or a real transport | No mailable classes exist in the code, so mail is not used yet. |

## Deploy steps

Run these on the server, in the project directory, as the deploy user.

```bash
php artisan down --retry=60
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Notes on each step:

- `php artisan down` puts the app in maintenance mode. The driver is `file` (`APP_MAINTENANCE_DRIVER` in `.env.example`).
- `npm ci` uses `package-lock.json`. It needs Node 20.19 or newer, or 22.12 or newer (required by Vite 8).
- `npm run build` writes `public/build`, which is in `.gitignore`. Build on the server or upload the output.
- `migrate --force` is required outside local development. It runs the migrations without asking.
- `config:cache` must be run again after any `.env` change. Otherwise the app keeps the old values.
- `route:cache` has to serialize the one closure route in `routes/web.php` (the `/` home route). Laravel supports this by serializing the closure as an unsigned closure, but this app has not been tested with it. Run it on staging first. Converting that route to an invokable controller removes the question.
- Do not run `php artisan db:seed` in production. The demo seeder skips itself outside `local` and `testing`, but the seeder still logs a warning each time.

### First install

```bash
php artisan migrate --force
php artisan kcam:install
```

`kcam:install` asks for the school and owner details. Answer the password prompt yourself. Do not pass `--owner-password` on the command line, because the value stays in shell history. The command refuses to run a second time (see [Getting started](getting-started.md#first-time-setup)).

## Web root and public files

- Point the web server document root at `public/`, and route every request that is not a file to `public/index.php`. Nothing outside `public/` may be served.
- `public/` contains `index.php`, `robots.txt`, `favicon.ico`, `manifest.json`, `sw.js`, `fonts/` and `build/`. `fonts/kalpurush.ttf` is no longer referenced by the CSS.
- `sw.js` caches only the offline page, the manifest and versioned build assets. Pages are never cached. `public/sw.js` clears every cache when a page posts the `clear-caches` message. No page in this codebase posts it, so logout does not clear the caches today.
- There is no `storage:link` step. The `public` disk is defined in `config/filesystems.php` but no code uses it.

## Storage and private uploads

Student photos and guardian NID scans are personal data. They live on the private `local` disk, which is `storage/app/private` (`config/filesystems.php`):

| Upload | Path pattern | Served by |
|---|---|---|
| Student photo | `storage/app/private/students/<school_id>/photo/` | `students.photo` route (permission `view-students`) |
| Guardian NID front or back | `storage/app/private/students/<school_id>/guardian-nid/` | `students.guardian-nid` route (permission `view-students`) |

Rules for the server:

- Keep `storage/app/private` outside `public/`. Never symlink it into `public/`.
- Owner is the deploy user, group is the PHP-FPM user, and directories are not world readable. For example `chmod -R u=rwX,g=rX,o= storage/app/private`.
- The files are not in git. `storage/app/private/.gitignore` ignores everything except itself.
- Back up `storage/app/private` together with the database. `students.photo` and `guardians.nid_front_path` / `nid_back_path` store paths into this folder, so a database restore without the files leaves broken links.
- Uploads are limited to 2048 KB each. Photos must be JPG, PNG or WebP. NID scans must be JPG, PNG or PDF (rules in `StoreStudentRequest` and `UpdateStudentRequest`).

## Database backups

Use a logical dump with a consistent snapshot. Replace the placeholders with your values:

```bash
mysqldump --single-transaction --routines -u DB_USERNAME -p DB_DATABASE > kcam-$(date +%F).sql
```

- Store dumps encrypted and off the server. They contain names, mobile numbers, birth registration numbers and NID numbers.
- Keep a copy of `APP_KEY`, or the full `.env`, in your secret store. A restore on a new server needs the same `APP_KEY` to read the cookies and sessions, and it needs the `KCAM_*` values.
- Test a restore on a separate host at least once. Check that you can sign in and that a student photo opens.
- The application has no backup job. Scheduling is your responsibility.

## Sessions, cache and HTTPS

- Sessions are stored in the `sessions` table (`SESSION_DRIVER=database`). `SESSION_SAME_SITE` defaults to `lax` and `SESSION_HTTP_ONLY` defaults to `true` (`config/session.php`).
- Set `SESSION_SECURE_COOKIE=true` when the site is served over HTTPS. Laravel does not set it when the variable is missing.
- `SecurityHeaders` sends `Strict-Transport-Security` only when `$request->isSecure()` is true. `bootstrap/app.php` does not configure trusted proxies. If TLS ends at a load balancer or reverse proxy, Laravel sees plain HTTP, so the HSTS header is not sent. Add trusted proxies in `bootstrap/app.php` before you rely on HTTPS detection.
- Login throttling is keyed by the login value and `$request->ip()` (`AuthenticatedSessionController::throttleKey`). Behind a proxy without trusted proxies, every user shares the proxy's IP and therefore one limit.
- The CSP is enforced outside `local` (`SecurityHeaders`). It allows `'unsafe-inline'` and `'unsafe-eval'` for scripts, because Livewire and Alpine need them. The docblock says nonces are tracked in `docs/dev-plan.md`.
- `vite.config.js` declares Instrument Sans through `laravel-vite-plugin/fonts` (Bunny provider). The build downloads the font and writes it to `public/build/assets/`, so the browser makes no Bunny request at runtime and the CSP is not involved. No CSS uses the family, so this entry can be removed.

## Queues and scheduler

- The code dispatches no jobs and declares no `ShouldQueue` classes. `routes/console.php` contains only the default `inspire` command, and no task is scheduled.
- `QUEUE_CONNECTION=database` still needs the `jobs` table, which the framework migrations create. `composer run dev` starts `queue:listen` for development only.
- No `queue:work` process or cron entry for `schedule:run` is needed today. When you add jobs or scheduled tasks, add a supervised `queue:work` process and a cron entry for `php artisan schedule:run` at the same time.

## Logs and monitoring

- Logs go to `storage/logs/laravel.log` (`LOG_STACK=single`). Stack traces can contain personal data. Restrict the file to the deploy and PHP users, and rotate it.
- `/health` returns JSON with `status`, `database` and a timestamp. It returns 503 when the database is unreachable. It exposes no environment details. Use it for uptime checks.
- `/up` is the framework health route registered in `bootstrap/app.php`.
- `report()` records database failures from `/health` in the log.

## Limitations

- No deployment automation, CI workflow or server template is in the repository.
- Trusted proxies are not configured (see above). Behind a reverse proxy, HSTS detection and per-IP login throttling are wrong until you configure them.
- `SESSION_SECURE_COOKIE` is not set by default, and `.env.example` sets `APP_DEBUG=true` and `LOG_LEVEL=debug`. Change them on every production install.
- NID numbers (`guardians.nid`) are stored as plain text, and uploaded files are not encrypted at rest. Protection relies on the server's file permissions and access control.
- The CSP allows `'unsafe-inline'` and `'unsafe-eval'`.
- `database/database.sqlite` exists in the working tree. It is ignored by git, and the MySQL setup does not use it. Do not copy it to a production server.
- The school `settings.timezone` (`Asia/Dhaka`) is not applied. `config/app.php` hard-codes `UTC`, and the environment has no timezone key. Receipt serials take their year from `date('Y')` in that timezone, so receipts issued between 00:00 and 05:59 on 1 January Bangladesh time get the previous year's prefix. Decide on the timezone before the first live year-end.
- No payment gateway or SMS gateway is connected, and the code sends no mail. The install does not send messages or take online payments.
- `route:cache` on the closure route in `routes/web.php` has not been tested in this environment. Run it on staging first.
