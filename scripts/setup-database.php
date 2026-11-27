<?php

declare(strict_types=1);

/**
 * One command to prepare a KCAM database: create it if missing, run the migrations,
 * seed roles and permissions, then create the school (kcam:install) or load demo data.
 *
 *   php scripts/setup-database.php --host=HOST --database=NAME --username=USER
 *
 * The password is never taken from the command line history by accident: leave out
 * --password to be prompted (hidden), or export KCAM_DB_PASSWORD. Nothing is written to
 * .env; the settings are passed to the child artisan processes through the environment.
 */
const USAGE = <<<'TXT'
Usage: php scripts/setup-database.php --database=NAME --username=USER [options]

Required:
  --database=NAME       Database name (letters, digits and underscore only)
  --username=USER       Database user

Optional:
  --host=HOST           Default 127.0.0.1
  --port=PORT           Default 3306
  --password=SECRET     Prefer the hidden prompt, or env KCAM_DB_PASSWORD
  --ssl-ca=PATH         CA bundle for TLS (most hosted databases require TLS)
  --demo                Load demo data (public demo passwords, never for real schools)
  --yes                 Do not ask for confirmation (with --demo)
  --push-vercel         Also store the DB settings as Vercel production env vars
  --vercel-ssl-ca=PATH  CA bundle path as seen on Vercel (set with --push-vercel)
  --help                Show this help

Without --demo the script runs `php artisan kcam:install` so you create the real
school and owner account. It only does so when the database has no school yet.
TXT;

/**
 * @param  list<string>  $argv
 * @return array<string, string|bool>
 */
function parseOptions(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $arg) {
        if (! str_starts_with($arg, '--')) {
            throw new InvalidArgumentException("Unexpected argument: {$arg}");
        }

        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }

    return $options;
}

/**
 * @param  array<string, string|bool>  $options
 * @return array{host: string, port: string, database: string, username: string, password: string, ssl_ca: string}
 */
function resolveConfig(array $options, ?string $envPassword, ?callable $promptPassword): array
{
    $known = ['host', 'port', 'database', 'username', 'password', 'ssl-ca', 'demo', 'yes', 'push-vercel', 'vercel-ssl-ca', 'help'];

    foreach (array_keys($options) as $name) {
        if (! in_array($name, $known, true)) {
            throw new InvalidArgumentException("Unknown option --{$name}");
        }
    }

    foreach (['database', 'username'] as $required) {
        if (! isset($options[$required]) || ! is_string($options[$required]) || $options[$required] === '') {
            throw new InvalidArgumentException("Missing --{$required}");
        }
    }

    $database = (string) $options['database'];

    if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $database) !== 1) {
        throw new InvalidArgumentException('--database may contain only letters, digits and underscore (max 64).');
    }

    $port = is_string($options['port'] ?? null) ? $options['port'] : '3306';

    if (preg_match('/^\d{1,5}$/', $port) !== 1) {
        throw new InvalidArgumentException('--port must be a number.');
    }

    $password = is_string($options['password'] ?? null) ? $options['password'] : ($envPassword ?? '');

    if ($password === '' && $promptPassword !== null) {
        $password = $promptPassword();
    }

    return [
        'host' => is_string($options['host'] ?? null) ? $options['host'] : '127.0.0.1',
        'port' => $port,
        'database' => $database,
        'username' => (string) $options['username'],
        'password' => $password,
        'ssl_ca' => is_string($options['ssl-ca'] ?? null) ? $options['ssl-ca'] : '',
    ];
}

function promptHidden(string $label): string
{
    fwrite(STDERR, $label);
    $tty = function_exists('posix_isatty') && posix_isatty(STDIN);

    if ($tty) {
        shell_exec('stty -echo');
    }

    $value = rtrim((string) fgets(STDIN), "\r\n");

    if ($tty) {
        shell_exec('stty echo');
        fwrite(STDERR, "\n");
    }

    return $value;
}

/**
 * @param  array{host: string, port: string, database: string, username: string, password: string, ssl_ca: string}  $config
 */
function connect(array $config, bool $withDatabase): PDO
{
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4".($withDatabase ? ";dbname={$config['database']}" : '');
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15];

    if ($config['ssl_ca'] !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
    }

    return new PDO($dsn, $config['username'], $config['password'], $options);
}

/**
 * Create the database when the user is allowed to; hosted providers often pre-create it.
 *
 * @param  array{host: string, port: string, database: string, username: string, password: string, ssl_ca: string}  $config
 */
function ensureDatabase(array $config): void
{
    try {
        $server = connect($config, false);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        step("Database `{$config['database']}` is ready.");

        return;
    } catch (PDOException $e) {
        step('Could not create the database ('.firstLine($e->getMessage()).'); checking whether it already exists.');
    }

    connect($config, true);
    step("Database `{$config['database']}` already exists.");
}

function firstLine(string $text): string
{
    return trim(strtok($text, "\n") ?: $text);
}

function step(string $message): void
{
    fwrite(STDOUT, "[kcam] {$message}\n");
}

/**
 * @param  array{host: string, port: string, database: string, username: string, password: string, ssl_ca: string}  $config
 * @return array<string, string>
 */
function childEnvironment(array $config, string $appEnv): array
{
    return array_filter([
        'DB_CONNECTION' => 'mysql',
        'DB_URL' => '',
        'DB_HOST' => $config['host'],
        'DB_PORT' => $config['port'],
        'DB_DATABASE' => $config['database'],
        'DB_USERNAME' => $config['username'],
        'DB_PASSWORD' => $config['password'],
        'MYSQL_ATTR_SSL_CA' => $config['ssl_ca'],
        'APP_ENV' => $appEnv,
    ], static fn (string $value, string $key): bool => $value !== '' || in_array($key, ['DB_URL', 'DB_PASSWORD'], true), ARRAY_FILTER_USE_BOTH);
}

/**
 * Run a command without a shell. Returns the exit code.
 *
 * @param  list<string>  $command
 * @param  array<string, string>  $extraEnv
 */
function run(array $command, array $extraEnv = [], ?string $stdin = null): int
{
    $descriptors = $stdin === null
        ? [0 => STDIN, 1 => STDOUT, 2 => STDERR]
        : [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR];

    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__), array_merge(getenv(), $extraEnv));

    if (! is_resource($process)) {
        return 1;
    }

    if ($stdin !== null) {
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
    }

    return proc_close($process);
}

/**
 * @param  array{host: string, port: string, database: string, username: string, password: string, ssl_ca: string}  $config
 */
function schoolCount(array $config): int
{
    return (int) connect($config, true)->query('select count(*) from schools')->fetchColumn();
}

/**
 * @param  array<string, string|bool>  $options
 * @param  array{host: string, port: string, database: string, username: string, password: string, ssl_ca: string}  $config
 */
function pushToVercel(array $options, array $config): void
{
    $values = [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => $config['host'],
        'DB_PORT' => $config['port'],
        'DB_DATABASE' => $config['database'],
        'DB_USERNAME' => $config['username'],
        'DB_PASSWORD' => $config['password'],
    ];

    if (is_string($options['vercel-ssl-ca'] ?? null)) {
        $values['MYSQL_ATTR_SSL_CA'] = $options['vercel-ssl-ca'];
    }

    foreach ($values as $name => $value) {
        $code = run(['vercel', 'env', 'add', $name, 'production', '--force'], [], $value);
        step($code === 0 ? "Vercel env {$name} set." : "Vercel env {$name} FAILED (run `vercel login` and `vercel link` first).");

        if ($code !== 0) {
            exit(1);
        }
    }
}

function main(array $argv): int
{
    try {
        $options = parseOptions($argv);

        if (isset($options['help'])) {
            fwrite(STDOUT, USAGE."\n");

            return 0;
        }

        $envPassword = getenv('KCAM_DB_PASSWORD');
        $config = resolveConfig(
            $options,
            $envPassword === false ? null : $envPassword,
            static fn (): string => promptHidden('Database password: '),
        );
    } catch (InvalidArgumentException $e) {
        fwrite(STDERR, 'Error: '.$e->getMessage()."\n\n".USAGE."\n");

        return 2;
    }

    $demo = isset($options['demo']);

    try {
        ensureDatabase($config);
    } catch (PDOException $e) {
        fwrite(STDERR, 'Error: cannot connect to the database: '.firstLine($e->getMessage())."\n");

        return 1;
    }

    $env = childEnvironment($config, $demo ? 'local' : (getenv('APP_ENV') ?: 'production'));
    run(['php', 'artisan', 'config:clear'], $env);

    step('Running migrations...');
    if (run(['php', 'artisan', 'migrate', '--force'], $env) !== 0) {
        return 1;
    }

    step('Seeding roles and permissions...');
    if (run(['php', 'artisan', 'db:seed', '--class=Database\\Seeders\\RoleAndPermissionSeeder', '--force'], $env) !== 0) {
        return 1;
    }

    if (schoolCount($config) > 0) {
        step('A school already exists in this database: skipping school setup.');
    } elseif ($demo) {
        if (! isset($options['yes'])) {
            fwrite(STDERR, "Demo data creates accounts with a public password (\"password\"). Anyone who can reach this site could sign in.\nType YES to continue: ");

            if (trim((string) fgets(STDIN)) !== 'YES') {
                step('Demo seeding cancelled.');

                return 1;
            }
        }

        step('Loading demo data...');
        if (run(['php', 'artisan', 'db:seed', '--class=Database\\Seeders\\DemoSchoolSeeder', '--force'], $env) !== 0) {
            return 1;
        }
    } else {
        step('Creating your school and owner account...');
        if (run(['php', 'artisan', 'kcam:install'], $env) !== 0) {
            return 1;
        }
    }

    if (isset($options['push-vercel'])) {
        pushToVercel($options, $config);
    }

    step('Done.');

    return 0;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(main($argv));
}
