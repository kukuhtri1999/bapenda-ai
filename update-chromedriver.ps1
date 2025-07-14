# PowerShell script to download correct ChromeDriver for Chrome 138
Write-Host "Updating ChromeDriver for Chrome 138..." -ForegroundColor Green

# Chrome version detected: 138.0.7204.101
$chromeMajorVersion = "138"
Write-Host "Chrome major version: $chromeMajorVersion" -ForegroundColor Yellow

# Create chromedriver directory
$chromedriverDir = "C:\chromedriver"
if (!(Test-Path $chromedriverDir)) {
    New-Item -ItemType Directory -Path $chromedriverDir -Force
}

# Remove old ChromeDriver
Remove-Item "$chromedriverDir\chromedriver.exe" -Force -ErrorAction SilentlyContinue

# Download correct ChromeDriver for Chrome 138
try {
    # For newer Chrome versions (115+), use the new JSON API
    Write-Host "Downloading ChromeDriver for Chrome 138..." -ForegroundColor Yellow

    # Use a known working version for Chrome 138
    $downloadUrl = "https://storage.googleapis.com/chrome-for-testing-public/138.0.7204.101/win32/chromedriver-win32.zip"
    $zipPath = "$chromedriverDir\chromedriver.zip"

    Write-Host "Downloading from: $downloadUrl" -ForegroundColor Yellow
    Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath

    # Extract ChromeDriver
    Expand-Archive -Path $zipPath -DestinationPath $chromedriverDir -Force

    # Move chromedriver.exe from subfolder to main directory
    if (Test-Path "$chromedriverDir\chromedriver-win32\chromedriver.exe") {
        Move-Item "$chromedriverDir\chromedriver-win32\chromedriver.exe" "$chromedriverDir\chromedriver.exe" -Force
        Remove-Item "$chromedriverDir\chromedriver-win32" -Recurse -Force
    }

    Remove-Item $zipPath

    Write-Host "ChromeDriver 138 installed successfully at: $chromedriverDir\chromedriver.exe" -ForegroundColor Green

} catch {
    Write-Host "Error downloading ChromeDriver 138: $($_.Exception.Message)" -ForegroundColor Red

    # Fallback to ChromeDriver for Testing repository
    try {
        Write-Host "Trying fallback download for Chrome 138..." -ForegroundColor Yellow
        $fallbackUrl = "https://storage.googleapis.com/chrome-for-testing-public/138.0.7204.59/win32/chromedriver-win32.zip"
        Invoke-WebRequest -Uri $fallbackUrl -OutFile "$chromedriverDir\chromedriver.zip"
        Expand-Archive -Path "$chromedriverDir\chromedriver.zip" -DestinationPath $chromedriverDir -Force

        # Move chromedriver.exe from subfolder to main directory
        if (Test-Path "$chromedriverDir\chromedriver-win32\chromedriver.exe") {
            Move-Item "$chromedriverDir\chromedriver-win32\chromedriver.exe" "$chromedriverDir\chromedriver.exe" -Force
            Remove-Item "$chromedriverDir\chromedriver-win32" -Recurse -Force
        }

        Remove-Item "$chromedriverDir\chromedriver.zip"
        Write-Host "ChromeDriver fallback version for Chrome 138 installed successfully" -ForegroundColor Green
    } catch {
        Write-Host "Fallback download also failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please manually download ChromeDriver from https://googlechromelabs.github.io/chrome-for-testing/" -ForegroundColor Yellow
    }
}

Write-Host "Setup complete!" -ForegroundColor Green
Write-Host "ChromeDriver location: $chromedriverDir\chromedriver.exe" -ForegroundColor Yellow
Write-Host "To test, run: $chromedriverDir\chromedriver.exe --version" -ForegroundColor Yellow
