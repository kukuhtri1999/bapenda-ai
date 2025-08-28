<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\LotreController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Chat API Routes
Route::prefix('chat')->group(function () {
    Route::post('/start', [ChatController::class, 'startChat']);
    Route::post('/message', [ChatController::class, 'sendMessage']);
    Route::get('/history', [ChatController::class, 'getChatHistory']);
    Route::post('/close', [ChatController::class, 'closeChat']);
});

// Public App Settings API Routes
Route::prefix('settings')->group(function () {
    Route::get('/public', [AppSettingController::class, 'getPublic']);
    Route::get('/group/{group}', [AppSettingController::class, 'getGroup']);
});

// Lotre public APIs
Route::prefix('lotre')->group(function () {
    Route::get('/participants', [LotreController::class, 'list']);
    Route::get('/winners', [LotreController::class, 'winners']);
    Route::post('/participants', [LotreController::class, 'store']);
});
