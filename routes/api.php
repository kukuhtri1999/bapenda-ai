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
