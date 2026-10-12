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
    'SESSION_DRIVER' => 'cookie', 'CACHE_STORE' => 'array',   // no shared storage between servers, so keep these out of the database
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) { putenv("$key=$value"); $_ENV[$key] = $_SERVER[$key] = $value; }
}
if (getenv('DB_CONNECTION') === 'sqlite' && getenv('DB_DATABASE') === '/tmp/database.sqlite' && ! file_exists('/tmp/database.sqlite')) {
    copy(__DIR__.'/../database/demo.sqlite', '/tmp/database.sqlite');
}

$storage = '/tmp/storage';
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    is_dir("$storage/$dir") || mkdir("$storage/$dir", 0755, true);
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($storage);

$app->handleRequest(Request::capture());
