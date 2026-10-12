<?php

use Illuminate\Support\Facades\Schedule;

// Cron on the host should run `php artisan schedule:run` every minute.
Schedule::command('foods:update-prices')->weeklyOn(1, '03:00');
