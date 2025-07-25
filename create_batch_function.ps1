Write-Host "Creating batch upload Vue component..."

# Create the new uploadFiles function content
$newFunction = 'const uploadFiles = async (files) => {
    if (files.length === 0) return;

    if (files.length > 100) {
        showSnackbar("Maximum 100 photos allowed", "error");
        return;
    }

    uploading.value = true;
    uploadProgress.value.processed = 0;
    uploadProgress.value.total = files.length;
    uploadProgress.value.show = true;

    if (files.length > 5) {
        startProgressTracking();
    }

    try {
        const BATCH_SIZE = 15;
        const batches = [];

        for (let i = 0; i < files.length; i += BATCH_SIZE) {
            batches.push(files.slice(i, i + BATCH_SIZE));
        }

        let allUploadedPhotos = [];

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
                        .querySelector(`meta[name="csrf-token"]`)
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
                uploadProgress.value.processed = Math.min(
                    uploadProgress.value.processed + batch.length,
                    uploadProgress.value.total
                );
            } else {
                throw new Error("Upload failed for batch " + (batchIndex + 1));
            }
        }

        photos.value.push(...allUploadedPhotos);
        showSnackbar(
            `${allUploadedPhotos.length} photos uploaded and edited successfully!`
        );

    } catch (error) {
        console.error("Upload error:", error);
        showSnackbar("Upload failed: " + error.message, "error");
    } finally {
        stopProgressTracking();
        uploading.value = false;
    }
};'

# Write the function to a temporary file
$newFunction | Set-Content 'temp_upload_function.js'

Write-Host "Created new upload function. Now updating Vue file..."
