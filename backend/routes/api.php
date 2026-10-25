<?php

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\PosTransactionController;
use App\Http\Controllers\Api\TareController;
use Illuminate\Support\Facades\Route;

Route::middleware('device.token')->group(function () {
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/config', [ConfigController::class, 'show']);
    Route::post('/tare', [TareController::class, 'store']);
});

Route::post('/pos/transactions', [PosTransactionController::class, 'store'])
    ->middleware('pos.token');
