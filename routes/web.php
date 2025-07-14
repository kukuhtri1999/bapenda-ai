<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Route::get('/', function () {
    //     return redirect()->route('dashboard');
    // });
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/products', function () {
        return Inertia::render('Products/Index');
    })->name('products.index');
    Route::get('/chat', function () {
        return Inertia::render('Chat/Index');
    })->name('chat');
});

// Public chat route (accessible without login for public service)
Route::get('/customer-service', function () {
    return Inertia::render('Chat/Index');
})->name('customer-service');

// PKB routes (accessible without login for public service)
Route::get('/cek-pkb', [App\Http\Controllers\PkbController::class, 'index'])->name('pkb.index');

// Test route for WebDriver
if (app()->environment('local')) {
    include __DIR__ . '/test.php';
    include __DIR__ . '/debug.php';

    // Route untuk cleanup PKB sessions
    Route::get('/cleanup-pkb-sessions', function () {
        App\Services\PkbScrapingService::cleanupExpiredSessions();
        return response()->json(['message' => 'PKB sessions cleaned up successfully']);
    });
}
