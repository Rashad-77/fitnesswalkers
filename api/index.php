<?php

// Entry point for Vercel (runtime: vercel-php). It does what public/index.php does, with one change:
// Vercel's disk is read-only except /tmp, so Laravel's storage folder lives there (sessions and cache are in the database).

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$storage = '/tmp/storage';
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    is_dir("$storage/$dir") || mkdir("$storage/$dir", 0755, true);
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($storage);

$app->handleRequest(Request::capture());
