<?php

use HMsoft\Tools\Features\DateTime\Controllers\DateTimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('datetime')->group(function () {
    Route::get('/config', [DateTimeController::class, 'config']);
    Route::get('/now', [DateTimeController::class, 'now']);

    Route::post('/convert', [DateTimeController::class, 'convert']);
});
