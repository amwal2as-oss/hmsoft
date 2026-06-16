<?php

use HMsoft\Tools\Features\Attribute\Controllers\AttributeController;
use Illuminate\Support\Facades\Route;

Route::prefix('{scope}/attributes')->controller(AttributeController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::post('/updateAll', 'updateAll');
    Route::delete('/bulk-delete', 'bulkDelete');
    Route::get('/{attribute}', 'show');
    Route::post('/{attribute}', 'update');
    Route::delete('/{attribute}', 'destroy');
    Route::post('/{attribute}/image', 'updateImage');
});
