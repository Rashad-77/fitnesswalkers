<?php

// Entry point for Vercel (runtime: vercel-php). It does what public/index.php does, with one change:
// Vercel's disk is read-only except /tmp, so Laravel's storage folder lives there (sessions and cache are in the database).

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Demo mode: the data is the sample-data SQLite file kept in the repo (database/demo.sqlite).
// The repo is read-only on Vercel, so each server copies it to /tmp. Changes made on the live demo are temporary
// (they vanish when the server restarts) and are not shared between servers. To use a real database instead,
// set DB_CONNECTION and the DB_* variables in Vercel; those win over these defaults.
$defaults = [
    'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'LOG_CHANNEL' => 'stderr', 'QUEUE_CONNECTION' => 'sync',
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => '/tmp/database.sqlite',
    // Laravel keeps small cache files in bootstrap/cache, which is read-only on Vercel, so they go to /tmp.
    'APP_PACKAGES_CACHE' => '/tmp/storage/bootstrap-cache/packages.php', 'APP_SERVICES_CACHE' => '/tmp/storage/bootstrap-cache/services.php',
    'APP_CONFIG_CACHE' => '/tmp/storage/bootstrap-cache/config.php', 'APP_ROUTES_CACHE' => '/tmp/storage/bootstrap-cache/routes.php',
    'APP_EVENTS_CACHE' => '/tmp/storage/bootstrap-cache/events.php',
    'SHOW_ERRORS' => '1',   // demo only: show a short error message instead of a blank 500. Set SHOW_ERRORS=0 in Vercel to hide it.
    'SESSION_DRIVER' => 'cookie', 'CACHE_STORE' => 'array',   // no shared storage between servers, so keep these out of the database
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) { putenv("$key=$value"); $_ENV[$key] = $_SERVER[$key] = $value; }
}
if (getenv('DB_CONNECTION') === 'sqlite' && getenv('DB_DATABASE') === '/tmp/database.sqlite' && ! file_exists('/tmp/database.sqlite')) {
    copy(__DIR__.'/../database/demo.sqlite', '/tmp/database.sqlite');
}

// Say what is wrong, in plain words, instead of a blank 500 page.
$problems = [];
if (! getenv('APP_KEY')) $problems[] = 'APP_KEY is not set. Add it in Vercel: Settings > Environment Variables, then redeploy.';
if (PHP_VERSION_ID < 80300) $problems[] = 'PHP '.PHP_VERSION.' is too old; this app needs PHP 8.3 or newer.';
foreach (['mbstring', 'openssl', 'pdo', 'ctype', 'tokenizer', 'fileinfo'] as $ext) {
    if (! extension_loaded($ext)) $problems[] = "The PHP extension \"$ext\" is missing on this server.";
}
if (getenv('DB_CONNECTION') === 'sqlite' && ! extension_loaded('pdo_sqlite')) $problems[] = 'The PHP extension "pdo_sqlite" is missing on this server, so the demo database cannot be read.';
if (! is_file(__DIR__.'/../vendor/autoload.php')) $problems[] = 'The vendor folder is missing: composer install did not run during the build.';
if ($problems) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Setup problem:\n- ".implode("\n- ", $problems)."\n\nPHP ".PHP_VERSION."\n");
}

$storage = '/tmp/storage';
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'bootstrap-cache'] as $dir) {
    is_dir("$storage/$dir") || mkdir("$storage/$dir", 0755, true);
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($storage);

try {
    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit(getenv('SHOW_ERRORS') === '1' ? 'Error: '.get_class($e).': '.$e->getMessage() : 'Server error.');
}
