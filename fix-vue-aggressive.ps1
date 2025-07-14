# More aggressive Vue tag fixer

Write-Host "Scanning for all malformed tags in Vue files..." -ForegroundColor Cyan

$vueFiles = Get-ChildItem -Path "resources\js" -Recurse -Filter "*.vue"

foreach ($file in $vueFiles) {
    Write-Host "Processing: $($file.Name)" -ForegroundColor Yellow

    $content = Get-Content $file.FullName -Raw
    $originalContent = $content

    # Fix all closing tags that have spaces before >
    $content = $content -replace '</(\w+[-\w]*)\s+>', '</$1>'

    # Fix self-closing tags with spaces
    $content = $content -replace '<(\w+[-\w]*[^>]*)\s+/>', '<$1/>'

    # Fix v-icon specifically with any whitespace issues
    $content = $content -replace '(?s)<v-icon([^>]*)>\s*([^<]+)\s*</v-icon\s*>', '<v-icon$1>$2</v-icon>'

    # Fix any other Vue component closing tags
    $content = $content -replace '</v-(\w+[-\w]*)\s+>', '</v-$1>'

    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Host "FIXED: $($file.Name)" -ForegroundColor Green
    } else {
        Write-Host "OK: $($file.Name)" -ForegroundColor Gray
    }
}

Write-Host "Scanning complete!" -ForegroundColor Cyan
