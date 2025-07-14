<?php

use Illuminate\Support\Facades\Route;
use App\Services\PkbScrapingService;

Route::get('/cleanup-pkb-sessions', function () {
  PkbScrapingService::cleanupExpiredSessions();
  return response()->json(['message' => 'PKB sessions cleaned up successfully']);
});
