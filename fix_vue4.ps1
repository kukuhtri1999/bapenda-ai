$content = Get-Content 'resources\js\Pages\PhotoEditing\Index.vue' -Raw

# Fix template to use processed instead of current
$content = $content -replace "uploadProgress\.current", "uploadProgress.processed"

$content | Set-Content 'resources\js\Pages\PhotoEditing\Index.vue'
Write-Host "Fixed template to use processed property!"
