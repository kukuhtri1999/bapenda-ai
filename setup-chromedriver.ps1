# PowerShell script to download and setup ChromeDriver
Write-Host "Setting up ChromeDriver..." -ForegroundColor Green

# Get Chrome version
$chromeVersion = (Get-Item "C:\Program Files\Google\Chrome\Application\chrome.exe").VersionInfo.ProductVersion
$chromeMajorVersion = $chromeVersion.Split('.')[0]
Write-Host "Chrome version detected: $chromeVersion" -ForegroundColor Yellow
Write-Host "Chrome major version: $chromeMajorVersion" -ForegroundColor Yellow

# Create chromedriver directory
$chromedriverDir = "C:\chromedriver"
if (!(Test-Path $chromedriverDir)) {
    New-Item -ItemType Directory -Path $chromedriverDir -Force
}

# Download ChromeDriver
try {
    $url = "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_$chromeMajorVersion"
    $latestVersion = Invoke-RestMethod -Uri $url
    Write-Host "Latest ChromeDriver version for Chrome $chromeMajorVersion : $latestVersion" -ForegroundColor Yellow

    $downloadUrl = "https://chromedriver.storage.googleapis.com/$latestVersion/chromedriver_win32.zip"
    $zipPath = "$chromedriverDir\chromedriver.zip"

    Write-Host "Downloading ChromeDriver from: $downloadUrl" -ForegroundColor Yellow
    Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath

    # Extract ChromeDriver
    Expand-Archive -Path $zipPath -DestinationPath $chromedriverDir -Force
    Remove-Item $zipPath

    Write-Host "ChromeDriver installed successfully at: $chromedriverDir\chromedriver.exe" -ForegroundColor Green

    # Add to PATH if not already there
    $currentPath = [Environment]::GetEnvironmentVariable("PATH", "Machine")
    if ($currentPath -notlike "*$chromedriverDir*") {
        Write-Host "Adding ChromeDriver to system PATH..." -ForegroundColor Yellow
        [Environment]::SetEnvironmentVariable("PATH", "$currentPath;$chromedriverDir", "Machine")
        Write-Host "ChromeDriver added to PATH. Please restart your terminal." -ForegroundColor Green
    }

} catch {
    Write-Host "Error downloading ChromeDriver: $($_.Exception.Message)" -ForegroundColor Red

    # Fallback to latest stable
    try {
        Write-Host "Trying fallback download..." -ForegroundColor Yellow
        $fallbackUrl = "https://chromedriver.storage.googleapis.com/114.0.5735.90/chromedriver_win32.zip"
        Invoke-WebRequest -Uri $fallbackUrl -OutFile "$chromedriverDir\chromedriver.zip"
        Expand-Archive -Path "$chromedriverDir\chromedriver.zip" -DestinationPath $chromedriverDir -Force
        Remove-Item "$chromedriverDir\chromedriver.zip"
        Write-Host "ChromeDriver fallback version installed successfully" -ForegroundColor Green
    } catch {
        Write-Host "Fallback download also failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "Setup complete!" -ForegroundColor Green
Write-Host "ChromeDriver location: $chromedriverDir\chromedriver.exe" -ForegroundColor Yellow
Write-Host "To test, run: $chromedriverDir\chromedriver.exe --version" -ForegroundColor Yellow
