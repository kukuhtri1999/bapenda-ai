<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Services\PkbScrapingService;

Route::get('/test-pkb-structure', function () {
  try {
    Log::info('Testing PKB website structure...');

    $service = new PkbScrapingService();

    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($service);
    $initMethod = $reflection->getMethod('initializeDriver');
    $initMethod->setAccessible(true);

    $result = $initMethod->invoke($service);

    if (!$result) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to initialize WebDriver'
      ]);
    }

    // Access private driver property
    $driverProperty = $reflection->getProperty('driver');
    $driverProperty->setAccessible(true);
    $driver = $driverProperty->getValue($service);

    // Navigate to PKB website
    $driver->get('https://info.dipendajatim.go.id/index.php?page=info_pkb');

    // Wait for page to load
    sleep(3);

    // Get page title
    $pageTitle = $driver->getTitle();

    // Get page source
    $pageSource = $driver->getPageSource();

    // Check if captcha element exists
    $captchaExists = false;
    $captchaHtml = '';
    try {
      $captchaElement = $driver->findElement(\Facebook\WebDriver\WebDriverBy::id('captcha'));
      $captchaExists = true;
      $captchaHtml = $captchaElement->getAttribute('outerHTML');
    } catch (\Exception $e) {
      $captchaHtml = 'Captcha element not found: ' . $e->getMessage();
    }

    // Get all form elements
    $formElements = [];
    try {
      $inputs = $driver->findElements(\Facebook\WebDriver\WebDriverBy::tagName('input'));
      foreach ($inputs as $input) {
        $formElements[] = [
          'type' => $input->getAttribute('type'),
          'name' => $input->getAttribute('name'),
          'id' => $input->getAttribute('id'),
          'placeholder' => $input->getAttribute('placeholder'),
        ];
      }
    } catch (\Exception $e) {
      $formElements = ['error' => $e->getMessage()];
    }

    // Take a full page screenshot for debugging
    $screenshot = $driver->takeScreenshot();
    $filename = 'debug/pkb_page_structure.png';
    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $screenshot);

    // Cleanup
    $driver->quit();

    return response()->json([
      'success' => true,
      'page_title' => $pageTitle,
      'captcha_exists' => $captchaExists,
      'captcha_html' => substr($captchaHtml, 0, 1000), // Limit output
      'form_elements' => $formElements,
      'screenshot_url' => asset('storage/' . $filename),
      'page_source_length' => strlen($pageSource)
    ]);
  } catch (\Exception $e) {
    Log::error('Test PKB structure error: ' . $e->getMessage());
    return response()->json([
      'success' => false,
      'message' => 'Error: ' . $e->getMessage()
    ]);
  }
});
