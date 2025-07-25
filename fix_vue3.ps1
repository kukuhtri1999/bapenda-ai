$content = Get-Content 'resources\js\Pages\PhotoEditing\Index.vue' -Raw

# Add progress initialization in uploadFiles function
$content = $content -replace "uploading\.value = true;(\s*\n\s*)// Start progress tracking", "uploading.value = true;`$1`n    // Initialize progress values`n    uploadProgress.value.processed = 0;`n    uploadProgress.value.total = files.length;`n    uploadProgress.value.show = true;`n`n    // Start progress tracking"

$content | Set-Content 'resources\js\Pages\PhotoEditing\Index.vue'
Write-Host "Vue component upload initialization updated!"
