<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/scripts/setup-database.php';

test('options are parsed into key value pairs and flags', function (): void {
    expect(parseOptions(['x', '--host=db.example.com', '--demo']))
        ->toBe(['host' => 'db.example.com', 'demo' => true]);
});

test('a bare argument is rejected', function (): void {
    parseOptions(['x', 'oops']);
})->throws(InvalidArgumentException::class, 'Unexpected argument');

test('database and username are required', function (): void {
    resolveConfig(['username' => 'u'], null, null);
})->throws(InvalidArgumentException::class, 'Missing --database');

test('unknown options are rejected', function (): void {
    resolveConfig(['database' => 'kcam', 'username' => 'u', 'hots' => 'x'], null, null);
})->throws(InvalidArgumentException::class, 'Unknown option --hots');

test('database names that could inject SQL are rejected', function (string $name): void {
    resolveConfig(['database' => $name, 'username' => 'u'], null, null);
})->with(['kcam`; DROP DATABASE x; --', 'a b', 'kcam-prod', '', str_repeat('a', 65)])
    ->throws(InvalidArgumentException::class);

test('defaults apply and the password comes from the environment', function (): void {
    $config = resolveConfig(['database' => 'kcam_prod', 'username' => 'u'], 'secret', null);

    expect($config)->toMatchArray([
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'kcam_prod',
        'username' => 'u',
        'password' => 'secret',
        'ssl_ca' => '',
    ]);
});

test('the password is prompted only when none is given', function (): void {
    $calls = 0;
    $prompt = function () use (&$calls): string {
        $calls++;

        return 'typed';
    };

    expect(resolveConfig(['database' => 'd', 'username' => 'u'], null, $prompt)['password'])->toBe('typed')
        ->and(resolveConfig(['database' => 'd', 'username' => 'u'], 'env', $prompt)['password'])->toBe('env')
        ->and($calls)->toBe(1);
});

test('a non numeric port is rejected', function (): void {
    resolveConfig(['database' => 'd', 'username' => 'u', 'port' => '33x6'], null, null);
})->throws(InvalidArgumentException::class, '--port');

test('child environment carries the db settings and the ssl ca only when set', function (): void {
    $env = childEnvironment(['host' => 'h', 'port' => '4000', 'database' => 'd', 'username' => 'u', 'password' => 'p', 'ssl_ca' => ''], 'production');

    expect($env)->toMatchArray(['DB_HOST' => 'h', 'DB_PORT' => '4000', 'DB_DATABASE' => 'd', 'DB_PASSWORD' => 'p', 'APP_ENV' => 'production'])
        ->and($env)->not->toHaveKey('MYSQL_ATTR_SSL_CA');
});

test('the script prints help and exits cleanly', function (): void {
    exec('php '.escapeshellarg(dirname(__DIR__, 2).'/scripts/setup-database.php').' --help 2>&1', $lines, $code);

    expect($code)->toBe(0)->and(implode("\n", $lines))->toContain('--database=NAME');
});

test('the script exits with an error code on bad input', function (): void {
    exec('php '.escapeshellarg(dirname(__DIR__, 2).'/scripts/setup-database.php').' --username=u 2>&1', $lines, $code);

    expect($code)->toBe(2)->and(implode("\n", $lines))->toContain('Missing --database');
});
