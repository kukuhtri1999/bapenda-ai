<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Services\PkbScrapingService;

Route::get('/test-webdriver', function () {
  try {
    Log::info('Testing WebDriver connection...');

    $service = new PkbScrapingService();

    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($service);
    $initMethod = $reflection->getMethod('initializeDriver');
    $initMethod->setAccessible(true);

    $result = $initMethod->invoke($service);

    if ($result) {
      return response()->json([
        'success' => true,
        'message' => 'WebDriver connection successful!',
        'chromedriver_running' => true
      ]);
    } else {
      return response()->json([
        'success' => false,
        'message' => 'WebDriver connection failed',
        'chromedriver_running' => false
      ]);
    }
  } catch (\Exception $e) {
    Log::error('WebDriver test error: ' . $e->getMessage());
    return response()->json([
      'success' => false,
      'message' => 'Error: ' . $e->getMessage(),
      'chromedriver_running' => false
    ]);
  }
});
