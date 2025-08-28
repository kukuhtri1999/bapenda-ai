<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    categories: Object,
    types: Object,
    statuses: Object,
});

const form = useForm({
    title: "",
    content: "",
    excerpt: "",
    category: "",
    type: "",
    status: "draft",
    source_type: "manual",
    priority: 1,
    tags: "",
    file: null,
    is_active: true,
});

const showAdvanced = ref(false);
const dragActive = ref(false);
const filePreview = ref(null);
const allowedFileTypes = ["pdf", "doc", "docx", "txt", "md"];

const maxFileSize = 10; // MB
const maxFileSizeBytes = maxFileSize * 1024 * 1024;

const categoryItems = computed(() =>
    Object.entries(props.categories).map(([key, value]) => ({
        title: value,
        value: key,
    })),
);

const typeItems = computed(() =>
    Object.entries(props.types).map(([key, value]) => ({
        title: value,
        value: key,
    })),
);

const statusItems = computed(() =>
    Object.entries(props.statuses).map(([key, value]) => ({
        title: value,
        value: key,
    })),
);

const sourceTypeItems = [
    { title: "Manual Entry", value: "manual" },
    { title: "File Upload", value: "file" },
];

const priorityItems = [
    { title: "Low", value: 1 },
    { title: "Normal", value: 2 },
    { title: "High", value: 3 },
    { title: "Critical", value: 4 },
];

// Generate excerpt automatically from content
watch(
    () => form.content,
    (newContent) => {
        if (newContent && !form.excerpt) {
            const words = newContent.replace(/<[^>]*>/g, "").split(" ");
            form.excerpt =
                words.slice(0, 30).join(" ") + (words.length > 30 ? "..." : "");
        }
    },
);

// Handle file upload
const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        processFile(file);
    }
};

const handleFileDrop = (event) => {
    event.preventDefault();
    dragActive.value = false;

    const file = event.dataTransfer.files[0];
    if (file) {
        processFile(file);
    }
};

const processFile = (file) => {
    // Validate file type
    const fileExtension = file.name.split(".").pop().toLowerCase();
    if (!allowedFileTypes.includes(fileExtension)) {
        alert(
            `File type not allowed. Please use: ${allowedFileTypes.join(", ")}`,
        );
        return;
    }

    // Validate file size
    if (file.size > maxFileSizeBytes) {
        alert(`File size too large. Maximum size is ${maxFileSize}MB`);
        return;
    }

    form.file = file;

    // Create preview
    filePreview.value = {
        name: file.name,
        size: (file.size / 1024 / 1024).toFixed(2) + " MB",
        type: fileExtension.toUpperCase(),
    };

    // Auto-fill title if empty
    if (!form.title) {
        form.title = file.name.replace(/\.[^/.]+$/, "").replace(/[-_]/g, " ");
    }
};

const removeFile = () => {
    form.file = null;
    filePreview.value = null;
};

const submit = () => {
    // Convert tags string to array
    if (form.tags) {
        form.tags = form.tags
            .split(",")
            .map((tag) => tag.trim())
            .filter((tag) => tag);
    }

    form.post(route("knowledge-base.store"), {
        preserveScroll: true,
        onSuccess: () => {
            // Form will redirect on success
        },
    });
};

const cancel = () => {
    window.history.back();
};
</script>

<template>
    <AppLayout title="Create Knowledge Base Entry">
        <div class="pa-0">
            <!-- Header -->
            <v-row class="mb-6">
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-card-text class="pa-6">
                            <v-row align="center">
                                <v-col cols="12" md="8">
                                    <h1
                                        class="text-h4 font-weight-bold text-primary mb-2"
                                    >
                                        <v-icon class="mr-3" size="36"
                                            >mdi-plus-circle</v-icon
                                        >
                                        Create New Knowledge Base Entry
                                    </h1>
                                    <p class="text-body-1 text-medium-emphasis">
                                        Add new content to your AI knowledge
                                        base
                                    </p>
                                </v-col>
                                <v-col cols="12" md="4" class="text-right">
                                    <v-btn
                                        variant="outlined"
                                        @click="cancel"
                                        class="mr-3"
                                    >
                                        Cancel
                                    </v-btn>
                                    <v-btn
                                        color="primary"
                                        @click="submit"
                                        :loading="form.processing"
                                        :disabled="!form.title || !form.content"
                                    >
                                        Save Entry
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <form @submit.prevent="submit">
                <v-row>
                    <!-- Main Content -->
                    <v-col cols="12" md="8">
                        <v-card elevation="2" class="mb-4">
                            <v-card-title class="bg-primary text-white">
                                <v-icon class="mr-2"
                                    >mdi-file-document-edit</v-icon
                                >
                                Content Details
                            </v-card-title>
                            <v-card-text class="pa-6">
                                <!-- Source Type Selection -->
                                <v-row class="mb-4">
                                    <v-col cols="12">
                                        <v-radio-group
                                            v-model="form.source_type"
                                            inline
                                            :error-messages="
                                                form.errors.source_type
                                            "
                                        >
                                            <template #label>
                                                <span
                                                    class="text-subtitle-1 font-weight-medium"
                                                    >Content Source</span
                                                >
                                            </template>
                                            <v-radio
                                                v-for="item in sourceTypeItems"
                                                :key="item.value"
                                                :label="item.title"
                                                :value="item.value"
                                            ></v-radio>
                                        </v-radio-group>
                                    </v-col>
                                </v-row>

                                <!-- File Upload Section -->
                                <div
                                    v-if="form.source_type === 'file'"
                                    class="mb-6"
                                >
                                    <v-card
                                        variant="outlined"
                                        :class="{
                                            'border-primary': dragActive,
                                        }"
                                        @dragover.prevent="dragActive = true"
                                        @dragleave.prevent="dragActive = false"
                                        @drop="handleFileDrop"
                                    >
                                        <v-card-text class="text-center pa-8">
                                            <div v-if="!filePreview">
                                                <v-icon size="64" color="grey"
                                                    >mdi-cloud-upload</v-icon
                                                >
                                                <h3 class="text-h6 mt-3">
                                                    Upload File
                                                </h3>
                                                <p
                                                    class="text-body-2 text-medium-emphasis mb-4"
                                                >
                                                    Drag and drop your file here
                                                    or click to browse
                                                </p>
                                                <p
                                                    class="text-caption text-medium-emphasis mb-4"
                                                >
                                                    Supported formats:
                                                    {{
                                                        allowedFileTypes
                                                            .join(", ")
                                                            .toUpperCase()
                                                    }}
                                                    <br />
                                                    Maximum size:
                                                    {{ maxFileSize }}MB
                                                </p>
                                                <v-btn
                                                    color="primary"
                                                    variant="outlined"
                                                    @click="
                                                        $refs.fileInput.click()
                                                    "
                                                >
                                                    Choose File
                                                </v-btn>
                                                <input
                                                    ref="fileInput"
                                                    type="file"
                                                    hidden
                                                    :accept="
                                                        '.' +
                                                        allowedFileTypes.join(
                                                            ',.',
                                                        )
                                                    "
                                                    @change="handleFileSelect"
                                                />
                                            </div>
                                            <div v-else>
                                                <v-icon
                                                    size="64"
                                                    color="success"
                                                    >mdi-file-check</v-icon
                                                >
                                                <h3 class="text-h6 mt-3">
                                                    {{ filePreview.name }}
                                                </h3>
                                                <p
                                                    class="text-body-2 text-medium-emphasis"
                                                >
                                                    {{ filePreview.type }} •
                                                    {{ filePreview.size }}
                                                </p>
                                                <v-btn
                                                    color="error"
                                                    variant="outlined"
                                                    @click="removeFile"
                                                    class="mt-3"
                                                >
                                                    Remove File
                                                </v-btn>
                                            </div>
                                        </v-card-text>
                                    </v-card>
                                    <v-alert
                                        v-if="form.errors.file"
                                        type="error"
                                        class="mt-3"
                                    >
                                        {{ form.errors.file }}
                                    </v-alert>
                                </div>

                                <!-- Title -->
                                <v-text-field
                                    v-model="form.title"
                                    label="Title *"
                                    variant="outlined"
                                    :error-messages="form.errors.title"
                                    class="mb-4"
                                    prepend-inner-icon="mdi-format-title"
                                ></v-text-field>

                                <!-- Content (for manual entry) -->
                                <div v-if="form.source_type === 'manual'">
                                    <v-textarea
                                        v-model="form.content"
                                        label="Content *"
                                        variant="outlined"
                                        :error-messages="form.errors.content"
                                        rows="12"
                                        class="mb-4"
                                        prepend-inner-icon="mdi-text"
                                    ></v-textarea>
                                </div>

                                <!-- Excerpt -->
                                <v-textarea
                                    v-model="form.excerpt"
                                    label="Excerpt"
                                    variant="outlined"
                                    :error-messages="form.errors.excerpt"
                                    rows="3"
                                    hint="Brief summary of the content (auto-generated if left empty)"
                                    persistent-hint
                                    prepend-inner-icon="mdi-text-short"
                                ></v-textarea>
                            </v-card-text>
                        </v-card>

                        <!-- Advanced Options -->
                        <v-card elevation="2" v-if="showAdvanced">
                            <v-card-title class="bg-blue-grey text-white">
                                <v-icon class="mr-2">mdi-cog</v-icon>
                                Advanced Options
                            </v-card-title>
                            <v-card-text class="pa-6">
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.priority"
                                            :items="priorityItems"
                                            label="Priority"
                                            variant="outlined"
                                            :error-messages="
                                                form.errors.priority
                                            "
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.tags"
                                            label="Tags"
                                            variant="outlined"
                                            :error-messages="form.errors.tags"
                                            hint="Separate tags with commas"
                                            persistent-hint
                                            prepend-inner-icon="mdi-tag-multiple"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Sidebar -->
                    <v-col cols="12" md="4">
                        <!-- Publishing Options -->
                        <v-card elevation="2" class="mb-4">
                            <v-card-title class="bg-success text-white">
                                <v-icon class="mr-2">mdi-publish</v-icon>
                                Publishing
                            </v-card-title>
                            <v-card-text class="pa-6">
                                <v-select
                                    v-model="form.status"
                                    :items="statusItems"
                                    label="Status"
                                    variant="outlined"
                                    :error-messages="form.errors.status"
                                    class="mb-4"
                                ></v-select>

                                <v-switch
                                    v-model="form.is_active"
                                    label="Active"
                                    color="success"
                                    :error-messages="form.errors.is_active"
                                    hide-details
                                ></v-switch>
                            </v-card-text>
                        </v-card>

                        <!-- Categorization -->
                        <v-card elevation="2" class="mb-4">
                            <v-card-title class="bg-orange text-white">
                                <v-icon class="mr-2">mdi-folder</v-icon>
                                Categorization
                            </v-card-title>
                            <v-card-text class="pa-6">
                                <v-select
                                    v-model="form.category"
                                    :items="categoryItems"
                                    label="Category"
                                    variant="outlined"
                                    :error-messages="form.errors.category"
                                    class="mb-4"
                                ></v-select>

                                <v-select
                                    v-model="form.type"
                                    :items="typeItems"
                                    label="Type"
                                    variant="outlined"
                                    :error-messages="form.errors.type"
                                ></v-select>
                            </v-card-text>
                        </v-card>

                        <!-- Actions -->
                        <v-card elevation="2">
                            <v-card-title class="bg-blue text-white">
                                <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
                                Actions
                            </v-card-title>
                            <v-card-text class="pa-6">
                                <v-btn
                                    @click="showAdvanced = !showAdvanced"
                                    variant="outlined"
                                    block
                                    class="mb-3"
                                >
                                    <v-icon class="mr-2">
                                        {{
                                            showAdvanced
                                                ? "mdi-chevron-up"
                                                : "mdi-chevron-down"
                                        }}
                                    </v-icon>
                                    {{
                                        showAdvanced ? "Hide" : "Show"
                                    }}
                                    Advanced Options
                                </v-btn>

                                <v-btn
                                    color="primary"
                                    @click="submit"
                                    :loading="form.processing"
                                    :disabled="
                                        !form.title ||
                                        (form.source_type === 'manual' &&
                                            !form.content)
                                    "
                                    block
                                    size="large"
                                >
                                    <v-icon class="mr-2"
                                        >mdi-content-save</v-icon
                                    >
                                    Save Entry
                                </v-btn>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.border-primary {
    border-color: rgb(var(--v-theme-primary)) !important;
    border-width: 2px !important;
}
</style>
