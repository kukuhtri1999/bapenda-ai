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
    Route::post('/convert-markdown', [ChatController::class, 'convertMarkdownToHtml']);
    Route::post('/end-session', [ChatController::class, 'endChatSession']);
    Route::post('/feedback', [ChatController::class, 'submitFeedback']);
});

// Admin analytics
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ChatHistoryController;

Route::prefix('admin/analytics')->group(function () {
    Route::get('/count', [AnalyticsController::class, 'count']);
    Route::post('/start', [AnalyticsController::class, 'start']);
    Route::get('/reports', [AnalyticsController::class, 'index']);
    Route::get('/reports/{id}', [AnalyticsController::class, 'show']);
});

// Admin chat history APIs
Route::prefix('admin/chat-history')->group(function () {
    Route::get('/meta',       [ChatHistoryController::class, 'meta']);
    Route::get('/list',       [ChatHistoryController::class, 'index']);
    Route::get('/show/{id}',  [ChatHistoryController::class, 'show']);
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
    Route::post('/pick', [LotreController::class, 'pick']);
    Route::post('/reset', [LotreController::class, 'resetWinners']);
    Route::post('/participants', [LotreController::class, 'store']);
});
