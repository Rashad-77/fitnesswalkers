<?php

// This replaces public/index.php when the site lives in public_html and the app lives in ~/fitnesswalkers.
// If you put the app in a different folder, change APP_PATH below.
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
$app_path = __DIR__.'/../fitnesswalkers';

if (file_exists($maintenance = $app_path.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app_path.'/vendor/autoload.php';

(require_once $app_path.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
