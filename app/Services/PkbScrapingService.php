<?php

namespace App\Services;

use App\Models\WajibPajak;
use App\Models\DataPkb;
use App\Models\TambahanBiaya;
use App\Models\Captcha;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PkbScrapingService
{
    private $driver;
    private $wait;
    private $sessionId;

    public function __construct()
    {
        $this->sessionId = uniqid('pkb_session_');
    }

    /**
     * Initialize Chrome WebDriver
     */
    private function initializeDriver()
    {
        try {
            $options = new ChromeOptions();
            $options->addArguments([
                '--headless=new', // Use new headless mode
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-web-security',
                '--disable-features=VizDisplayCompositor',
                '--disable-extensions',
                '--disable-plugins',
                '--disable-images', // Faster loading
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36'
            ]);

            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

            // Try connecting to ChromeDriver
            $endpoint = 'http://localhost:9515';

            Log::info("Trying to connect to WebDriver at: " . $endpoint);
            $this->driver = RemoteWebDriver::create($endpoint, $capabilities, 30000, 30000);
            $this->wait = new WebDriverWait($this->driver, 30);
            Log::info("Successfully connected to WebDriver at: " . $endpoint);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to initialize WebDriver: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Make sure ChromeDriver is running: C:\chromedriver\chromedriver.exe --port=9515');
            return false;
        }
    }

    /**
     * Main function: Submit form, get captcha, wait for user input, then continue
     */
    public function checkPkbWithCaptchaFlow($wajibPajakData, $recaptchaToken)
    {
        try {
            // Validate reCAPTCHA
            if (!$this->validateRecaptcha($recaptchaToken)) {
                return ['success' => false, 'message' => 'reCAPTCHA validation failed'];
            }

            // Save wajib pajak data
            $wajibPajak = WajibPajak::create($wajibPajakData);

            // Initialize WebDriver - this session will stay alive throughout the process
            if (!$this->initializeDriver()) {
                return ['success' => false, 'message' => 'Failed to initialize browser'];
            }

            // Navigate to PKB info website
            $this->driver->get('https://info.dipendajatim.go.id/index.php?page=info_pkb');

            // Wait for page to load
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('captcha'))
            );

            // Fill form data FIRST (before captcha)
            Log::info("Filling PKB form with data...");
            $this->fillPkbFormData($wajibPajak);

            // Screenshot captcha and save as captcha_ask.jpg (fixed name)
            $captchaPath = $this->screenshotCaptchaFixed();
            if (!$captchaPath) {
                $this->cleanup();
                return ['success' => false, 'message' => 'Failed to capture captcha'];
            }

            // Signal frontend that captcha is ready
            Log::info("Captcha saved, signaling frontend...");

            // Sleep 30 seconds to allow user to input captcha answer
            Log::info("Waiting 30 seconds for user to input captcha answer...");
            sleep(30);

            // Get captcha answer from database (ID 1)
            $captchaAnswer = $this->getCaptchaAnswerFromDB();
            if (!$captchaAnswer) {
                $this->cleanup();
                return ['success' => false, 'message' => 'No captcha answer provided within time limit'];
            }

            Log::info("Got captcha answer: " . $captchaAnswer);

            // Fill captcha and submit form
            $this->fillCaptchaAndSubmit($captchaAnswer);

            // Process results
            $result = $this->processPkbResults($wajibPajak);

            $this->cleanup();
            return $result;
        } catch (\Exception $e) {
            Log::error('PKB Check Error: ' . $e->getMessage());
            $this->cleanup();
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }

    /**
     * Continue PKB checking after captcha submission
     */
    public function continuePkbCheck($wajibPajakId, $captchaAnswer, $sessionId)
    {
        try {
            Log::info("Starting continuePkbCheck for wajib_pajak_id: $wajibPajakId, session: $sessionId");

            // Check if session exists in cache
            $sessionData = Cache::get("pkb_session_{$sessionId}");
            if (!$sessionData) {
                Log::error("No active session found in cache for: " . $sessionId);
                return ['success' => false, 'message' => 'Session expired or invalid'];
            }

            Log::info("Session found in cache: " . json_encode($sessionData));

            $wajibPajak = WajibPajak::find($wajibPajakId);
            if (!$wajibPajak) {
                Log::error("Wajib pajak not found with ID: $wajibPajakId");
                Cache::forget("pkb_session_{$sessionId}");
                return ['success' => false, 'message' => 'Wajib pajak not found'];
            }

            Log::info("Found wajib pajak: " . $wajibPajak->nama);

            // Save captcha answer
            Captcha::create(['jawaban_captcha' => $captchaAnswer]);
            Log::info("Captcha answer saved: $captchaAnswer");

            // Initialize new WebDriver connection for form submission
            if (!$this->initializeDriver()) {
                Log::error("Failed to initialize WebDriver for form submission");
                Cache::forget("pkb_session_{$sessionId}");
                return ['success' => false, 'message' => 'Failed to initialize browser'];
            }

            // Navigate to PKB website
            Log::info("Navigating to PKB website for form submission...");
            $this->driver->get('https://info.dipendajatim.go.id/index.php?page=info_pkb');

            // Restore cookies from previous session to maintain captcha consistency
            if (isset($sessionData['cookies'])) {
                Log::info("Restoring cookies from previous session...");
                foreach ($sessionData['cookies'] as $cookieData) {
                    try {
                        $this->driver->manage()->addCookie($cookieData);
                    } catch (\Exception $e) {
                        Log::warning("Failed to restore cookie: " . $cookieData['name']);
                    }
                }

                // Refresh page after setting cookies
                $this->driver->get('https://info.dipendajatim.go.id/index.php?page=info_pkb');
            }

            // Wait for form elements
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('txtnopol'))
            );

            Log::info("Filling PKB form...");
            // Fill form data directly
            $this->fillPkbForm($wajibPajak, $captchaAnswer);

            Log::info("Processing PKB results...");
            // Submit form and get results
            $result = $this->processPkbResults($wajibPajak);

            // Clean up the session after successful completion
            Cache::forget("pkb_session_{$sessionId}");
            $this->cleanup();
            Log::info("continuePkbCheck completed successfully");
            return $result;
        } catch (\Exception $e) {
            Log::error('Continue PKB Check Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Cache::forget("pkb_session_{$sessionId}");
            $this->cleanup();
            return ['success' => false, 'message' => 'System error: ' . $e->getMessage()];
        }
    }

    /**
     * Screenshot captcha image
     */
    private function screenshotCaptcha()
    {
        try {
            // Wait for captcha element
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('captcha'))
            );

            Log::info("Captcha element found, getting image...");

            // Wait for captcha image to load
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#captcha img'))
            );

            // Get the captcha image source directly
            $captchaImg = $this->driver->findElement(WebDriverBy::cssSelector('#captcha img'));
            $captchaSrc = $captchaImg->getAttribute('src');
            Log::info("Captcha image src: " . $captchaSrc);

            // Convert relative URL to absolute URL
            if (strpos($captchaSrc, 'http') !== 0) {
                $baseUrl = 'https://info.dipendajatim.go.id';
                $captchaSrc = $baseUrl . $captchaSrc;
            }

            Log::info("Absolute captcha URL: " . $captchaSrc);

            // Try to download the captcha image directly using Guzzle or cURL
            try {
                // Get browser cookies for authentication
                $cookies = $this->driver->manage()->getCookies();
                $cookieString = '';
                foreach ($cookies as $cookie) {
                    $cookieString .= $cookie['name'] . '=' . $cookie['value'] . '; ';
                }

                // Use cURL to download the image with same session
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $captchaSrc);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $imageData) {
                    // Save the image data
                    $filename = 'cekpkb/captcha_' . $this->sessionId . '.png';
                    Storage::disk('public')->put($filename, $imageData);

                    Log::info("Captcha image downloaded and saved: " . $filename);
                    Log::info("Image size: " . strlen($imageData) . " bytes");

                    return asset('storage/' . $filename);
                } else {
                    Log::warning("Failed to download captcha image, HTTP code: " . $httpCode);
                    throw new \Exception("Failed to download captcha image");
                }
            } catch (\Exception $e) {
                Log::warning("Direct download failed: " . $e->getMessage() . ", falling back to screenshot");

                // Fallback to screenshot method
                // Scroll to captcha element to ensure it's in view
                $this->driver->executeScript("
                    var captchaEl = document.getElementById('captcha');
                    if (captchaEl) {
                        captchaEl.scrollIntoView({behavior: 'instant', block: 'center'});
                    }
                ");

                // Wait a moment for scroll to complete
                sleep(1);

                // Take a full page screenshot
                $screenshot = $this->driver->takeScreenshot();

                // Save the screenshot
                $filename = 'cekpkb/captcha_' . $this->sessionId . '.png';
                Storage::disk('public')->put($filename, $screenshot);

                Log::info("Captcha screenshot saved: " . $filename);

                return asset('storage/' . $filename);
            }
        } catch (\Exception $e) {
            Log::error('Captcha Screenshot Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Fill PKB form with data
     */
    private function fillPkbForm($wajibPajak, $captchaAnswer)
    {
        try {
            Log::info("Filling form with nopol: " . $wajibPajak->nopol);

            // Wait for form elements to be present
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('txtnopol'))
            );

            // Fill nopol
            $nopolInput = $this->driver->findElement(WebDriverBy::id('txtnopol'));
            $nopolInput->clear();
            $nopolInput->sendKeys($wajibPajak->nopol);
            Log::info("Nopol filled: " . $wajibPajak->nopol);

            // Fill 5 digit terakhir no rangka
            $norangInput = $this->driver->findElement(WebDriverBy::id('txtnorang'));
            $norangInput->clear();
            $norangInput->sendKeys($wajibPajak->lima_digit_terakhir_no_rangka);
            Log::info("No rangka filled: " . $wajibPajak->lima_digit_terakhir_no_rangka);

            // Try different selectors for captcha input
            $captchaInput = null;
            $captchaSelectors = [
                WebDriverBy::id('txtcaptcha'),
                WebDriverBy::name('captcha'),
                WebDriverBy::id('captcha_input'),
                WebDriverBy::cssSelector('input[placeholder*="captcha" i]'),
                WebDriverBy::cssSelector('input[placeholder*="kode" i]'),
            ];

            foreach ($captchaSelectors as $selector) {
                try {
                    $captchaInput = $this->driver->findElement($selector);
                    Log::info("Found captcha input with selector: " . $selector->getMechanism() . "=" . $selector->getValue());
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$captchaInput) {
                throw new \Exception("Captcha input field not found with any selector");
            }

            // Fill captcha answer
            $captchaInput->clear();
            $captchaInput->sendKeys($captchaAnswer);
            Log::info("Captcha filled: " . $captchaAnswer);

            // Submit form - try different submit methods
            try {
                $submitButton = $this->driver->findElement(WebDriverBy::id('btncari'));
                $submitButton->click();
                Log::info("Form submitted via btncari button");
            } catch (\Exception $e) {
                try {
                    $submitButton = $this->driver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
                    $submitButton->click();
                    Log::info("Form submitted via submit button");
                } catch (\Exception $e2) {
                    try {
                        $submitButton = $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
                        $submitButton->click();
                        Log::info("Form submitted via button submit");
                    } catch (\Exception $e3) {
                        // Try form submit
                        $form = $this->driver->findElement(WebDriverBy::tagName('form'));
                        $form->submit();
                        Log::info("Form submitted via form.submit()");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Fill PKB Form Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Fill PKB form data (without captcha)
     */
    private function fillPkbFormData($wajibPajak)
    {
        try {
            Log::info("Filling form with nopol: " . $wajibPajak->nopol);

            // Wait for form elements to be present
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('txtnopol'))
            );

            // Fill nopol
            $nopolInput = $this->driver->findElement(WebDriverBy::id('txtnopol'));
            $nopolInput->clear();
            $nopolInput->sendKeys($wajibPajak->nopol);
            Log::info("Nopol filled: " . $wajibPajak->nopol);

            // Fill 5 digit terakhir no rangka
            $norangInput = $this->driver->findElement(WebDriverBy::id('txtnorang'));
            $norangInput->clear();
            $norangInput->sendKeys($wajibPajak->lima_digit_terakhir_no_rangka);
            Log::info("No rangka filled: " . $wajibPajak->lima_digit_terakhir_no_rangka);
        } catch (\Exception $e) {
            Log::error('Fill PKB Form Data Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Screenshot captcha with fixed filename
     */
    private function screenshotCaptchaFixed()
    {
        try {
            // Wait for captcha element
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('captcha'))
            );

            Log::info("Captcha element found, taking screenshot...");

            // Wait for captcha image to load
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#captcha img'))
            );

            // Get the captcha image source directly
            $captchaImg = $this->driver->findElement(WebDriverBy::cssSelector('#captcha img'));
            $captchaSrc = $captchaImg->getAttribute('src');
            Log::info("Captcha image src: " . $captchaSrc);

            // Convert relative URL to absolute URL
            if (strpos($captchaSrc, 'http') !== 0) {
                $baseUrl = 'https://info.dipendajatim.go.id';
                $captchaSrc = $baseUrl . $captchaSrc;
            }

            // Try to download the captcha image directly
            try {
                // Get browser cookies for authentication
                $cookies = $this->driver->manage()->getCookies();
                $cookieString = '';
                foreach ($cookies as $cookie) {
                    $cookieString .= $cookie['name'] . '=' . $cookie['value'] . '; ';
                }

                // Use cURL to download the image with same session
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $captchaSrc);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $imageData) {
                    // Save with fixed filename: captcha_ask.jpg
                    $filename = 'captcha_ask.jpg';
                    Storage::disk('public')->put($filename, $imageData);

                    Log::info("Captcha image saved as: " . $filename);
                    Log::info("Image size: " . strlen($imageData) . " bytes");

                    return asset('storage/' . $filename);
                } else {
                    Log::warning("Failed to download captcha image, HTTP code: " . $httpCode);
                    throw new \Exception("Failed to download captcha image");
                }
            } catch (\Exception $e) {
                Log::warning("Direct download failed: " . $e->getMessage() . ", falling back to screenshot");

                // Fallback to screenshot method
                $this->driver->executeScript("
                    var captchaEl = document.getElementById('captcha');
                    if (captchaEl) {
                        captchaEl.scrollIntoView({behavior: 'instant', block: 'center'});
                    }
                ");

                sleep(1);
                $screenshot = $this->driver->takeScreenshot();

                // Save with fixed filename: captcha_ask.jpg
                $filename = 'captcha_ask.jpg';
                Storage::disk('public')->put($filename, $screenshot);

                Log::info("Captcha screenshot saved as: " . $filename);
                return asset('storage/' . $filename);
            }
        } catch (\Exception $e) {
            Log::error('Captcha Screenshot Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get captcha answer from database (ID 1)
     */
    private function getCaptchaAnswerFromDB()
    {
        try {
            $captcha = Captcha::find(1);
            if ($captcha && $captcha->jawaban_captcha) {
                Log::info("Found captcha answer in database: " . $captcha->jawaban_captcha);
                return $captcha->jawaban_captcha;
            }

            Log::warning("No captcha answer found in database");
            return null;
        } catch (\Exception $e) {
            Log::error('Get Captcha Answer Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fill captcha and submit form
     */
    private function fillCaptchaAndSubmit($captchaAnswer)
    {
        try {
            Log::info("Filling captcha with answer: " . $captchaAnswer);

            // Find captcha input field
            $captchaInput = null;
            $captchaSelectors = [
                WebDriverBy::id('txtcaptcha'),
                WebDriverBy::name('captcha'),
                WebDriverBy::id('captcha_input'),
                WebDriverBy::cssSelector('input[placeholder*="captcha" i]'),
                WebDriverBy::cssSelector('input[placeholder*="kode" i]'),
            ];

            foreach ($captchaSelectors as $selector) {
                try {
                    $captchaInput = $this->driver->findElement($selector);
                    Log::info("Found captcha input with selector: " . $selector->getMechanism() . "=" . $selector->getValue());
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$captchaInput) {
                throw new \Exception("Captcha input field not found with any selector");
            }

            // Fill captcha answer
            $captchaInput->clear();
            $captchaInput->sendKeys($captchaAnswer);
            Log::info("Captcha filled: " . $captchaAnswer);

            // Submit form
            try {
                $submitButton = $this->driver->findElement(WebDriverBy::id('btncari'));
                $submitButton->click();
                Log::info("Form submitted via btncari button");
            } catch (\Exception $e) {
                try {
                    $submitButton = $this->driver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
                    $submitButton->click();
                    Log::info("Form submitted via submit button");
                } catch (\Exception $e2) {
                    $form = $this->driver->findElement(WebDriverBy::tagName('form'));
                    $form->submit();
                    Log::info("Form submitted via form.submit()");
                }
            }
        } catch (\Exception $e) {
            Log::error('Fill Captcha and Submit Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save captcha answer to database (async function for frontend)
     */
    public function saveCaptchaAnswer($captchaAnswer)
    {
        try {
            // Update or create captcha with ID 1
            Captcha::updateOrCreate(
                ['id' => 1],
                ['jawaban_captcha' => $captchaAnswer]
            );

            Log::info("Captcha answer saved to database: " . $captchaAnswer);
            return ['success' => true, 'message' => 'Captcha answer saved'];
        } catch (\Exception $e) {
            Log::error('Save Captcha Answer Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save captcha answer'];
        }
    }

    /**
     * Process PKB results
     */
    private function processPkbResults($wajibPajak)
    {
        try {
            // Wait for results
            $this->wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('result'))
            );

            // Check for error message
            $errorElements = $this->driver->findElements(WebDriverBy::cssSelector('#result .alert.alert-warning'));
            if (count($errorElements) > 0) {
                $errorMessage = $errorElements[0]->getText();
                return ['success' => false, 'message' => $errorMessage];
            }

            // Extract PKB data
            $pkbData = $this->extractPkbData();
            $tambahanBiaya = $this->extractTambahanBiaya();

            // Save to database
            $dataPkb = DataPkb::create(array_merge($pkbData, ['id_wajib_pajak' => $wajibPajak->id]));

            // Save tambahan biaya if exists
            foreach ($tambahanBiaya as $biaya) {
                TambahanBiaya::create(array_merge($biaya, ['id_data_pkb' => $dataPkb->id]));
            }

            return [
                'success' => true,
                'data_pkb' => $dataPkb->load('tambahanBiaya'),
                'wajib_pajak' => $wajibPajak
            ];
        } catch (\Exception $e) {
            Log::error('Process PKB Results Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process results'];
        }
    }

    /**
     * Extract PKB data from webpage
     */
    private function extractPkbData()
    {
        $data = [];

        try {
            sleep(5); // Add 5 second delay
            // Take screenshot and save
            $screenshotPath = storage_path('app/cekpkb');
            if (!file_exists($screenshotPath)) {
                mkdir($screenshotPath, 0777, true);
            }

            $filename = 'pkb_screenshot_' . date('Y-m-d_H-i-s') . '.png';
            $this->driver->takeScreenshot($screenshotPath . '/' . $filename);

            // Extract vehicle information
            $data['nopol'] = $this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(1) td:nth-child(2)');
            $data['warna'] = $this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(2) td:nth-child(2)');
            $data['model'] = $this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(3) td:nth-child(2)');
            $data['merk'] = $this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(4) td:nth-child(2)');
            $data['type'] = $this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(5) td:nth-child(2)');
            $data['tahun'] = $this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(6) td:nth-child(2)');
            $data['tanggal_masa_pajak'] = $this->parseDate($this->getTableCellText('#result .table-striped:nth-child(3) tr:nth-child(7) td:nth-child(2)'));

            // Extract fee information
            $data['pkb'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(1) td:nth-child(2)'));
            $data['opsen_pkb'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(2) td:nth-child(2)'));
            $data['pkb_progresif'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(3) td:nth-child(2)'));
            $data['opsen_pkb_prog'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(4) td:nth-child(2)'));
            $data['swdkllj'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(5) td:nth-child(2)'));
            $data['parkir_berlangganan'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(6) td:nth-child(2)'));
            $data['pengesahan_stnk'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(7) td:nth-child(2)'));
            $data['total'] = $this->parseAmount($this->getTableCellText('#result .table-striped:nth-child(5) tr:nth-child(8) td:nth-child(2)'));

            Log::info("Extracted PKB data: " . json_encode($data)); // Log data instead of dd()
        } catch (\Exception $e) {
            Log::error('Extract PKB Data Error: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Extract tambahan biaya from webpage
     */
    private function extractTambahanBiaya()
    {
        $tambahanBiaya = [];

        try {
            // Check if tambahan biaya table exists
            $tambahanBiayaElements = $this->driver->findElements(WebDriverBy::cssSelector('#result .table-striped:nth-child(7)'));

            if (count($tambahanBiayaElements) > 0) {
                $rows = $this->driver->findElements(WebDriverBy::cssSelector('#result .table-striped:nth-child(7) tr'));

                foreach ($rows as $index => $row) {
                    if ($index === 0) continue; // Skip header row

                    $labelElement = $row->findElement(WebDriverBy::cssSelector('td:nth-child(1)'));
                    $hargaElement = $row->findElement(WebDriverBy::cssSelector('td:nth-child(2)'));

                    $tambahanBiaya[] = [
                        'label_biaya' => $labelElement->getText(),
                        'harga_biaya' => $this->parseAmount($hargaElement->getText())
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Extract Tambahan Biaya Error: ' . $e->getMessage());
        }

        return $tambahanBiaya;
    }

    /**
     * Get text from table cell
     */
    private function getTableCellText($selector)
    {
        try {
            $element = $this->driver->findElement(WebDriverBy::cssSelector($selector));
            return trim($element->getText());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse amount string to decimal
     */
    private function parseAmount($amountString)
    {
        if (!$amountString) return 0;

        // Remove currency symbols and formatting
        $cleaned = preg_replace('/[^\d,.]/', '', $amountString);
        $cleaned = str_replace(',', '', $cleaned);

        return (float) $cleaned;
    }

    /**
     * Parse date string
     */
    private function parseDate($dateString)
    {
        if (!$dateString) return null;

        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate Google reCAPTCHA
     */
    private function validateRecaptcha($token)
    {
        $secretKey = '6LcyLYIrAAAAADBINB0QeH-QVunvJpB9iMEVLQU_';

        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$token}");
        $responseKeys = json_decode($response, true);

        return $responseKeys['success'] === true;
    }

    /**
     * Cleanup WebDriver resources
     */
    private function cleanup()
    {
        if ($this->driver) {
            try {
                $this->driver->quit();
            } catch (\Exception $e) {
                Log::error('WebDriver cleanup error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Clean up expired sessions from cache
     */
    public static function cleanupExpiredSessions()
    {
        // Laravel cache automatically handles TTL expiration
        // This method can be used for additional cleanup if needed
        Log::info("Cache-based sessions are automatically cleaned up by Laravel");
    }

    /**
     * Soft delete PKB data
     */
    public function deletePkbData($dataPkbId)
    {
        try {
            $dataPkb = DataPkb::find($dataPkbId);
            if ($dataPkb) {
                // Soft delete tambahan biaya
                $dataPkb->tambahanBiaya()->delete();

                // Soft delete data PKB
                $dataPkb->delete();

                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Delete PKB Data Error: ' . $e->getMessage());
            return false;
        }
    }
}
