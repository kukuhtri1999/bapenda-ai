$content = Get-Content 'app\Http\Controllers\PhotoEditingController.php' -Raw
$content = $content -replace "ini_set\('memory_limit', '512M'\);", "ini_set('memory_limit', '1024M'); // 1GB"
$content = $content -replace "ini_set\('max_execution_time', 300\); // 5 minutes", "ini_set('max_execution_time', 600); // 10 minutes`n        set_time_limit(600); // Alternative way to set time limit"
$content | Set-Content 'app\Http\Controllers\PhotoEditingController.php'
Write-Host "Controller updated successfully!"
