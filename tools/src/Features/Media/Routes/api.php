<?php

use HMsoft\Tools\Features\Media\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// مسار الميديا المرتبطة بكيانات بشكل مباشر 
Route::prefix('{owner_type}/{owner_id}/media')->group(function () {
    Route::get('/', [MediaController::class, 'index']);

    Route::post('/', [MediaController::class, 'store']); // رفع ملف واحد (Object)
    Route::post('/bulk', [MediaController::class, 'storeBulk']); // رفع عدة ملفات (Array)

    Route::post('/bulk-update', [MediaController::class, 'updateAll']);
    Route::delete('/bulk-delete', [MediaController::class, 'deleteBulk']);

    // Binding name {medium}
    Route::get('/{medium}', [MediaController::class, 'show']);
    Route::post('/{medium}', [MediaController::class, 'update']);
    Route::delete('/{medium}', [MediaController::class, 'destroy']);
});
