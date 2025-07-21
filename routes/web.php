<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WajibPajakController;

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

// Public Wajib Pajak routes (entry point for chat)
Route::get('/wajib-pajak', [WajibPajakController::class, 'showForm'])->name('wajib-pajak.form');
Route::post('/api/wajib-pajak/start-chat', [WajibPajakController::class, 'startChatSession'])->name('wajib-pajak.start-chat');
Route::post('/api/wajib-pajak/clear-session', [WajibPajakController::class, 'clearSession'])->name('wajib-pajak.clear-session');
Route::get('/api/check-wajib-pajak-session', [WajibPajakController::class, 'checkSession'])->name('wajib-pajak.check-session');

// Public chat route (accessible without login for public service) - REQUIRES WAJIB PAJAK DATA
Route::middleware('ensure.wajib.pajak')->get('/customer-service', function () {
    return Inertia::render('Chat/Index', [
        'wajibPajakData' => session('wajib_pajak_data')
    ]);
})->name('customer-service');
