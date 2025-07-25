$content = Get-Content 'resources\js\Pages\PhotoEditing\Index.vue' -Raw

# Add progress initialization in uploadFiles function
$pattern = "uploading\.value = true;\s*\n\s*// Start progress tracking for uploads > 5 files"
$replacement = @"
uploading.value = true;

    // Initialize progress values
    uploadProgress.value.processed = 0;
    uploadProgress.value.total = files.length;
    uploadProgress.value.show = true;

    // Start progress tracking for uploads > 5 files"@

$content = $content -replace $pattern, $replacement

$content | Set-Content 'resources\js\Pages\PhotoEditing\Index.vue'
Write-Host "Vue component upload initialization updated!"
