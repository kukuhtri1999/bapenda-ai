$content = Get-Content 'resources\js\Pages\PhotoEditing\Index.vue' -Raw

# Add better safety checks and fallbacks for division
$content = $content -replace "uploadProgress\.total > 0 && uploadProgress\.processed !== undefined", "uploadProgress.total > 0 && uploadProgress.processed >= 0"

# Add fallback for NaN values
$content = $content -replace "Math\.round\(\s*\(uploadProgress\.processed /\s*uploadProgress\.total\) \*\s*100,?\s*\)", "Math.round((uploadProgress.processed || 0) / (uploadProgress.total || 1) * 100)"

$content | Set-Content 'resources\js\Pages\PhotoEditing\Index.vue'
Write-Host "Added better safety checks for progress calculation!"
