# PowerShell script to fix malformed v-icon tags in Vue files

Write-Host "Finding and fixing malformed v-icon tags in Vue files..."

$vueFiles = Get-ChildItem -Path "resources\js" -Recurse -Filter "*.vue"

foreach ($file in $vueFiles) {
    Write-Host "Processing: $($file.FullName)"

    $content = Get-Content $file.FullName -Raw
    $originalContent = $content

    # Fix v-icon tags with line breaks
    $content = $content -replace '(?s)<v-icon([^>]*)>\s*([^<]+)\s*</v-icon\s*>', '<v-icon$1>$2</v-icon>'

    # Fix any other malformed closing tags with spaces
    $content = $content -replace '(?s)</([a-zA-Z-]+)\s+>', '</$1>'

    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content
        Write-Host "Fixed: $($file.FullName)" -ForegroundColor Green
    } else {
        Write-Host "No changes needed: $($file.FullName)" -ForegroundColor Yellow
    }
}

Write-Host "Done fixing Vue files!" -ForegroundColor Cyan
