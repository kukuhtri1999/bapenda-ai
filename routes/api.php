<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

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

// PKB API Routes
Route::prefix('pkb')->group(function () {
    Route::post('/check', [App\Http\Controllers\PkbController::class, 'check']);
    Route::post('/save-captcha-answer', [App\Http\Controllers\PkbController::class, 'saveCaptchaAnswer']);
    Route::post('/submit-captcha', [App\Http\Controllers\PkbController::class, 'submitCaptcha']);
    Route::post('/confirm', [App\Http\Controllers\PkbController::class, 'confirmData']);
    Route::get('/data/{id}', [App\Http\Controllers\PkbController::class, 'getPkbData']);
});
