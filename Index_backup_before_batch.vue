<template>
    <v-app>
        <v-app-bar color="primary" dark>
            <v-app-bar-title>
                <v-icon class="mr-2">mdi-image-edit</v-icon>
                Photo Editor
            </v-app-bar-title>
            <v-spacer></v-spacer>
            <v-btn
                v-if="photos.length > 0"
                @click="downloadAll"
                color="success"
                :loading="downloading"
            >
                <v-icon left>mdi-download</v-icon>
                Download All
            </v-btn>
        </v-app-bar>

        <v-main>
            <v-container fluid class="pa-6">
                <!-- Upload Area -->
                <v-card class="mb-6" elevation="2">
                    <v-card-title>
                        <v-icon class="mr-2">mdi-cloud-upload</v-icon>
                        Upload Photos
                    </v-card-title>
                    <v-card-text>
                        <div
                            @drop="handleDrop"
                            @dragover.prevent
                            @dragenter.prevent
                            class="drop-zone"
                            :class="{ 'drag-over': isDragOver }"
                            @dragenter="isDragOver = true"
                            @dragleave="isDragOver = false"
                        >
                            <v-icon size="64" color="primary" class="mb-4"
                                >mdi-cloud-upload-outline</v-icon
                            >
                            <h3 class="text-h6 mb-2">
                                Drag & Drop Photos Here
                            </h3>
                            <p class="text-body-2 text-medium-emphasis mb-4">
                                Or click to select files (max 100 photos, 2MB
                                each)
                            </p>
                            <v-btn
                                color="primary"
                                @click="$refs.fileInput.click()"
                                :loading="uploading"
                            >
                                <v-icon left>mdi-folder-open</v-icon>
                                Select Photos
                            </v-btn>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept="image/*"
                                @change="handleFileSelect"
                                style="display: none"
                            />
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Photos Grid -->
                <v-card v-if="photos.length > 0" elevation="2">
                    <v-card-title>
                        <v-icon class="mr-2">mdi-image-multiple</v-icon>
                        Edited Photos ({{ photos.length }})
                        <v-spacer></v-spacer>
                        <v-btn
                            @click="downloadAll"
                            color="success"
                            :loading="downloading"
                            class="mr-2"
                        >
                            <v-icon left>mdi-download</v-icon>
                            Download All
                        </v-btn>
                        <v-btn
                            @click="deleteAllPhotos"
                            color="error"
                            :loading="deletingAll"
                            class="mr-2"
                        >
                            <v-icon left>mdi-delete-sweep</v-icon>
                            Delete All
                        </v-btn>
                        <v-chip color="info" variant="outlined">
                            Auto-expiry: 2 hours
                        </v-chip>
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col
                                v-for="photo in photos"
                                :key="photo.id"
                                cols="12"
                                sm="6"
                                md="4"
                                lg="3"
                            >
                                <v-card elevation="4" class="photo-card">
                                    <div class="image-container">
                                        <v-img
                                            :src="photo.edited_url"
                                            :alt="photo.original_filename"
                                            height="200"
                                            cover
                                            class="cursor-pointer"
                                            @click="openImageDialog(photo)"
                                        >
                                            <v-overlay
                                                contained
                                                class="align-center justify-center"
                                                opacity="0"
                                                hover
                                            >
                                                <v-icon size="32" color="white"
                                                    >mdi-eye</v-icon
                                                >
                                            </v-overlay>
                                        </v-img>
                                        <v-btn
                                            @click="deletePhoto(photo.id)"
                                            color="error"
                                            size="small"
                                            icon
                                            class="delete-btn"
                                            :loading="
                                                deletingIds.includes(photo.id)
                                            "
                                        >
                                            <v-icon>mdi-delete</v-icon>
                                        </v-btn>
                                    </div>
                                    <v-card-text class="pa-3">
                                        <p
                                            class="text-body-2 font-weight-medium mb-1"
                                        >
                                            {{ photo.original_filename }}
                                        </p>
                                        <v-chip
                                            v-for="(
                                                value, key
                                            ) in photo.edit_details"
                                            :key="key"
                                            v-if="
                                                key !== 'timestamp' &&
                                                value !== 0
                                            "
                                            size="x-small"
                                            class="mr-1 mb-1"
                                            :color="getChipColor(key, value)"
                                            variant="outlined"
                                        >
                                            {{ getChipLabel(key, value) }}
                                        </v-chip>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Empty State -->
                <v-card v-else elevation="2">
                    <v-card-text class="text-center pa-12">
                        <v-icon size="80" color="grey-lighten-1" class="mb-4"
                            >mdi-image-off-outline</v-icon
                        >
                        <h3 class="text-h6 text-medium-emphasis mb-2">
                            No Photos Yet
                        </h3>
                        <p class="text-body-2 text-medium-emphasis">
                            Upload some photos to get started with batch editing
                        </p>
                    </v-card-text>
                </v-card>
            </v-container>
        </v-main>

        <!-- Image Preview Dialog -->
        <v-dialog v-model="imageDialog" max-width="800">
            <v-card v-if="selectedPhoto">
                <v-card-title class="d-flex align-center">
                    {{ selectedPhoto.original_filename }}
                    <v-spacer></v-spacer>
                    <v-btn icon @click="imageDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-0">
                    <v-row no-gutters>
                        <v-col cols="6">
                            <div class="pa-4">
                                <h4 class="text-subtitle-1 mb-2">Original</h4>
                                <v-img
                                    :src="selectedPhoto.original_url"
                                    :alt="selectedPhoto.original_filename"
                                    max-height="300"
                                    contain
                                ></v-img>
                            </div>
                        </v-col>
                        <v-col cols="6">
                            <div class="pa-4">
                                <h4 class="text-subtitle-1 mb-2">Edited</h4>
                                <v-img
                                    :src="selectedPhoto.edited_url"
                                    :alt="selectedPhoto.original_filename"
                                    max-height="300"
                                    contain
                                ></v-img>
                            </div>
                        </v-col>
                    </v-row>
                    <v-divider></v-divider>
                    <div class="pa-4">
                        <h4 class="text-subtitle-1 mb-2">Applied Edits</h4>
                        <v-chip
                            v-for="(value, key) in selectedPhoto.edit_details"
                            :key="key"
                            v-if="key !== 'timestamp'"
                            class="mr-2 mb-2"
                            :color="getChipColor(key, value)"
                            variant="outlined"
                        >
                            {{ getChipLabel(key, value) }}
                        </v-chip>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Snackbar for notifications -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="3000"
        >
            {{ snackbar.message }}
        </v-snackbar>

        <!-- Upload Progress Dialog -->
        <v-dialog
            v-model="uploadProgress.show"
            persistent
            max-width="400"
            class="progress-dialog"
        >
            <v-card elevation="8" rounded="lg">
                <v-card-title class="text-h6 bg-primary text-white">
                    <v-icon class="mr-2">mdi-cloud-upload</v-icon>
                    Processing Photos
                </v-card-title>
                <v-card-text class="pa-6">
                    <div class="text-center mb-4">
                        <v-progress-circular
                            :model-value="
                                uploadProgress.total > 0 && uploadProgress.processed >= 0
                                    ? (uploadProgress.processed /
                                          uploadProgress.total) *
                                      100
                                    : 0
                            "
                            size="80"
                            width="8"
                            color="primary"
                        >
                            {{
                                uploadProgress.total > 0 && uploadProgress.processed >= 0
                                    ? Math.round((uploadProgress.processed || 0) / (uploadProgress.total || 1) * 100)
                                    : 0
                            }}%
                        </v-progress-circular>
                    </div>
                    <div class="text-center">
                        <div class="text-h6 mb-2">
                            {{ uploadProgress.processed }} /
                            {{ uploadProgress.total }}
                        </div>
                        <div class="text-body-2 text-medium-emphasis">
                            Processing photos with random effects...
                        </div>
                    </div>
                    <v-progress-linear
                        :model-value="
                            uploadProgress.total > 0 && uploadProgress.processed >= 0
                                ? (uploadProgress.processed /
                                      uploadProgress.total) *
                                  100
                                : 0
                        "
                        height="6"
                        rounded
                        color="primary"
                        class="mt-4"
                    ></v-progress-linear>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";

const photos = ref([]);
const uploading = ref(false);
const downloading = ref(false);
const deletingIds = ref([]);
const deletingAll = ref(false);
const isDragOver = ref(false);
const imageDialog = ref(false);
const selectedPhoto = ref(null);

// Progress tracking variables
const uploadProgress = ref({
    show: false,
    processed: 0,
    total: 0,
    filename: "",
    percentage: 0,
});
let progressInterval = null;

const snackbar = ref({
    show: false,
    message: "",
    color: "success",
});

const showSnackbar = (message, color = "success") => {
    snackbar.value = { show: true, message, color };
};

const loadPhotos = async () => {
    try {
        const response = await fetch("/edit-foto/photos");

        if (response.status === 401) {
            // Redirect to login if not authenticated
            window.location.href = "/edit-foto/login";
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        photos.value = data.photos;
    } catch (error) {
        console.error("Error loading photos:", error);
        showSnackbar("Failed to load photos", "error");
    }
};

const startProgressTracking = () => {
    uploadProgress.value.show = true;

    progressInterval = setInterval(async () => {
        try {
            const response = await fetch("/edit-foto/progress");
            if (response.ok) {
                const data = await response.json();
                if (data) {
                    uploadProgress.value.processed = data.current;
                    uploadProgress.value.total = data.total;
                    uploadProgress.value.filename = data.filename;
                    uploadProgress.value.percentage = Math.round(
                        (data.current / data.total) * 100,
                    );
                }
            }
        } catch (error) {
            console.error("Progress tracking error:", error);
        }
    }, 500); // Check every 500ms
};

const stopProgressTracking = () => {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
    uploadProgress.value.show = false;
    uploadProgress.value.processed = 0;
    uploadProgress.value.total = 0;
    uploadProgress.value.filename = "";
    uploadProgress.value.percentage = 0;
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    uploadFiles(files);
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragOver.value = false;
    const files = Array.from(event.dataTransfer.files);
    uploadFiles(files);
};

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

    const formData = new FormData();
    files.forEach((file) => {
        formData.append("photos[]", file);
    });

    try {
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
            // Redirect to login if not authenticated
            window.location.href = "/edit-foto/login";
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            photos.value.push(...data.photos);
            showSnackbar(
                `${data.photos.length} photos uploaded and edited successfully!`,
            );
        } else {
            showSnackbar("Upload failed", "error");
        }
    } catch (error) {
        console.error("Upload error:", error);
        showSnackbar("Upload failed", "error");
    } finally {
        stopProgressTracking();
        uploading.value = false;
    }
};

const deletePhoto = async (photoId) => {
    deletingIds.value.push(photoId);

    try {
        const response = await fetch(`/edit-foto/photos/${photoId}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        });

        if (response.ok) {
            photos.value = photos.value.filter((p) => p.id !== photoId);
            showSnackbar("Photo deleted");
        } else {
            showSnackbar("Delete failed", "error");
        }
    } catch (error) {
        console.error("Delete error:", error);
        showSnackbar("Delete failed", "error");
    }

    deletingIds.value = deletingIds.value.filter((id) => id !== photoId);
};

const deleteAllPhotos = async () => {
    if (photos.value.length === 0) return;

    if (
        !confirm(
            `Are you sure you want to delete all ${photos.value.length} photos?`,
        )
    ) {
        return;
    }

    deletingAll.value = true;

    try {
        // Delete all photos one by one
        const deletePromises = photos.value.map((photo) =>
            fetch(`/edit-foto/photos/${photo.id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            }),
        );

        const responses = await Promise.all(deletePromises);
        const successCount = responses.filter((response) => response.ok).length;

        if (successCount === photos.value.length) {
            photos.value = [];
            showSnackbar(`All ${successCount} photos deleted successfully!`);
        } else {
            showSnackbar(
                `${successCount} of ${photos.value.length} photos deleted`,
                "warning",
            );
            // Reload photos to get current state
            loadPhotos();
        }
    } catch (error) {
        console.error("Delete all error:", error);
        showSnackbar("Failed to delete all photos", "error");
    }

    deletingAll.value = false;
};

const downloadAll = async () => {
    if (photos.value.length === 0) return;

    downloading.value = true;

    try {
        const response = await fetch("/edit-foto/download");

        if (response.status === 401) {
            // Redirect to login if not authenticated
            window.location.href = "/edit-foto/login";
            return;
        }

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `edited_photos_${new Date().toISOString().slice(0, 10)}.zip`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            showSnackbar("Download started!");
        } else {
            showSnackbar("Download failed", "error");
        }
    } catch (error) {
        console.error("Download error:", error);
        showSnackbar("Download failed", "error");
    }

    downloading.value = false;
};

const openImageDialog = (photo) => {
    selectedPhoto.value = photo;
    imageDialog.value = true;
};

const getChipLabel = (key, value) => {
    if (key === "temperature") {
        if (value > 0) {
            return `warm +${value}`;
        } else {
            return `cool ${value}`;
        }
    }
    if (key === "crop") {
        return `cropped ${value}%`;
    }
    return `${key}: ${value}`;
};

const getChipColor = (key, value) => {
    if (key === "temperature") {
        return value > 0 ? "orange" : "blue";
    }
    if (key === "crop") {
        return "purple";
    }
    return "primary";
};

onMounted(() => {
    loadPhotos();
});
</script>

<style scoped>
.drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.drop-zone:hover,
.drop-zone.drag-over {
    border-color: #1976d2;
    background-color: rgba(25, 118, 210, 0.05);
}

.photo-card {
    position: relative;
    transition: transform 0.2s ease;
}

.photo-card:hover {
    transform: translateY(-2px);
}

.image-container {
    position: relative;
}

.delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 2;
}

.cursor-pointer {
    cursor: pointer;
}

/* Progress dialog positioning */
.progress-dialog .v-overlay__content {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    top: auto !important;
    left: auto !important;
    margin: 0 !important;
    transform: none !important;
    animation: slideInUp 0.3s ease-out;
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>




