<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  knowledgeBase: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const confirmDelete = ref(false);
const showContent = ref(true);
const working = ref(false);

const deleteItem = () => {
  working.value = true;
  router.delete(route('knowledge-base.destroy', props.knowledgeBase.id), {
    onFinish: () => (working.value = false),
  });
};

const toggleStatus = () => {
  working.value = true;
  router.post(
    route('knowledge-base.toggle-status', props.knowledgeBase.id),
    {},
    {
      preserveScroll: true,
      onFinish: () => (working.value = false),
    },
  );
};

const downloadFile = () => {
  window.open(route('knowledge-base.download', props.knowledgeBase.id));
};

const getStatusColor = (status) => {
  const colors = {
    published: 'success',
    draft: 'warning',
    archived: 'grey',
  };
  return colors[status] || 'grey';
};

const getPriorityColor = (priority) => {
  const colors = {
    1: 'green',
    2: 'blue',
    3: 'orange',
    4: 'red',
  };
  return colors[priority] || 'grey';
};

const getPriorityText = (priority) => {
  const texts = {
    1: 'Low',
    2: 'Normal',
    3: 'High',
    4: 'Critical',
  };
  return texts[priority] || 'Unknown';
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    // Could add a toast notification here
  });
};
</script>

<template>
  <AppLayout :title="knowledgeBase.title">
    <div class="pa-0 relative">
      <Transition name="fade">
        <div
          v-if="working"
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
            <span class="text-sm text-gray-600">Processing…</span>
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
                  <div class="d-flex align-center mb-3">
                    <VChip
                      :color="
                        knowledgeBase.source_type === 'file' ? 'blue' : 'green'
                      "
                      variant="tonal"
                      size="small"
                      class="mr-3"
                    >
                      <VIcon
                        :icon="
                          knowledgeBase.source_type === 'file'
                            ? 'mdi-file-document'
                            : 'mdi-keyboard'
                        "
                        class="mr-1"
                        size="small"
                      ></VIcon>
                      {{
                        knowledgeBase.source_type === 'file' ? 'File' : 'Manual'
                      }}
                    </VChip>
                    <VChip
                      :color="getStatusColor(knowledgeBase.status)"
                      variant="tonal"
                      size="small"
                      class="mr-3"
                    >
                      {{
                        statuses[knowledgeBase.status] || knowledgeBase.status
                      }}
                    </VChip>
                    <VChip
                      :color="knowledgeBase.is_active ? 'success' : 'error'"
                      variant="tonal"
                      size="small"
                    >
                      {{ knowledgeBase.is_active ? 'Active' : 'Inactive' }}
                    </VChip>
                  </div>
                  <h1 class="text-h4 font-weight-bold text-primary mb-2">
                    {{ knowledgeBase.title }}
                  </h1>
                  <p
                    class="text-body-1 text-medium-emphasis mb-3"
                    v-if="knowledgeBase.excerpt"
                  >
                    {{ knowledgeBase.excerpt }}
                  </p>
                  <div class="d-flex align-center gap-4">
                    <VChip variant="outlined" size="small">
                      <VIcon class="mr-1" size="small">mdi-eye</VIcon>
                      {{ knowledgeBase.view_count || 0 }}
                      views
                    </VChip>
                    <VChip variant="outlined" size="small">
                      <VIcon class="mr-1" size="small">mdi-calendar</VIcon>
                      {{ formatDate(knowledgeBase.created_at) }}
                    </VChip>
                    <VChip
                      v-if="knowledgeBase.creator"
                      variant="outlined"
                      size="small"
                    >
                      <VIcon class="mr-1" size="small">mdi-account</VIcon>
                      {{ knowledgeBase.creator.name }}
                    </VChip>
                  </div>
                </VCol>
                <VCol cols="12" md="4" class="text-right">
                  <VBtn
                    variant="outlined"
                    @click="$inertia.visit(route('knowledge-base.index'))"
                    class="mr-2"
                  >
                    <VIcon class="mr-2">mdi-arrow-left</VIcon>
                    Back to List
                  </VBtn>
                  <VMenu>
                    <template #activator="{ props }">
                      <VBtn color="primary" v-bind="props">
                        Actions
                        <VIcon class="ml-2">mdi-chevron-down</VIcon>
                      </VBtn>
                    </template>
                    <VList>
                      <VListItem
                        @click="
                          $inertia.visit(
                            route('knowledge-base.edit', knowledgeBase.id),
                          )
                        "
                      >
                        <template #prepend>
                          <VIcon>mdi-pencil</VIcon>
                        </template>
                        <VListItemTitle>Edit</VListItemTitle>
                      </VListItem>
                      <VListItem @click="toggleStatus">
                        <template #prepend>
                          <VIcon>{{
                            knowledgeBase.is_active ? 'mdi-eye-off' : 'mdi-eye'
                          }}</VIcon>
                        </template>
                        <VListItemTitle>
                          {{
                            knowledgeBase.is_active ? 'Deactivate' : 'Activate'
                          }}
                        </VListItemTitle>
                      </VListItem>
                      <VListItem
                        v-if="knowledgeBase.file_path"
                        @click="downloadFile"
                      >
                        <template #prepend>
                          <VIcon>mdi-download</VIcon>
                        </template>
                        <VListItemTitle>Download File</VListItemTitle>
                      </VListItem>
                      <VDivider></VDivider>
                      <VListItem
                        @click="confirmDelete = true"
                        class="text-error"
                      >
                        <template #prepend>
                          <VIcon color="error">mdi-delete</VIcon>
                        </template>
                        <VListItemTitle>Delete</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <VRow>
        <!-- Main Content -->
        <VCol cols="12" md="8">
          <!-- Content -->
          <VCard elevation="2" class="mb-4">
            <VCardTitle
              class="bg-primary text-white d-flex align-center justify-space-between"
            >
              <div class="d-flex align-center">
                <VIcon class="mr-2">mdi-file-document-outline</VIcon>
                Content
              </div>
              <VBtn
                icon
                variant="text"
                @click="showContent = !showContent"
                color="white"
              >
                <VIcon>{{
                  showContent ? 'mdi-chevron-up' : 'mdi-chevron-down'
                }}</VIcon>
              </VBtn>
            </VCardTitle>
            <VExpandTransition>
              <VCardText v-show="showContent" class="pa-6">
                <div v-if="knowledgeBase.content" class="content-display">
                  <div
                    v-html="knowledgeBase.content.replace(/\n/g, '<br>')"
                  ></div>
                </div>
                <div
                  v-else-if="knowledgeBase.source_type === 'file'"
                  class="text-center pa-8"
                >
                  <VIcon size="64" color="blue">mdi-file-document</VIcon>
                  <h3 class="text-h6 mt-3">File Content</h3>
                  <p class="text-body-2 text-medium-emphasis">
                    This entry is based on an uploaded file.
                    <span v-if="knowledgeBase.file_path">
                      <br />Download the file to view the content.
                    </span>
                  </p>
                  <VBtn
                    v-if="knowledgeBase.file_path"
                    color="blue"
                    variant="outlined"
                    @click="downloadFile"
                    class="mt-3"
                  >
                    <VIcon class="mr-2">mdi-download</VIcon>
                    Download File
                  </VBtn>
                </div>
                <div v-else class="text-center pa-8">
                  <VIcon size="64" color="grey">mdi-text</VIcon>
                  <h3 class="text-h6 mt-3">No Content</h3>
                  <p class="text-body-2 text-medium-emphasis">
                    This entry doesn't have any content yet.
                  </p>
                </div>
              </VCardText>
            </VExpandTransition>
          </VCard>

          <!-- File Information -->
          <VCard v-if="knowledgeBase.file_path" elevation="2" class="mb-4">
            <VCardTitle class="bg-blue text-white">
              <VIcon class="mr-2">mdi-file-document</VIcon>
              File Information
            </VCardTitle>
            <VCardText class="pa-6">
              <VRow>
                <VCol cols="12" md="6">
                  <div class="mb-3">
                    <strong>File Name:</strong>
                    <div class="mt-1">
                      {{ knowledgeBase.file_name || 'Unknown' }}
                    </div>
                  </div>
                  <div class="mb-3">
                    <strong>File Type:</strong>
                    <div class="mt-1">
                      <VChip size="small" variant="outlined">
                        {{
                          knowledgeBase.file_type?.toUpperCase() || 'Unknown'
                        }}
                      </VChip>
                    </div>
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="mb-3">
                    <strong>File Size:</strong>
                    <div class="mt-1">
                      {{
                        knowledgeBase.file_size
                          ? (knowledgeBase.file_size / 1024 / 1024).toFixed(2) +
                            ' MB'
                          : 'Unknown'
                      }}
                    </div>
                  </div>
                  <div class="mb-3">
                    <strong>Actions:</strong>
                    <div class="mt-2">
                      <VBtn
                        color="blue"
                        variant="outlined"
                        size="small"
                        @click="downloadFile"
                      >
                        <VIcon class="mr-1">mdi-download</VIcon>
                        Download
                      </VBtn>
                    </div>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <!-- Tags -->
          <VCard
            v-if="knowledgeBase.tags && knowledgeBase.tags.length > 0"
            elevation="2"
          >
            <VCardTitle class="bg-green text-white">
              <VIcon class="mr-2">mdi-tag-multiple</VIcon>
              Tags
            </VCardTitle>
            <VCardText class="pa-6">
              <div class="d-flex flex-wrap gap-2">
                <VChip
                  v-for="tag in knowledgeBase.tags"
                  :key="tag"
                  variant="tonal"
                  size="small"
                >
                  <VIcon class="mr-1" size="small">mdi-tag</VIcon>
                  {{ tag }}
                </VChip>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Sidebar -->
        <VCol cols="12" md="4">
          <!-- Metadata -->
          <VCard elevation="2" class="mb-4">
            <VCardTitle class="bg-orange text-white">
              <VIcon class="mr-2">mdi-information</VIcon>
              Details
            </VCardTitle>
            <VCardText class="pa-6">
              <div class="mb-4">
                <strong>Category:</strong>
                <div class="mt-1">
                  <VChip
                    :color="
                      knowledgeBase.category === 'pajak'
                        ? 'blue'
                        : knowledgeBase.category === 'stnk'
                          ? 'green'
                          : 'grey'
                    "
                    variant="tonal"
                    size="small"
                  >
                    {{
                      categories[knowledgeBase.category] ||
                      knowledgeBase.category
                    }}
                  </VChip>
                </div>
              </div>

              <div class="mb-4">
                <strong>Type:</strong>
                <div class="mt-1">
                  <VChip
                    :color="
                      knowledgeBase.type === 'faq'
                        ? 'orange'
                        : knowledgeBase.type === 'sop'
                          ? 'purple'
                          : 'blue-grey'
                    "
                    variant="tonal"
                    size="small"
                  >
                    {{ types[knowledgeBase.type] || knowledgeBase.type }}
                  </VChip>
                </div>
              </div>

              <div class="mb-4">
                <strong>Priority:</strong>
                <div class="mt-1">
                  <VChip
                    :color="getPriorityColor(knowledgeBase.priority)"
                    variant="tonal"
                    size="small"
                  >
                    {{ getPriorityText(knowledgeBase.priority) }}
                  </VChip>
                </div>
              </div>

              <div class="mb-4">
                <strong>Status:</strong>
                <div class="mt-1">
                  <VChip
                    :color="getStatusColor(knowledgeBase.status)"
                    variant="tonal"
                    size="small"
                  >
                    {{ statuses[knowledgeBase.status] || knowledgeBase.status }}
                  </VChip>
                </div>
              </div>

              <div class="mb-4">
                <strong>Active:</strong>
                <div class="mt-1">
                  <VChip
                    :color="knowledgeBase.is_active ? 'success' : 'error'"
                    variant="tonal"
                    size="small"
                  >
                    {{ knowledgeBase.is_active ? 'Yes' : 'No' }}
                  </VChip>
                </div>
              </div>

              <div>
                <strong>Views:</strong>
                <div class="mt-1">
                  <VChip color="info" variant="outlined" size="small">
                    <VIcon class="mr-1" size="small">mdi-eye</VIcon>
                    {{ knowledgeBase.view_count || 0 }}
                  </VChip>
                </div>
              </div>
            </VCardText>
          </VCard>

          <!-- Timestamps -->
          <VCard elevation="2" class="mb-4">
            <VCardTitle class="bg-blue-grey text-white">
              <VIcon class="mr-2">mdi-clock</VIcon>
              Timestamps
            </VCardTitle>
            <VCardText class="pa-6">
              <div class="mb-3">
                <strong>Created:</strong>
                <div class="mt-1 text-body-2">
                  {{ formatDate(knowledgeBase.created_at) }}
                </div>
                <div
                  v-if="knowledgeBase.creator"
                  class="text-caption text-medium-emphasis"
                >
                  by {{ knowledgeBase.creator.name }}
                </div>
              </div>

              <div
                v-if="knowledgeBase.updated_at !== knowledgeBase.created_at"
                class="mb-3"
              >
                <strong>Last Updated:</strong>
                <div class="mt-1 text-body-2">
                  {{ formatDate(knowledgeBase.updated_at) }}
                </div>
                <div
                  v-if="knowledgeBase.updater"
                  class="text-caption text-medium-emphasis"
                >
                  by {{ knowledgeBase.updater.name }}
                </div>
              </div>

              <div v-if="knowledgeBase.published_at">
                <strong>Published:</strong>
                <div class="mt-1 text-body-2">
                  {{ formatDate(knowledgeBase.published_at) }}
                </div>
              </div>
            </VCardText>
          </VCard>

          <!-- Quick Actions -->
          <VCard elevation="2">
            <VCardTitle class="bg-purple text-white">
              <VIcon class="mr-2">mdi-lightning-bolt</VIcon>
              Quick Actions
            </VCardTitle>
            <VCardText class="pa-6">
              <VBtn
                color="primary"
                variant="outlined"
                block
                @click="
                  $inertia.visit(route('knowledge-base.edit', knowledgeBase.id))
                "
                class="mb-3"
              >
                <VIcon class="mr-2">mdi-pencil</VIcon>
                Edit Entry
              </VBtn>

              <VBtn
                :color="knowledgeBase.is_active ? 'warning' : 'success'"
                variant="outlined"
                block
                @click="toggleStatus"
                class="mb-3"
              >
                <VIcon class="mr-2">{{
                  knowledgeBase.is_active ? 'mdi-eye-off' : 'mdi-eye'
                }}</VIcon>
                {{ knowledgeBase.is_active ? 'Deactivate' : 'Activate' }}
              </VBtn>

              <VBtn
                v-if="knowledgeBase.file_path"
                color="blue"
                variant="outlined"
                block
                @click="downloadFile"
                class="mb-3"
              >
                <VIcon class="mr-2">mdi-download</VIcon>
                Download File
              </VBtn>

              <VBtn
                color="info"
                variant="outlined"
                block
                @click="copyToClipboard(knowledgeBase.title)"
                class="mb-3"
              >
                <VIcon class="mr-2">mdi-content-copy</VIcon>
                Copy Title
              </VBtn>

              <VBtn
                color="error"
                variant="outlined"
                block
                @click="confirmDelete = true"
              >
                <VIcon class="mr-2">mdi-delete</VIcon>
                Delete Entry
              </VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Delete Confirmation Dialog -->
      <VDialog v-model="confirmDelete" max-width="400">
        <VCard>
          <VCardTitle>
            <VIcon color="error" class="mr-2">mdi-alert</VIcon>
            Confirm Deletion
          </VCardTitle>
          <VCardText>
            Are you sure you want to delete "<strong>{{
              knowledgeBase.title
            }}</strong
            >"? This action cannot be undone.
          </VCardText>
          <VCardActions>
            <VSpacer></VSpacer>
            <VBtn @click="confirmDelete = false">Cancel</VBtn>
            <VBtn color="error" @click="deleteItem">Delete</VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </AppLayout>
</template>

<style scoped>
.gap-2 {
  gap: 8px;
}

.gap-4 {
  gap: 16px;
}

.content-display {
  line-height: 1.6;
  font-size: 16px;
}

.content-display h1,
.content-display h2,
.content-display h3 {
  margin-top: 1.5em;
  margin-bottom: 0.5em;
}

.content-display p {
  margin-bottom: 1em;
}

.content-display ul,
.content-display ol {
  margin-bottom: 1em;
  padding-left: 2em;
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
