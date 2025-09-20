<script setup>
import {
  ref, computed, onMounted, reactive, inject,
} from 'vue';
import { router } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const $toast = inject('$toast');

const props = defineProps({
  categories: Object,
  types: Object,
  statuses: Object,
});

const form = reactive({
  title: '',
  content: '',
  category: 'pajak',
  type: 'faq',
  status: 'published',
  source_type: 'manual',
  priority: 3,
  tags: '',
  file: null,
  is_active: true,
});

const formState = reactive({
  processing: false,
  errors: {},
  success: false,
});

const isClient = ref(false);
onMounted(() => {
  isClient.value = true;
});

const showAdvanced = ref(false);
const dragActive = ref(false);
const filePreview = ref(null);
const allowedFileTypes = ['pdf', 'doc', 'docx', 'txt', 'md'];

const maxFileSize = 10; // MB
const maxFileSizeBytes = maxFileSize * 1024 * 1024;

const categoryItems = computed(() => {
  const entries = props.categories ? Object.entries(props.categories) : [];
  return entries.map(([key, value]) => ({ title: value, value: key }));
});

const typeItems = computed(() => {
  const entries = props.types ? Object.entries(props.types) : [];
  return entries.map(([key, value]) => ({ title: value, value: key }));
});

const statusItems = computed(() => {
  const entries = props.statuses ? Object.entries(props.statuses) : [];
  return entries.map(([key, value]) => ({ title: value, value: key }));
});

const sourceTypeItems = [
  { title: 'Manual Entry', value: 'manual' },
  { title: 'File Upload', value: 'file' },
];

const priorityItems = [
  { title: 'Low', value: 1 },
  { title: 'Normal', value: 2 },
  { title: 'High', value: 3 },
  { title: 'Critical', value: 4 },
];

// No excerpt generation; using rich text content directly

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
  const fileExtension = file.name.split('.').pop().toLowerCase();
  if (!allowedFileTypes.includes(fileExtension)) {
    alert(`File type not allowed. Please use: ${allowedFileTypes.join(', ')}`);
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
    size: `${(file.size / 1024 / 1024).toFixed(2)} MB`,
    type: fileExtension.toUpperCase(),
  };

  // Auto-fill title if empty
  if (!form.title) {
    form.title = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
  }
};

const removeFile = () => {
  form.file = null;
  filePreview.value = null;
};

const parseTags = (value) => {
  if (!value || typeof value !== 'string') return [];
  return value
    .split(',')
    .map((t) => t.trim())
    .filter(Boolean);
};

const submit = async () => {
  formState.processing = true;
  formState.errors = {};

  const needsFormData = form.source_type === 'file' && !!form.file;

  try {
    // Prepare form data
    const formData = new FormData();

    // Add all form fields
    formData.append('title', form.title);
    formData.append('content', form.content);
    formData.append('category', form.category);
    formData.append('type', form.type);
    formData.append('status', form.status);
    formData.append('source_type', form.source_type);
    formData.append('priority', form.priority);
    formData.append('is_active', form.is_active ? '1' : '0');

    // Handle tags
    const tags = parseTags(form.tags);
    tags.forEach((tag, index) => {
      formData.append(`tags[${index}]`, tag);
    });

    // Add file if present
    if (form.file) {
      formData.append('file', form.file);
    }

    // Get CSRF token
    const csrfToken = document.head.querySelector(
      'meta[name="csrf-token"]',
    )?.content;

    const response = await axios.post('/knowledge-base', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
    });

    if (response.data.success) {
      formState.success = true;
      // Show success toast
      $toast.success(
        response.data.message || 'Knowledge base entry created successfully!',
      );

      // Navigate to index page using SPA
      router.visit('/knowledge-base');
    }
  } catch (error) {
    console.error('Form submission error:', error);

    if (error.response) {
      if (error.response.status === 422) {
        // Validation errors
        formState.errors = error.response.data.errors || {};
        $toast.error('Please check the form for validation errors.');
      } else if (error.response.status === 419) {
        // CSRF token expired
        $toast.error('Session expired. Please refresh the page and try again.');
        console.log('CSRF token expired, refreshing page...');
        window.location.reload();
      } else {
        formState.errors = {
          general: ['An error occurred while saving the data.'],
        };
        $toast.error('An error occurred while saving the data.');
      }
    } else {
      formState.errors = { general: ['Network error occurred.'] };
      $toast.error('Network error occurred while saving.');
    }
  } finally {
    formState.processing = false;
  }
};

const cancel = () => {
  window.history.back();
};
</script>

<template>
  <AppLayout title="Create Knowledge Base Entry">
    <div class="pa-0 relative">
      <Transition name="fade">
        <div
          v-if="formState.processing"
          class="absolute inset-0 bg-white/60 backdrop-blur-sm z-20 flex items-center justify-center"
        >
          <div class="flex flex-col items-center gap-3">
            <svg
              class="animate-spin h-8 w-8 text-primary"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
              />
            </svg>
            <span class="text-sm text-gray-600">Saving…</span>
          </div>
        </div>
      </Transition>
      <!-- Header -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText class="pa-6">
              <VRow align="center">
                <VCol cols="12" md="8">
                  <h1 class="text-h4 font-weight-bold text-primary mb-2">
                    <VIcon class="mr-3" size="36">mdi-plus-circle</VIcon>
                    Create New Knowledge Base Entry
                  </h1>
                  <p class="text-body-1 text-medium-emphasis">
                    Add new content to your AI knowledge base
                  </p>
                </VCol>
                <VCol cols="12" md="4" class="text-right">
                  <VBtn variant="outlined" @click="cancel" class="mr-3">
                    Cancel
                  </VBtn>
                  <VBtn
                    color="primary"
                    @click="submit"
                    :loading="form.processing"
                    :disabled="
                      !form.title ||
                      (form.source_type === 'manual' && !form.content)
                    "
                  >
                    Save Entry
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <form @submit.prevent="submit">
        <VRow>
          <!-- Main Content -->
          <VCol cols="12" md="8">
            <VCard elevation="2" class="mb-4">
              <VCardTitle class="bg-primary text-white">
                <VIcon class="mr-2">mdi-file-document-edit</VIcon>
                Content Details
              </VCardTitle>
              <VCardText class="pa-6">
                <!-- Source Type Selection -->
                <VRow class="mb-4">
                  <VCol cols="12">
                    <VRadioGroup
                      v-model="form.source_type"
                      inline
                      :error-messages="formState.errors.source_type"
                    >
                      <template #label>
                        <span class="text-subtitle-1 font-weight-medium"
                          >Content Source</span
                        >
                      </template>
                      <VRadio
                        v-for="item in sourceTypeItems"
                        :key="item.value"
                        :label="item.title"
                        :value="item.value"
                      ></VRadio>
                    </VRadioGroup>
                  </VCol>
                </VRow>

                <!-- File Upload Section -->
                <div v-if="form.source_type === 'file'" class="mb-6">
                  <VCard
                    variant="outlined"
                    :class="{
                      'border-primary': dragActive,
                    }"
                    @dragover.prevent="dragActive = true"
                    @dragleave.prevent="dragActive = false"
                    @drop="handleFileDrop"
                  >
                    <VCardText class="text-center pa-8">
                      <div v-if="!filePreview">
                        <VIcon size="64" color="grey">mdi-cloud-upload</VIcon>
                        <h3 class="text-h6 mt-3">Upload File</h3>
                        <p class="text-body-2 text-medium-emphasis mb-4">
                          Drag and drop your file here or click to browse
                        </p>
                        <p class="text-caption text-medium-emphasis mb-4">
                          Supported formats:
                          {{ allowedFileTypes.join(', ').toUpperCase() }}
                          <br />
                          Maximum size:
                          {{ maxFileSize }}MB
                        </p>
                        <VBtn
                          color="primary"
                          variant="outlined"
                          @click="$refs.fileInput.click()"
                        >
                          Choose File
                        </VBtn>
                        <input
                          ref="fileInput"
                          type="file"
                          hidden
                          :accept="'.' + allowedFileTypes.join(',.')"
                          @change="handleFileSelect"
                        />
                      </div>
                      <div v-else>
                        <VIcon size="64" color="success">mdi-file-check</VIcon>
                        <h3 class="text-h6 mt-3">
                          {{ filePreview.name }}
                        </h3>
                        <p class="text-body-2 text-medium-emphasis">
                          {{ filePreview.type }} •
                          {{ filePreview.size }}
                        </p>
                        <VBtn
                          color="error"
                          variant="outlined"
                          @click="removeFile"
                          class="mt-3"
                        >
                          Remove File
                        </VBtn>
                      </div>
                    </VCardText>
                  </VCard>
                  <VAlert
                    v-if="formState.errors.file"
                    type="error"
                    class="mt-3"
                  >
                    {{ formState.errors.file }}
                  </VAlert>
                </div>

                <!-- Title -->
                <VTextField
                  v-model="form.title"
                  label="Title *"
                  variant="outlined"
                  :error-messages="formState.errors.title"
                  class="mb-4"
                  prepend-inner-icon="mdi-format-title"
                ></VTextField>

                <!-- Content (for manual entry) -->
                <div v-if="form.source_type === 'manual'">
                  <QuillEditor
                    v-if="isClient"
                    v-model:content="form.content"
                    content-type="html"
                    theme="snow"
                    toolbar="full"
                    style="
                      min-height: 280px;
                      background: white;
                      border-radius: 8px;
                    "
                  />
                  <div
                    v-if="formState.errors.content"
                    class="text-error text-caption mt-2"
                  >
                    {{ formState.errors.content }}
                  </div>
                </div>
              </VCardText>
            </VCard>

            <!-- Advanced Options -->
            <VCard elevation="2" v-if="showAdvanced">
              <VCardTitle class="bg-blue-grey text-white">
                <VIcon class="mr-2">mdi-cog</VIcon>
                Advanced Options
              </VCardTitle>
              <VCardText class="pa-6">
                <VRow>
                  <VCol cols="12" md="6">
                    <VSelect
                      v-model="form.priority"
                      :items="priorityItems"
                      label="Priority"
                      variant="outlined"
                      :error-messages="formState.errors.priority"
                    ></VSelect>
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="form.tags"
                      label="Tags"
                      variant="outlined"
                      :error-messages="formState.errors.tags"
                      hint="Separate tags with commas"
                      persistent-hint
                      prepend-inner-icon="mdi-tag-multiple"
                    ></VTextField>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Sidebar -->
          <VCol cols="12" md="4">
            <!-- Publishing Options -->
            <VCard elevation="2" class="mb-4">
              <VCardTitle class="bg-success text-white">
                <VIcon class="mr-2">mdi-publish</VIcon>
                Publishing
              </VCardTitle>
              <VCardText class="pa-6">
                <VSelect
                  v-model="form.status"
                  :items="statusItems"
                  label="Status"
                  variant="outlined"
                  :error-messages="formState.errors.status"
                  class="mb-4"
                ></VSelect>

                <VSwitch
                  v-model="form.is_active"
                  label="Active"
                  color="success"
                  :error-messages="formState.errors.is_active"
                  hide-details
                ></VSwitch>
              </VCardText>
            </VCard>

            <!-- Categorization -->
            <VCard elevation="2" class="mb-4">
              <VCardTitle class="bg-orange text-white">
                <VIcon class="mr-2">mdi-folder</VIcon>
                Categorization
              </VCardTitle>
              <VCardText class="pa-6">
                <VSelect
                  v-model="form.category"
                  :items="categoryItems"
                  label="Category"
                  variant="outlined"
                  :error-messages="formState.errors.category"
                  class="mb-4"
                ></VSelect>

                <VSelect
                  v-model="form.type"
                  :items="typeItems"
                  label="Type"
                  variant="outlined"
                  :error-messages="formState.errors.type"
                ></VSelect>
              </VCardText>
            </VCard>

            <!-- Actions -->
            <VCard elevation="2">
              <VCardTitle class="bg-blue text-white">
                <VIcon class="mr-2">mdi-lightning-bolt</VIcon>
                Actions
              </VCardTitle>
              <VCardText class="pa-6">
                <VBtn
                  @click="showAdvanced = !showAdvanced"
                  variant="outlined"
                  block
                  class="mb-3"
                >
                  <VIcon class="mr-2">
                    {{ showAdvanced ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
                  </VIcon>
                  {{ showAdvanced ? 'Hide' : 'Show' }}
                  Advanced Options
                </VBtn>

                <VBtn
                  color="primary"
                  @click="submit"
                  :loading="formState.processing"
                  :disabled="
                    !form.title ||
                    (form.source_type === 'manual' && !form.content)
                  "
                  block
                  size="large"
                >
                  <VIcon class="mr-2">mdi-content-save</VIcon>
                  Save Entry
                </VBtn>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </form>
    </div>
  </AppLayout>
</template>

<style scoped>
.border-primary {
  border-color: rgb(var(--v-theme-primary)) !important;
  border-width: 2px !important;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
