/*
 * BATCH UPLOAD SOLUTION FOR PHOTO EDITING
 *
 * Problem: PHP max_file_uploads = 20, we need 100
 * Solution: Split uploads into batches of 15 files each
 *
 * This function replaces the existing uploadFiles function
 * Copy and paste this into your Index.vue file around line 411
 */

const uploadFiles = async (files) => {
  if (files.length === 0) return;

  if (files.length > 100) {
    showSnackbar('Maximum 100 photos allowed', 'error');
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
    const BATCH_SIZE = 15; // Process 15 files per batch (under PHP limit)
    const batches = [];

    // Split files into batches
    for (let i = 0; i < files.length; i += BATCH_SIZE) {
      batches.push(files.slice(i, i + BATCH_SIZE));
    }

    const allUploadedPhotos = [];

    // Process each batch sequentially
    for (let batchIndex = 0; batchIndex < batches.length; batchIndex++) {
      const batch = batches[batchIndex];

      // Create FormData for this batch
      const formData = new FormData();
      batch.forEach((file) => {
        formData.append('photos[]', file);
      });

      // Upload this batch
      const response = await fetch('/edit-foto/upload', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content'),
        },
      });

      if (response.status === 401) {
        window.location.href = '/edit-foto/login';
        return;
      }

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        allUploadedPhotos.push(...data.photos);

        // Update progress manually for batches
        uploadProgress.value.processed = allUploadedPhotos.length;

        console.log(
          `Batch ${batchIndex + 1}/${batches.length} completed: ${batch.length} photos processed`,
        );
      } else {
        throw new Error(`Upload failed for batch ${batchIndex + 1}`);
      }
    }

    // Add all photos to display
    photos.value.push(...allUploadedPhotos);
    showSnackbar(
      `${allUploadedPhotos.length} photos uploaded and edited successfully!`,
    );
  } catch (error) {
    console.error('Upload error:', error);
    showSnackbar(`Upload failed: ${error.message}`, 'error');
  } finally {
    stopProgressTracking();
    uploading.value = false;
  }
};
