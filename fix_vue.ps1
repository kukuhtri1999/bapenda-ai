$content = Get-Content 'resources\js\Pages\PhotoEditing\Index.vue' -Raw

# Fix progress structure: change uploadProgress.processed to uploadProgress.current
$content = $content -replace "uploadProgress\.processed", "uploadProgress.current"

# Fix progress data structure initialization to use processed instead of current
$content = $content -replace "current: 0,", "processed: 0,"
$content = $content -replace "uploadProgress\.value\.current", "uploadProgress.value.processed"

# Fix division by zero issues in template
$content = $content -replace "uploadProgress\.total > 0", "uploadProgress.total > 0 && uploadProgress.processed !== undefined"

$content | Set-Content 'resources\js\Pages\PhotoEditing\Index.vue'
Write-Host "Vue component updated successfully!"
