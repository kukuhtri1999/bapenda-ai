<script setup>
import {
  ref, computed, onMounted, onUnmounted, reactive, inject,
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

// ── Loading overlay state ──────────────────────────────────────────────────
const STAGES = [
  {
    id: 'uploading',
    icon: 'mdi-cloud-upload-outline',
    label: 'Uploading file…',
    color: '#2196F3',
  },
  {
    id: 'processing',
    icon: 'mdi-cog-sync-outline',
    label: 'Processing document…',
    color: '#FF9800',
  },
  {
    id: 'extracting',
    icon: 'mdi-text-search',
    label: 'Extracting text & metadata…',
    color: '#9C27B0',
  },
  {
    id: 'saving',
    icon: 'mdi-database-plus-outline',
    label: 'Saving to knowledge base…',
    color: '#00BCD4',
  },
  {
    id: 'indexing',
    icon: 'mdi-vector-link',
    label: 'Indexing for AI search…',
    color: '#4CAF50',
  },
  {
    id: 'done',
    icon: 'mdi-check-circle-outline',
    label: 'Done!',
    color: '#4CAF50',
  },
];

const loading = reactive({
  active: false,
  stageIndex: 0,
  uploadPct: 0, // 0-100 real upload bytes progress
  serverPct: 0, // 0-100 simulated server-processing progress
  elapsedSec: 0,
  totalBytes: 0,
  loadedBytes: 0,
  aborted: false,
});

let _stageTimer = null;
let _elapsedTimer = null;
let _serverTimer = null;
let _axiosCancel = null;

const currentStage = computed(() => STAGES[loading.stageIndex] ?? STAGES[0]);

const overallPct = computed(() => {
  // Upload = first 40 %, server stages = remaining 60 %
  if (loading.stageIndex === 0) return Math.round(loading.uploadPct * 0.4);
  if (loading.stageIndex >= STAGES.length - 1) return 100;
  const serverFraction = (loading.stageIndex - 1) / (STAGES.length - 2);
  return Math.round(
    40
      + serverFraction * 55
      + (loading.serverPct / 100) * (55 / (STAGES.length - 2)),
  );
});

const elapsedLabel = computed(() => {
  const s = loading.elapsedSec;
  if (s < 60) return `${s}s`;
  return `${Math.floor(s / 60)}m ${s % 60}s`;
});

const uploadedLabel = computed(() => {
  const mb = (n) => `${(n / 1024 / 1024).toFixed(1)} MB`;
  if (!loading.totalBytes) return '';
  return `${mb(loading.loadedBytes)} / ${mb(loading.totalBytes)}`;
});

function startLoading(hasFile) {
  loading.active = true;
  loading.stageIndex = 0;
  loading.uploadPct = 0;
  loading.serverPct = 0;
  loading.elapsedSec = 0;
  loading.totalBytes = form.file?.size ?? 0;
  loading.loadedBytes = 0;
  loading.aborted = false;

  // Elapsed clock
  _elapsedTimer = setInterval(() => {
    loading.elapsedSec += 1;
  }, 1000);

  if (!hasFile) {
    // Manual entry — skip upload stage, jump straight to saving
    loading.stageIndex = 3;
    startServerProgress(3, 5, 2);
  }
}

function onUploadComplete() {
  loading.uploadPct = 100;
  loading.loadedBytes = loading.totalBytes;
  // Advance through server-side stages with realistic delays
  advanceToStage(
    1,
    () => advanceToStage(
      2,
      () => advanceToStage(
        3,
        () => advanceToStage(4, () => advanceToStage(5, null, 800), 800),
        1800,
      ),
      1200,
    ),
    900,
  );
}

function advanceToStage(idx, callback, delay = 1000) {
  _stageTimer = setTimeout(() => {
    loading.stageIndex = idx;
    loading.serverPct = 0;
    startServerProgress(idx, delay, idx < 4 ? 3 : 1);
    callback && callback();
  }, delay);
}

function startServerProgress(stageIdx, duration, cycles) {
  clearInterval(_serverTimer);
  const step = 100 / (duration / 60);
  _serverTimer = setInterval(() => {
    if (loading.stageIndex !== stageIdx) {
      clearInterval(_serverTimer);
      return;
    }
    loading.serverPct = Math.min(100, loading.serverPct + step);
  }, 60);
}

function stopLoading(success) {
  clearInterval(_elapsedTimer);
  clearInterval(_serverTimer);
  clearTimeout(_stageTimer);
  if (success) {
    loading.stageIndex = STAGES.length - 1;
    loading.serverPct = 100;
    setTimeout(() => {
      loading.active = false;
    }, 1200);
  } else {
    setTimeout(() => {
      loading.active = false;
    }, 300);
  }
}

const isClient = ref(false);
onMounted(() => {
  isClient.value = true;
});
onUnmounted(() => {
  clearInterval(_elapsedTimer);
  clearInterval(_serverTimer);
  clearTimeout(_stageTimer);
});

const showAdvanced = ref(false);
const dragActive = ref(false);
const filePreview = ref(null);
const allowedFileTypes = ['pdf', 'doc', 'docx', 'txt', 'md'];

const maxFileSize = 50; // MB — matches server limit in .htaccess
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
  // Only validate file size client-side; file type is validated server-side
  // to avoid false rejections from OS/browser MIME type differences.
  if (file.size > maxFileSizeBytes) {
    $toast.error(`File size too large. Maximum size is ${maxFileSize} MB.`);
    return;
  }

  const fileExtension = (file.name.split('.').pop() || '').toLowerCase().trim();

  form.file = file;

  // Create preview
  filePreview.value = {
    name: file.name,
    size: `${(file.size / 1024 / 1024).toFixed(2)} MB`,
    type: fileExtension.toUpperCase() || 'FILE',
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
  const hasFile = form.source_type === 'file' && !!form.file;
  startLoading(hasFile);

  try {
    const formData = new FormData();
    formData.append('title', form.title);
    formData.append('content', form.content);
    formData.append('category', form.category);
    formData.append('type', form.type);
    formData.append('status', form.status);
    formData.append('source_type', form.source_type);
    formData.append('priority', form.priority);
    formData.append('is_active', form.is_active ? '1' : '0');

    const tags = parseTags(form.tags);
    tags.forEach((tag, index) => formData.append(`tags[${index}]`, tag));

    if (form.file) formData.append('file', form.file);

    const csrfToken = document.head.querySelector(
      'meta[name="csrf-token"]',
    )?.content;

    const { CancelToken } = axios;
    const source = CancelToken.source();
    _axiosCancel = source;

    const response = await axios.post('/knowledge-base', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      // Large PDF uploads can take minutes — no timeout limit on the client
      timeout: 0,
      cancelToken: source.token,
      onUploadProgress: (evt) => {
        if (evt.lengthComputable) {
          loading.loadedBytes = evt.loaded;
          loading.totalBytes = evt.total;
          loading.uploadPct = Math.min(
            99,
            Math.round((evt.loaded / evt.total) * 100),
          );
        }
        // Once upload bytes are fully sent, move to server-processing stages
        if (evt.loaded >= evt.total && loading.stageIndex === 0) {
          onUploadComplete();
        }
      },
    });

    if (response.data.success) {
      formState.success = true;
      stopLoading(true);
      $toast.success(
        response.data.message || 'Knowledge base entry created successfully!',
      );
      setTimeout(() => router.visit('/knowledge-base'), 1300);
    }
  } catch (error) {
    stopLoading(false);
    console.error('Form submission error:', error);

    if (axios.isCancel(error)) {
      $toast.info('Upload cancelled.');
    } else if (error.response) {
      if (error.response.status === 422) {
        formState.errors = error.response.data.errors || {};
        $toast.error('Please check the form for validation errors.');
      } else if (error.response.status === 419) {
        $toast.error('Session expired. Please refresh the page and try again.');
        window.location.reload();
      } else {
        formState.errors = {
          general: ['An error occurred while saving the data.'],
        };
        $toast.error(
          error.response.data?.message
            || 'An error occurred while saving the data.',
        );
      }
    } else {
      formState.errors = { general: ['Network error occurred.'] };
      $toast.error(
        'Network error occurred. For large PDFs this may be a server timeout — please try again.',
      );
    }
  } finally {
    formState.processing = false;
    _axiosCancel = null;
  }
};

const cancelUpload = () => {
  if (_axiosCancel) {
    _axiosCancel.cancel('User cancelled upload.');
    loading.aborted = true;
    stopLoading(false);
  }
};

const cancel = () => {
  window.history.back();
};
</script>

<template>
  <AppLayout title="Create Knowledge Base Entry">
    <div class="pa-0 relative">
      <!-- ══════════════════════════════════════════════════════════════════
           LOADING OVERLAY — full-screen animated processing indicator
           ══════════════════════════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="overlay-fade">
          <div v-if="loading.active" class="kb-loading-overlay">
            <!-- Blurred backdrop -->
            <div class="kb-backdrop" />

            <!-- Main card -->
            <Transition name="card-pop" appear>
              <div class="kb-loading-card">
                <!-- Top accent bar (animated gradient) -->
                <div
                  class="kb-accent-bar"
                  :style="{ background: currentStage.color }"
                />

                <!-- Stage icon -->
                <div
                  class="kb-icon-wrap"
                  :style="{ '--c': currentStage.color }"
                >
                  <Transition name="icon-swap" mode="out-in">
                    <VIcon
                      :key="currentStage.id"
                      :color="currentStage.color"
                      size="52"
                      :class="
                        loading.stageIndex === STAGES.length - 1
                          ? 'icon-bounce'
                          : 'icon-spin'
                      "
                      >{{ currentStage.icon }}</VIcon
                    >
                  </Transition>
                  <!-- Pulse rings -->
                  <span
                    class="kb-ring kb-ring-1"
                    :style="{ borderColor: currentStage.color }"
                  />
                  <span
                    class="kb-ring kb-ring-2"
                    :style="{ borderColor: currentStage.color }"
                  />
                </div>

                <!-- Stage label -->
                <Transition name="text-fade" mode="out-in">
                  <h2 :key="currentStage.id" class="kb-stage-label">
                    {{ currentStage.label }}
                  </h2>
                </Transition>

                <!-- File info pill -->
                <div v-if="filePreview" class="kb-file-pill">
                  <VIcon size="14" class="mr-1"
                    >mdi-file-document-outline</VIcon
                  >
                  <span class="kb-file-name">{{ filePreview.name }}</span>
                  <span class="kb-file-sep">·</span>
                  <span>{{ filePreview.size }}</span>
                </div>

                <!-- Overall progress bar -->
                <div class="kb-progress-track">
                  <div
                    class="kb-progress-fill"
                    :style="{
                      width: overallPct + '%',
                      background: currentStage.color,
                      transition:
                        loading.stageIndex === 0
                          ? 'width 0.3s ease'
                          : 'width 0.8s cubic-bezier(0.4,0,0.2,1)',
                    }"
                  />
                  <span class="kb-progress-pct">{{ overallPct }}%</span>
                </div>

                <!-- Step stepper -->
                <div class="kb-stepper">
                  <div
                    v-for="(stage, i) in STAGES"
                    :key="stage.id"
                    class="kb-step"
                    :class="{
                      'kb-step--done': i < loading.stageIndex,
                      'kb-step--active': i === loading.stageIndex,
                      'kb-step--future': i > loading.stageIndex,
                    }"
                  >
                    <div
                      class="kb-step-dot"
                      :style="
                        i <= loading.stageIndex
                          ? {
                              background: stage.color,
                              borderColor: stage.color,
                            }
                          : {}
                      "
                    >
                      <VIcon
                        v-if="i < loading.stageIndex"
                        size="10"
                        color="white"
                        >mdi-check</VIcon
                      >
                      <span
                        v-else-if="i === loading.stageIndex"
                        class="kb-step-pulse"
                        :style="{ background: stage.color }"
                      />
                    </div>
                    <span class="kb-step-label">{{
                      stage.label.replace('…', '')
                    }}</span>
                    <div
                      v-if="i < STAGES.length - 1"
                      class="kb-step-line"
                      :class="{ 'kb-step-line--done': i < loading.stageIndex }"
                    />
                  </div>
                </div>

                <!-- Upload byte counter (only during upload stage) -->
                <Transition name="text-fade">
                  <div
                    v-if="loading.stageIndex === 0 && uploadedLabel"
                    class="kb-bytes-label"
                  >
                    <VIcon size="13" class="mr-1">mdi-upload</VIcon
                    >{{ uploadedLabel }}
                  </div>
                </Transition>

                <!-- Elapsed time + hint -->
                <div class="kb-footer">
                  <span
                    ><VIcon size="13" class="mr-1">mdi-clock-outline</VIcon
                    >{{ elapsedLabel }}</span
                  >
                  <span class="kb-hint">Large PDFs may take 1–2 minutes</span>
                </div>

                <!-- Cancel button (only during upload, not after server starts) -->
                <Transition name="text-fade">
                  <button
                    v-if="loading.stageIndex === 0"
                    class="kb-cancel-btn"
                    @click="cancelUpload"
                  >
                    Cancel upload
                  </button>
                </Transition>
              </div>
            </Transition>
          </div>
        </Transition>
      </Teleport>

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
                  <VBtn
                    variant="outlined"
                    @click="cancel"
                    class="mr-3"
                    :disabled="formState.processing"
                  >
                    Cancel
                  </VBtn>
                  <VBtn
                    color="primary"
                    @click="submit"
                    :loading="formState.processing"
                    :disabled="
                      formState.processing ||
                      !form.title ||
                      (form.source_type === 'manual' && !form.content)
                    "
                  >
                    <VIcon class="mr-2">mdi-content-save</VIcon>
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
                          accept=".pdf,.doc,.docx,.txt,.md,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown"
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
/* ── File upload border ─────────────────────────────────────────── */
.border-primary {
  border-color: rgb(var(--v-theme-primary)) !important;
  border-width: 2px !important;
}

/* ── Loading overlay ─────────────────────────────────────────────── */
.kb-loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kb-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.kb-loading-card {
  position: relative;
  z-index: 1;
  background: #fff;
  border-radius: 20px;
  width: min(520px, 90vw);
  padding: 0 0 28px;
  box-shadow:
    0 24px 60px rgba(0, 0, 0, 0.25),
    0 4px 12px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  overflow: hidden;
}

/* Animated top bar */
.kb-accent-bar {
  width: 100%;
  height: 5px;
  transition: background 0.6s ease;
  background-size: 200% 100%;
  animation: bar-shimmer 1.8s linear infinite;
}
@keyframes bar-shimmer {
  0% {
    filter: brightness(1);
  }
  50% {
    filter: brightness(1.3);
  }
  100% {
    filter: brightness(1);
  }
}

/* Icon area */
.kb-icon-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 96px;
  height: 96px;
  margin: 28px auto 4px;
}

.icon-spin {
  animation: icon-rotate 2.4s linear infinite;
}
.icon-bounce {
  animation: icon-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes icon-rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
@keyframes icon-pop {
  from {
    transform: scale(0.4);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

/* Pulse rings */
.kb-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid;
  opacity: 0;
  pointer-events: none;
}
.kb-ring-1 {
  width: 72px;
  height: 72px;
  animation: ring-pulse 2s ease-out infinite;
}
.kb-ring-2 {
  width: 96px;
  height: 96px;
  animation: ring-pulse 2s ease-out 0.7s infinite;
}
@keyframes ring-pulse {
  0% {
    transform: scale(0.6);
    opacity: 0.6;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

/* Stage label */
.kb-stage-label {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1e293b;
  margin: 12px 24px 4px;
  text-align: center;
  min-height: 1.5em;
}

/* File pill */
.kb-file-pill {
  display: flex;
  align-items: center;
  font-size: 0.78rem;
  color: #64748b;
  background: #f1f5f9;
  border-radius: 999px;
  padding: 4px 12px;
  margin: 6px 24px 14px;
  max-width: calc(100% - 48px);
  white-space: nowrap;
  overflow: hidden;
}
.kb-file-name {
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 220px;
}
.kb-file-sep {
  margin: 0 6px;
}

/* Progress track */
.kb-progress-track {
  position: relative;
  width: calc(100% - 48px);
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
  margin: 0 24px 20px;
}
.kb-progress-fill {
  height: 100%;
  border-radius: 999px;
  min-width: 4px;
}
.kb-progress-pct {
  position: absolute;
  right: 0;
  top: -20px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
}

/* Step stepper */
.kb-stepper {
  display: flex;
  align-items: flex-start;
  width: calc(100% - 48px);
  margin: 0 24px 14px;
  gap: 0;
  overflow-x: auto;
  padding-bottom: 4px;
}
.kb-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
  min-width: 0;
}
.kb-step-dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.35s ease;
  z-index: 1;
  flex-shrink: 0;
}
.kb-step--done .kb-step-dot {
  background: #4caf50;
  border-color: #4caf50;
}
.kb-step--active .kb-step-dot {
  box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.08);
}
.kb-step-pulse {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: block;
  animation: step-pulse 1s ease-in-out infinite;
}
@keyframes step-pulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.4);
  }
}
.kb-step-label {
  font-size: 0.62rem;
  color: #94a3b8;
  text-align: center;
  margin-top: 5px;
  line-height: 1.2;
  max-width: 56px;
  word-break: break-word;
}
.kb-step--done .kb-step-label,
.kb-step--active .kb-step-label {
  color: #334155;
  font-weight: 600;
}
.kb-step-line {
  position: absolute;
  top: 10px;
  left: calc(50% + 11px);
  right: calc(-50% + 11px);
  height: 2px;
  background: #e2e8f0;
  z-index: 0;
  transition: background 0.4s ease;
}
.kb-step-line--done {
  background: #4caf50;
}

/* Byte counter */
.kb-bytes-label {
  font-size: 0.78rem;
  color: #475569;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
}

/* Footer */
.kb-footer {
  display: flex;
  gap: 16px;
  align-items: center;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 4px;
}
.kb-hint {
  font-style: italic;
}

/* Cancel button */
.kb-cancel-btn {
  margin-top: 14px;
  background: none;
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  padding: 5px 18px;
  font-size: 0.78rem;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}
.kb-cancel-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

/* ── Transition animations ──────────────────────────────────────── */
.overlay-fade-enter-active {
  transition: opacity 0.25s ease;
}
.overlay-fade-leave-active {
  transition: opacity 0.3s ease;
}
.overlay-fade-enter-from,
.overlay-fade-leave-to {
  opacity: 0;
}

.card-pop-enter-active {
  animation: card-enter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
.card-pop-leave-active {
  animation: card-leave 0.25s ease both;
}
@keyframes card-enter {
  from {
    transform: scale(0.85) translateY(24px);
    opacity: 0;
  }
  to {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}
@keyframes card-leave {
  from {
    transform: scale(1);
    opacity: 1;
  }
  to {
    transform: scale(0.95);
    opacity: 0;
  }
}

.icon-swap-enter-active {
  animation: icon-enter 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
.icon-swap-leave-active {
  animation: icon-leave 0.15s ease both;
}
@keyframes icon-enter {
  from {
    transform: scale(0.5) rotate(-30deg);
    opacity: 0;
  }
  to {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}
@keyframes icon-leave {
  from {
    transform: scale(1);
    opacity: 1;
  }
  to {
    transform: scale(0.5) rotate(20deg);
    opacity: 0;
  }
}

.text-fade-enter-active {
  transition: all 0.3s ease;
}
.text-fade-leave-active {
  transition: all 0.2s ease;
}
.text-fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.text-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
