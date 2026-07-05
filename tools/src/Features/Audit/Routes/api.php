<?php

use HMsoft\Tools\Features\Audit\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('audit')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{audit}', [AuditLogController::class, 'show']);
});
