<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  knowledgeBase: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const form = useForm({
  title: props.knowledgeBase.title,
  content: props.knowledgeBase.content,
  excerpt: props.knowledgeBase.excerpt,
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

const showAdvanced = ref(false);
const dragActive = ref(false);
const filePreview = ref(null);
const replaceFile = ref(false);
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

// Generate excerpt automatically from content
watch(
  () => form.content,
  (newContent) => {
    if (newContent && !form.excerpt) {
      const words = newContent.replace(/<[^>]*>/g, '').split(' ');
      form.excerpt = words.slice(0, 30).join(' ') + (words.length > 30 ? '...' : '');
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

const submit = () => {
  // Convert tags string to array
  if (form.tags) {
    form.tags = form.tags
      .split(',')
      .map((tag) => tag.trim())
      .filter((tag) => tag);
  }

  form.put(route('knowledge-base.update', props.knowledgeBase.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Form will redirect on success
    },
  });
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
</script>

<template>
  <AppLayout :title="`Edit: ${knowledgeBase.title}`">
    <div class="pa-0 relative">
      <Transition name="fade">
        <div
          v-if="form.processing"
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
                    :loading="form.processing"
                    :disabled="!form.title"
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
                        <VIcon size="64" color="orange"
                          >mdi-file-replace</VIcon
                        >
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
                        <VIcon size="64" color="success"
                          >mdi-file-check</VIcon
                        >
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
                  <VAlert v-if="form.errors.file" type="error" class="mt-3">
                    {{ form.errors.file }}
                  </VAlert>
                </div>

                <!-- Title -->
                <VTextField
                  v-model="form.title"
                  label="Title *"
                  variant="outlined"
                  :error-messages="form.errors.title"
                  class="mb-4"
                  prepend-inner-icon="mdi-format-title"
                ></VTextField>

                <!-- Content (for manual entry) -->
                <div v-if="form.source_type === 'manual'">
                  <VTextarea
                    v-model="form.content"
                    label="Content *"
                    variant="outlined"
                    :error-messages="form.errors.content"
                    rows="12"
                    class="mb-4"
                    prepend-inner-icon="mdi-text"
                  ></VTextarea>
                </div>

                <!-- Excerpt -->
                <VTextarea
                  v-model="form.excerpt"
                  label="Excerpt"
                  variant="outlined"
                  :error-messages="form.errors.excerpt"
                  rows="3"
                  hint="Brief summary of the content"
                  persistent-hint
                  prepend-inner-icon="mdi-text-short"
                ></VTextarea>
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
                      :error-messages="form.errors.priority"
                    ></VSelect>
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="form.tags"
                      label="Tags"
                      variant="outlined"
                      :error-messages="form.errors.tags"
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
                  :error-messages="form.errors.status"
                  class="mb-4"
                ></VSelect>

                <VSwitch
                  v-model="form.is_active"
                  label="Active"
                  color="success"
                  :error-messages="form.errors.is_active"
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
                  :error-messages="form.errors.category"
                  class="mb-4"
                ></VSelect>

                <VSelect
                  v-model="form.type"
                  :items="typeItems"
                  label="Type"
                  variant="outlined"
                  :error-messages="form.errors.type"
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
                  :loading="form.processing"
                  :disabled="!form.title"
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
