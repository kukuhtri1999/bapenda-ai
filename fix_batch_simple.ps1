# Batch Upload Implementation for Vue Component

# Read current content
$content = Get-Content 'resources\js\Pages\PhotoEditing\Index.vue' -Raw

# Find and replace uploadFiles function
$pattern = 'const uploadFiles = async \(files\) => \{[\s\S]*?\n\};'

$replacement = @'
const uploadFiles = async (files) => {
    if (files.length === 0) return;

    if (files.length > 100) {
        showSnackbar("Maximum 100 photos allowed", "error");
        return;
    }

    uploading.value = true;

    // Initialize progress values
    uploadProgress.value.processed = 0;
    uploadProgress.value.total = files.length;
    uploadProgress.value.show = true;

    // Start progress tracking for uploads > 5 files
    if (files.length > 5) {
        startProgressTracking();
    }

    try {
        const BATCH_SIZE = 15; // Process 15 files at a time (under PHP limit of 20)
        const batches = [];

        // Split files into batches
        for (let i = 0; i < files.length; i += BATCH_SIZE) {
            batches.push(files.slice(i, i + BATCH_SIZE));
        }

        let allUploadedPhotos = [];

        // Process each batch
        for (let batchIndex = 0; batchIndex < batches.length; batchIndex++) {
            const batch = batches[batchIndex];

            const formData = new FormData();
            batch.forEach((file) => {
                formData.append("photos[]", file);
            });

            const response = await fetch("/edit-foto/upload", {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            });

            if (response.status === 401) {
                window.location.href = "/edit-foto/login";
                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                allUploadedPhotos.push(...data.photos);
                // Update progress manually since we're doing batches
                uploadProgress.value.processed = Math.min(
                    uploadProgress.value.processed + batch.length,
                    uploadProgress.value.total
                );
            } else {
                throw new Error("Upload failed for batch " + (batchIndex + 1));
            }
        }

        // Add all photos to the display
        photos.value.push(...allUploadedPhotos);
        showSnackbar(
            `${allUploadedPhotos.length} photos uploaded and edited successfully!`,
        );

    } catch (error) {
        console.error("Upload error:", error);
        showSnackbar("Upload failed: " + error.message, "error");
    } finally {
        stopProgressTracking();
        uploading.value = false;
    }
};'@

$content = $content -replace $pattern, $replacement

$content | Set-Content 'resources\js\Pages\PhotoEditing\Index.vue'
Write-Host "Updated uploadFiles function to handle batch uploads (15 files per batch)!"
