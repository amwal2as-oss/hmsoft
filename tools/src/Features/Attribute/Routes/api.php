<?php

use HMsoft\Tools\Features\Attribute\Controllers\AttributeController;
use Illuminate\Support\Facades\Route;

// Object-bound: definitions + selected values for one record
Route::get('{scope}/{valuable}/attributes', [AttributeController::class, 'forObject']);

Route::prefix('{scope}/attributes')->controller(AttributeController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::post('/updateAll', 'updateAll');
    Route::delete('/bulk-delete', 'bulkDelete');
    Route::get('/{attribute}', 'show');
    Route::post('/{attribute}', 'update');
    Route::delete('/{attribute}', 'destroy');
    Route::post('/{attribute}/icon', 'updateIcon');
});
