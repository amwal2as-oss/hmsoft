<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Route::get('/cron/prune-users', function () {
    // التحقق من المفتاح السري المبعوث في الـ Header
    if (request()->header('X-Cron-Secret') !== config('app.cron_secret')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // تشغيل الأمر الذي أنشأناه
    Artisan::call('users:prune-unverified');

    return response()->json(['message' => 'Cleanup command executed successfully']);
});


// Verify the hash chain itself every hour
Schedule::command('audit:verify')->hourly();

// Run the heavy database state-match at 3:00 AM 
Schedule::command('audit:state-match')->dailyAt('03:00');
