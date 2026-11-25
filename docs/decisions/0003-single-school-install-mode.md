# 0003. Single-school install mode

**Status:** Accepted

## Context

KCAM is sold as a package: one install per school, configured for that school, often on the school's own host. A hosted install that serves many schools is still possible. Both need the same code, and each customer needs a different set of modules. The code must not offer public sign-up on a single school install, where anyone could create a second school.

## Decision

- `KCAM_MODE` selects `single` or `multi`. The default is `single` (`config/kcam.php`, key `mode`).
- In `single` mode:
  - `CurrentSchool::id()` falls back to the only `schools` row. The lookup returns null when there are zero or two rows, so the scope fails closed.
  - The public registration form returns 404 (`RegisteredSchoolController`).
  - The school and owner are created once with `php artisan kcam:install`. The command refuses to run when a school exists.
- In `multi` mode the school comes from the logged in user, and registration is open. `phpunit.xml` runs the test suite in `multi` mode, and `SingleSchoolModeTest` covers the single-mode paths.
- `kcam:install` runs `RoleAndPermissionSeeder` and then `SetupSchoolAction`, which creates the school, owner, academic year, classes, sections and default fee heads in one transaction.

Code evidence:

- `config/kcam.php` (`mode`)
- `app/Support/Tenancy/CurrentSchool.php` (`id`, `soleSchoolId`, `flush`)
- `app/Http/Controllers/Auth/RegisteredSchoolController.php` (`abort_if(config('kcam.mode') === 'single', 404)`)
- `app/Console/Commands/InstallKcam.php`
- `app/Domain/School/Actions/SetupSchoolAction.php`
- `phpunit.xml` (`KCAM_MODE` set to `multi`)
- `tests/Feature/SingleSchoolModeTest.php`

## Consequences

- Operators create the school from the command line once. The web cannot create a school in single mode.
- Each install needs exactly one `schools` row. Running the demo seeder after `kcam:install` adds a second row, and then single mode finds no school until the extra row is removed (see [Getting started](../getting-started.md#demo-data-local-and-testing-only)).
- Both modes must keep working. Changes to tenancy or registration need tests in both modes.
- Module switches (`KCAM_MODULE_*`) are per install, so one code base serves several packages.
- Switching an install from `single` to `multi` also opens public registration (`RegisteredSchoolController`). Review that before the switch. Existing rows keep their `school_id`.
