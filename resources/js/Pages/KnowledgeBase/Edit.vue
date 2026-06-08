<script setup>
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';
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
  knowledgeBase: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const form = reactive({
  title: props.knowledgeBase.title,
  content: props.knowledgeBase.content,
  ai_instructions: props.knowledgeBase.ai_instructions || '',
  category: props.knowledgeBase.category,
  type: props.knowledgeBase.type,
  status: props.knowledgeBase.status,
  source_type: props.knowledgeBase.source_type,
  priority: props.knowledgeBase.priority,
  tags: Array.isArray(props.knowledgeBase.tags)
    ? props.knowledgeBase.tags.join(', ')
    : '',
  file: null,
  is_active: props.knowledgeBase.is_active,
});

const formState = reactive({
  processing: false,
  errors: {},
  success: false,
});

const showAdvanced = ref(false);
const dragActive = ref(false);
const filePreview = ref(null);
const replaceFile = ref(false);
const allowedFileTypes = ['pdf', 'doc', 'docx', 'txt', 'md'];

const isClient = ref(false);
onMounted(() => {
  isClient.value = true;
});

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

// No excerpt handling; using full rich text content

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

  replaceFile.value = true;
};

const removeFile = () => {
  form.file = null;
  filePreview.value = null;
  replaceFile.value = false;
};

const downloadCurrentFile = () => {
  window.open(route('knowledge-base.download', props.knowledgeBase.id));
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

  try {
    // Prepare form data
    const formData = new FormData();

    // Add all form fields
    formData.append('title', form.title);
    formData.append('content', form.content);
    formData.append('ai_instructions', form.ai_instructions || '');
    formData.append('category', form.category);
    formData.append('type', form.type);
    formData.append('status', form.status);
    formData.append('source_type', form.source_type);
    formData.append('priority', form.priority);
    formData.append('is_active', form.is_active ? '1' : '0');
    formData.append('_method', 'PUT'); // Laravel method spoofing

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

    const response = await axios.post(
      `/knowledge-base/${props.knowledgeBase.id}`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json',
        },
      },
    );

    if (response.data.success) {
      formState.success = true;
      // Show success toast
      $toast.success(
        response.data.message || 'Knowledge base entry updated successfully!',
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
          general: ['An error occurred while updating the data.'],
        };
        $toast.error('An error occurred while updating the data.');
      }
    } else {
      formState.errors = { general: ['Network error occurred.'] };
      $toast.error('Network error occurred while updating.');
    }
  } finally {
    formState.processing = false;
  }
};

const cancel = () => {
  window.history.back();
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

const kbEditSteps = [
  { title: 'Edit Knowledge Base', intro: 'Halaman ini digunakan untuk memperbarui entri yang sudah ada di basis pengetahuan SALMA AI.' },
  { element: '#tour-kbedit-title', title: 'Judul', intro: 'Perbarui judul entri ini jika diperlukan. Pastikan judul tetap deskriptif dan relevan.' },
  { element: '#tour-kbedit-category', title: 'Kategori', intro: 'Ubah kategori jika informasi ini lebih cocok diklasifikasikan secara berbeda.' },
  { element: '#tour-kbedit-status', title: 'Status Aktif/Draft', intro: 'Ubah status menjadi "Active" agar konten digunakan AI, atau "Draft" untuk menyembunyikan sementara dari sistem AI.' },
  { title: 'Simpan Perubahan', intro: 'Setelah selesai mengedit, klik tombol "Update Knowledge Base" di bagian bawah. AI akan langsung menggunakan data terbaru setelah tersimpan.' },
];
const { startTour } = useTour(kbEditSteps);

</script>

<template>
  <AppLayout :title="`Edit: ${knowledgeBase.title}`">
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
                    <VIcon class="mr-3" size="36">mdi-pencil</VIcon>
                    Edit Knowledge Base Entry
                  </h1>
                  <p class="text-body-1 text-medium-emphasis">
                    Modify existing knowledge base content
                  </p>
                  <div class="d-flex align-center gap-4 mt-3">
                    <VChip variant="outlined" size="small">
                      <VIcon class="mr-1" size="small">mdi-eye</VIcon>
                      {{ knowledgeBase.view_count || 0 }}
                      views
                    </VChip>
                    <VChip variant="outlined" size="small">
                      <VIcon class="mr-1" size="small">mdi-calendar</VIcon>
                      Created
                      {{ formatDate(knowledgeBase.created_at) }}
                    </VChip>
                    <VChip
                      v-if="
                        knowledgeBase.updated_at !== knowledgeBase.created_at
                      "
                      variant="outlined"
                      size="small"
                    >
                      <VIcon class="mr-1" size="small">mdi-update</VIcon>
                      Updated
                      {{ formatDate(knowledgeBase.updated_at) }}
                    </VChip>
                  </div>
                </VCol>
                <VCol cols="12" md="4" class="text-right">
                  <VBtn variant="outlined" @click="cancel" class="mr-3">
                    Cancel
                  </VBtn>
                  <VBtn
                    color="primary"
                    @click="submit"
                    :loading="formState.processing"
                    :disabled="
                      !form.title ||
                      (form.source_type === 'manual' && !form.content)
                    "
                  >
                    Update Entry
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
                <!-- Source Type Display -->
                <VRow class="mb-4">
                  <VCol cols="12">
                    <div class="text-subtitle-1 font-weight-medium mb-2">
                      Content Source
                    </div>
                    <VChip
                      :color="form.source_type === 'file' ? 'blue' : 'green'"
                      variant="tonal"
                    >
                      <VIcon
                        :icon="
                          form.source_type === 'file'
                            ? 'mdi-file-document'
                            : 'mdi-keyboard'
                        "
                        class="mr-2"
                      ></VIcon>
                      {{
                        form.source_type === 'file'
                          ? 'File Upload'
                          : 'Manual Entry'
                      }}
                    </VChip>
                  </VCol>
                </VRow>

                <!-- Current File Information -->
                <div
                  v-if="form.source_type === 'file' && knowledgeBase.file_path"
                  class="mb-6"
                >
                  <VCard variant="outlined" color="blue-grey-lighten-5">
                    <VCardText class="pa-4">
                      <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center">
                          <VIcon color="blue" class="mr-3" size="large"
                            >mdi-file-document</VIcon
                          >
                          <div>
                            <div class="font-weight-medium">
                              {{ knowledgeBase.file_name || 'Current File' }}
                            </div>
                            <div class="text-caption text-medium-emphasis">
                              {{
                                knowledgeBase.file_size
                                  ? (
                                      knowledgeBase.file_size /
                                      1024 /
                                      1024
                                    ).toFixed(2) + ' MB'
                                  : ''
                              }}
                              {{
                                knowledgeBase.file_type
                                  ? ' • ' +
                                    knowledgeBase.file_type.toUpperCase()
                                  : ''
                              }}
                            </div>
                          </div>
                        </div>
                        <div>
                          <VBtn
                            color="blue"
                            variant="outlined"
                            size="small"
                            @click="downloadCurrentFile"
                            class="mr-2"
                          >
                            <VIcon class="mr-1">mdi-download</VIcon>
                            Download
                          </VBtn>
                          <VBtn
                            color="orange"
                            variant="outlined"
                            size="small"
                            @click="replaceFile = !replaceFile"
                          >
                            <VIcon class="mr-1">mdi-file-replace</VIcon>
                            Replace
                          </VBtn>
                        </div>
                      </div>
                    </VCardText>
                  </VCard>
                </div>

                <!-- File Upload Section (for replacement) -->
                <div
                  v-if="form.source_type === 'file' && replaceFile"
                  class="mb-6"
                >
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
                        <VIcon size="64" color="orange">mdi-file-replace</VIcon>
                        <h3 class="text-h6 mt-3">Replace Current File</h3>
                        <p class="text-body-2 text-medium-emphasis mb-4">
                          Drag and drop your new file here or click to browse
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
                          Choose New File
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
                  id="tour-kbedit-title"
                  label="Title *"
                  variant="outlined"
                  :error-messages="formState.errors.title"
                  class="mb-4"
                  prepend-inner-icon="mdi-format-title"
                ></VTextField>

                <!-- Content editor — shown for both manual and file-based entries -->
                <div class="mb-2">
                  <div class="d-flex align-center justify-space-between mb-2">
                    <span
                      class="text-subtitle-2 font-weight-medium text-medium-emphasis"
                    >
                      Content
                      <span
                        v-if="form.source_type === 'file'"
                        class="text-caption ml-1 text-info"
                      >
                        (extracted from uploaded file — you may edit freely)
                      </span>
                    </span>
                  </div>
                  <VAlert
                    v-if="form.source_type === 'file'"
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mb-3"
                    icon="mdi-information-outline"
                  >
                    This content was automatically extracted from the uploaded
                    file. You can edit it here and the changes will be saved to
                    the knowledge base and re-indexed in the vector database.
                  </VAlert>
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

                <!-- AI Instructions / RAG Hints -->
                <div class="mt-4">
                  <VTextarea
                    v-model="form.ai_instructions"
                    label="Instruksi AI / RAG Hints (Opsional)"
                    variant="outlined"
                    rows="3"
                    :error-messages="formState.errors.ai_instructions"
                    prepend-inner-icon="mdi-robot"
                    placeholder="Instruksi tambahan untuk AI (misal: baca kategori 'peraturan & kebijakan' untuk detail biaya, atau berikan link web online untuk cek PKB)"
                    hint="Instruksi khusus ini akan disisipkan ke sistem prompt AI saat dokumen ini digunakan sebagai referensi."
                    persistent-hint
                  ></VTextarea>
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
                  id="tour-kbedit-status"
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
                  id="tour-kbedit-category"
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
                  Update Entry
                </VBtn>

                <VBtn
                  color="blue"
                  variant="outlined"
                  @click="
                    $inertia.visit(
                      route('knowledge-base.show', knowledgeBase.id),
                    )
                  "
                  block
                  class="mt-3"
                >
                  <VIcon class="mr-2">mdi-eye</VIcon>
                  View Entry
                </VBtn>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </form>
    </div>
      <TourButton @start="startTour" />
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

.gap-4 {
  gap: 16px;
}
</style>
