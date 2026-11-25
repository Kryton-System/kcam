# 0004. Session locale with Bangla as the install default

**Status:** Accepted

## Context

KCAM is Bangla-first. Most users are parents, teachers and owners who read Bangla, while the developers and some staff prefer English. The language must be switchable by anyone, including signed-out visitors on the login page. The `users` table has no locale column, so a choice cannot be stored on the account without a migration and a settings screen that do not exist yet.

## Decision

- The visitor's choice is stored in the session under the key `locale`. `LocaleController` accepts only `en` and `bn` (`SetLocale::SUPPORTED`) and returns 404 for any other value.
- `SetLocale` runs on every web request, after `SecurityHeaders`. It applies the session value when it is valid. Otherwise it uses `config('app.locale')`.
- `config('app.locale')` reads `APP_LOCALE` and defaults to `en`. `.env.example` sets `APP_LOCALE=en`. Bangla-first installs set `APP_LOCALE=bn`, and the local `.env` in this working copy is set to `bn`. The Bangla default is therefore a per-install setting, not a code default.
- Enum labels, flash messages and screen text come from `lang/en` and `lang/bn`. Input is normalized for Bangla digits before validation (`NormalizesInput`, `BanglaHelper::toEnglishDigits`).

Code evidence:

- `app/Http/Middleware/SetLocale.php`
- `app/Http/Controllers/LocaleController.php`
- `routes/web.php` (`/language/{locale}`, name `locale.switch`)
- `config/app.php` (`locale` and `fallback_locale`)
- `.env.example` (`APP_LOCALE`)
- `bootstrap/app.php` (`SetLocale` appended to the web group)
- `app/Http/Requests/Concerns/NormalizesInput.php`
- `tests/Feature/LocaleSwitchTest.php`, `tests/Unit/BanglaHelperTest.php`, `tests/Unit/LocaleFormatTest.php`

## Consequences

- The language follows the browser session, not the account. A new browser or a new device starts with `APP_LOCALE`. The session lifetime is `SESSION_LIFETIME` minutes.
- Installs that need Bangla by default must set `APP_LOCALE=bn` in `.env` and run `config:cache` again. A missed setting shows English to every new visitor.
- The fallback locale is `en`. Missing Bangla keys show English text rather than a raw key.
- Every key added to `lang/en` needs the same key in `lang/bn`. Nothing in the code enforces this yet.
- `LocaleSwitchTest` expects Bangla as the default ("site defaults to Bangla"), but `phpunit.xml` does not set `APP_LOCALE`. The result depends on the `.env` file. A clean checkout without a `.env` that sets `bn` fails those tests.
- A per-user language setting is a future option. It would need a `locale` column and a change to `SetLocale`. This decision does not block it.
