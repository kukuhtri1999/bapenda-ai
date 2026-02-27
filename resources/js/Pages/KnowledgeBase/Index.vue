<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  knowledgeBases: Object,
  filters: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const typeFilter = ref(props.filters.type || '');
const sourceTypeFilter = ref(props.filters.source_type || '');
const statusFilter = ref(props.filters.status || '');
const activeFilter = ref(props.filters.is_active || '');
const selectedItems = ref([]);
const bulkAction = ref('');
const confirmDelete = ref(false);
const itemToDelete = ref(null);
const syncDialog = ref(false);
const syncProgress = ref(false);
const syncResults = ref(null);
const syncDryRun = ref(true);

const headers = [
  { title: 'Title', key: 'title', sortable: true },
  { title: 'Category', key: 'category', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  // { title: "Source", key: "source_type", sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Active', key: 'is_active', sortable: true },
  // { title: "Views", key: "view_count", sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const sourceTypeItems = [
  { title: 'All Sources', value: '' },
  { title: 'Manual Entry', value: 'manual' },
  { title: 'File Upload', value: 'file' },
];

const activeItems = [
  { title: 'All Status', value: '' },
  { title: 'Active', value: 'true' },
  { title: 'Inactive', value: 'false' },
];

const bulkActions = [
  { title: 'Select Action...', value: '' },
  { title: 'Delete Selected', value: 'delete' },
  { title: 'Activate Selected', value: 'activate' },
  { title: 'Deactivate Selected', value: 'deactivate' },
  { title: 'Publish Selected', value: 'publish' },
  { title: 'Archive Selected', value: 'archive' },
];

// New sections data
const analysisTab = ref('overview');

const implementationSteps = ref([
  {
    title: 'Content Strategy & Planning',
    description:
      'Define comprehensive content structure, categorization standards, and quality guidelines for knowledge base expansion.',
    duration: '1-2 weeks',
    difficulty: 'Medium',
    color: 'primary',
  },
  {
    title: 'Vector Database Optimization',
    description:
      'Implement advanced chunking strategies, optimize embedding models, and enhance search relevance algorithms.',
    duration: '2-3 weeks',
    difficulty: 'Hard',
    color: 'warning',
  },
  {
    title: 'AI Model Fine-tuning',
    description:
      'Customize language models for domain-specific responses, improve context understanding, and enhance answer accuracy.',
    duration: '3-4 weeks',
    difficulty: 'Hard',
    color: 'error',
  },
  {
    title: 'User Experience Enhancement',
    description:
      'Develop intuitive search interfaces, implement smart suggestions, and create seamless content discovery flows.',
    duration: '2-3 weeks',
    difficulty: 'Medium',
    color: 'success',
  },
  {
    title: 'Performance & Analytics',
    description:
      'Deploy comprehensive monitoring, implement usage analytics, and establish continuous improvement processes.',
    duration: '1-2 weeks',
    difficulty: 'Easy',
    color: 'info',
  },
]);

const strategicRecommendations = ref([
  {
    title: 'Enhanced Semantic Search Implementation',
    description:
      'Upgrade to advanced vector similarity algorithms with hybrid search capabilities combining semantic and keyword matching for superior accuracy.',
    priority: 'High',
    icon: 'mdi-magnify-plus',
    benefits: [
      '40% improvement in search accuracy',
      'Reduced query response time',
      'Better handling of complex queries',
      'Enhanced user satisfaction scores',
    ],
    timeline: 'Q1 2025',
    effort: 'High Impact',
  },
  {
    title: 'Multi-language Support Integration',
    description:
      'Implement comprehensive Javanese-Indonesian translation with cultural context preservation for inclusive service delivery.',
    priority: 'Medium',
    icon: 'mdi-translate',
    benefits: [
      'Expanded user accessibility',
      'Cultural sensitivity compliance',
      'Broader community engagement',
      'Government inclusivity standards',
    ],
    timeline: 'Q2 2025',
    effort: 'Medium Impact',
  },
  {
    title: 'Automated Content Quality Assurance',
    description:
      'Deploy AI-powered content validation, consistency checking, and automated quality scoring systems.',
    priority: 'Medium',
    icon: 'mdi-shield-check',
    benefits: [
      'Consistent content quality',
      'Reduced manual review time',
      'Automated compliance checking',
      'Standardized content structure',
    ],
    timeline: 'Q2 2025',
    effort: 'Medium Impact',
  },
  {
    title: 'Advanced Analytics Dashboard',
    description:
      'Create comprehensive analytics platform with user behavior insights, content performance metrics, and predictive analytics.',
    priority: 'Low',
    icon: 'mdi-chart-line',
    benefits: [
      'Data-driven decision making',
      'Content optimization insights',
      'User engagement tracking',
      'Performance trend analysis',
    ],
    timeline: 'Q3 2025',
    effort: 'Low Impact',
  },
]);

const roadmapPhases = ref([
  {
    title: 'Foundation & Infrastructure',
    description:
      'Establish robust technical foundation with optimized database architecture and core AI integration.',
    status: 'completed',
    progress: 100,
    timeline: 'Q4 2024',
    icon: 'mdi-foundation',
    deliverables: [
      'Vector database deployment',
      'Core AI model integration',
      'Basic search functionality',
      'Content management system',
    ],
  },
  {
    title: 'Enhanced Capabilities',
    description:
      'Implement advanced search features, improve AI response quality, and optimize system performance.',
    status: 'active',
    progress: 75,
    timeline: 'Q1 2025',
    icon: 'mdi-rocket-launch',
    deliverables: [
      'Semantic search upgrade',
      'Response quality improvements',
      'Performance optimization',
      'User interface enhancements',
    ],
  },
  {
    title: 'Intelligence & Automation',
    description:
      'Deploy machine learning automation, predictive analytics, and intelligent content management.',
    status: 'planned',
    progress: 25,
    timeline: 'Q2 2025',
    icon: 'mdi-brain',
    deliverables: [
      'Automated content classification',
      'Predictive user assistance',
      'Smart content recommendations',
      'Intelligent quality assurance',
    ],
  },
  {
    title: 'Scale & Innovation',
    description:
      'Achieve enterprise-scale deployment with cutting-edge AI features and comprehensive integration.',
    status: 'planned',
    progress: 0,
    timeline: 'Q3 2025',
    icon: 'mdi-trending-up',
    deliverables: [
      'Multi-language support',
      'Advanced analytics platform',
      'Third-party integrations',
      'Innovation lab features',
    ],
  },
]);

const filteredKnowledgeBases = computed(() => props.knowledgeBases.data);

const applyFilters = () => {
  router.get(
    route('knowledge-base.index'),
    {
      search: search.value,
      category: categoryFilter.value,
      type: typeFilter.value,
      source_type: sourceTypeFilter.value,
      status: statusFilter.value,
      is_active: activeFilter.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};

const clearFilters = () => {
  search.value = '';
  categoryFilter.value = '';
  typeFilter.value = '';
  sourceTypeFilter.value = '';
  statusFilter.value = '';
  activeFilter.value = '';
  router.get(route('knowledge-base.index'));
};

const deleteItem = (item) => {
  itemToDelete.value = item;
  confirmDelete.value = true;
};

const confirmDeleteItem = () => {
  if (itemToDelete.value) {
    router.delete(route('knowledge-base.destroy', itemToDelete.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        confirmDelete.value = false;
        itemToDelete.value = null;
      },
    });
  }
};

const toggleStatus = (item) => {
  router.post(
    route('knowledge-base.toggle-status', item.id),
    {},
    {
      preserveScroll: true,
    },
  );
};

const executeBulkAction = () => {
  if (!bulkAction.value || selectedItems.value.length === 0) return;

  router.post(
    route('knowledge-base.bulk-action'),
    {
      action: bulkAction.value,
      ids: selectedItems.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        selectedItems.value = [];
        bulkAction.value = '';
      },
    },
  );
};

const getStatusColor = (status) => {
  const colors = {
    published: 'success',
    draft: 'warning',
    archived: 'grey',
  };
  return colors[status] || 'grey';
};

const getSourceIcon = (sourceType) => (sourceType === 'file' ? 'mdi-file-document' : 'mdi-keyboard');

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
});

const openSyncDialog = () => {
  syncDialog.value = true;
  syncResults.value = null;
  syncDryRun.value = true;
};

const syncPinecone = async () => {
  try {
    syncProgress.value = true;

    // Read CSRF token fresh from the meta tag (same pattern as Create.vue)
    const csrfToken = document.head.querySelector(
      'meta[name="csrf-token"]',
    )?.content;

    const response = await axios.post(
      route('knowledge-base.sync-pinecone'),
      {
        dry_run: syncDryRun.value,
        confirm: !syncDryRun.value, // Require confirmation for actual rebuild
      },
      {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json',
        },
      },
    );

    syncResults.value = response.data;

    if (!syncDryRun.value) {
      // If it was a real rebuild, refresh the page data
      router.reload({ only: ['knowledgeBases'] });
    }
  } catch (error) {
    console.error('Rebuild error:', error);
    syncResults.value = {
      success: false,
      message:
        error.response?.data?.message || 'Vector database rebuild failed',
      stats: null,
    };
  } finally {
    syncProgress.value = false;
  }
};

const closeSyncDialog = () => {
  syncDialog.value = false;
  syncResults.value = null;
};

// ── Batch Upload ─────────────────────────────────────────────────────────────
const batchDialog = ref(false);
const batchStep = ref('setup'); // 'setup' | 'processing' | 'completed'
const batchFiles = ref([]); // raw File objects chosen by user
const batchFileEntries = ref([]); // [{original_name, size, status, error, kb_id, kb_title}]
const batchId = ref(null);
const batchProcessing = ref(false);
const batchCategory = ref('');
const batchType = ref('regulation');
const batchStatus = ref('published');
const batchError = ref(null);
const batchDragOver = ref(false);
const batchProcessed = ref(0);
const batchFailed = ref(0);

const batchProgress = computed(() => {
  if (!batchFileEntries.value.length) return 0;
  return Math.round(
    (batchProcessed.value / batchFileEntries.value.length) * 100,
  );
});

const formatFileSize = (bytes) => {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const fileStatusIcon = (status) => {
  const icons = {
    pending: 'mdi-clock-outline',
    processing: 'mdi-loading mdi-spin',
    done: 'mdi-check-circle',
    failed: 'mdi-alert-circle',
  };
  return icons[status] || 'mdi-help-circle';
};
const fileStatusColor = (status) => {
  const colors = {
    pending: 'grey',
    processing: 'blue',
    done: 'success',
    failed: 'error',
  };
  return colors[status] || 'grey';
};

const openBatchDialog = () => {
  batchDialog.value = true;
  batchStep.value = 'setup';
  batchFiles.value = [];
  batchFileEntries.value = [];
  batchId.value = null;
  batchProcessing.value = false;
  batchError.value = null;
  batchDragOver.value = false;
  batchProcessed.value = 0;
  batchFailed.value = 0;
  batchCategory.value = Object.keys(props.categories || {})[0] || '';
  batchType.value = 'regulation';
  batchStatus.value = 'published';
};

const closeBatchDialog = () => {
  batchDialog.value = false;
  if (batchStep.value === 'completed') {
    router.reload({ only: ['knowledgeBases'] });
  }
};

const onBatchDrop = (e) => {
  e.preventDefault();
  batchDragOver.value = false;
  const dropped = Array.from(e.dataTransfer?.files || []);
  addBatchFiles(dropped);
};

const onBatchFileInput = (e) => {
  const selected = Array.from(e.target?.files || []);
  addBatchFiles(selected);
  // Reset input so same file can be re-added after removal
  if (e.target) e.target.value = '';
};

const ALLOWED_EXTS = ['pdf', 'doc', 'docx'];
const addBatchFiles = (newFiles) => {
  for (const f of newFiles) {
    const ext = f.name.split('.').pop().toLowerCase();
    if (!ALLOWED_EXTS.includes(ext)) continue;
    // Avoid duplicates by name
    if (!batchFiles.value.find((x) => x.name === f.name)) {
      batchFiles.value.push(f);
    }
  }
};

const removeBatchFile = (index) => {
  batchFiles.value.splice(index, 1);
};

const startBatchUpload = async () => {
  if (!batchFiles.value.length || !batchCategory.value) return;

  batchProcessing.value = true;
  batchError.value = null;
  batchStep.value = 'processing';

  try {
    // ── Step 1: Upload all files to server and get batch_id ──────────────
    const formData = new FormData();
    batchFiles.value.forEach((f) => formData.append('files[]', f));
    formData.append('default_category', batchCategory.value);
    formData.append('default_type', batchType.value);
    formData.append('default_status', batchStatus.value);

    const csrfToken = document.head.querySelector(
      'meta[name="csrf-token"]',
    )?.content;

    const initRes = await axios.post(
      route('knowledge-base.batch-init'),
      formData,
      {
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
      },
    );

    if (!initRes.data.success) {
      throw new Error(
        initRes.data.message || 'Failed to initialise batch upload',
      );
    }

    batchId.value = initRes.data.batch_id;
    batchFileEntries.value = initRes.data.files.map((f) => ({ ...f }));
    batchProcessed.value = 0;
    batchFailed.value = 0;

    // ── Step 2: Process each file sequentially ────────────────────────────
    for (let i = 0; i < batchFileEntries.value.length; i++) {
      batchFileEntries.value[i].status = 'processing';

      try {
        const procRes = await axios.post(
          route('knowledge-base.batch-process', {
            batchId: batchId.value,
            fileIndex: i,
          }),
          {},
          {
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
          },
        );

        const d = procRes.data;
        batchFileEntries.value[i].status = d.file_status;
        batchFileEntries.value[i].error = d.error || null;
        batchFileEntries.value[i].kb_id = d.kb_id;
        batchFileEntries.value[i].kb_title = d.kb_title;
        batchProcessed.value = d.processed;
        batchFailed.value = d.failed;
      } catch (err) {
        batchFileEntries.value[i].status = 'failed';
        batchFileEntries.value[i].error = err.response?.data?.message || err.message;
        batchProcessed.value++;
        batchFailed.value++;
      }
    }

    batchStep.value = 'completed';
  } catch (err) {
    batchError.value = err.response?.data?.message || err.message || 'Upload failed';
    batchStep.value = 'setup';
  } finally {
    batchProcessing.value = false;
  }
};
</script>

<template>
  <AppLayout title="Knowledge Base Management">
    <div class="pa-0">
      <!-- Header -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText class="pa-6">
              <VRow align="center">
                <VCol cols="12" md="6">
                  <h1 class="text-h4 font-weight-bold text-primary mb-2">
                    <VIcon class="mr-3" size="36">mdi-book-open-variant</VIcon>
                    Knowledge Base Management
                  </h1>
                  <p class="text-body-1 text-medium-emphasis">
                    Manage AI knowledge base entries and content
                  </p>
                </VCol>
                <VCol cols="12" md="6" class="text-right">
                  <VBtn
                    color="success"
                    size="large"
                    @click="openBatchDialog"
                    prepend-icon="mdi-upload-multiple"
                    class="mr-3"
                  >
                    Batch Upload
                  </VBtn>
                  <VBtn
                    color="info"
                    size="large"
                    @click="openSyncDialog"
                    prepend-icon="mdi-sync"
                    class="mr-3"
                  >
                    Rebuild Vector DB
                  </VBtn>
                  <VBtn
                    color="primary"
                    size="large"
                    @click="$inertia.visit(route('knowledge-base.create'))"
                    prepend-icon="mdi-plus"
                  >
                    Add New Entry
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Filters -->
      <VRow class="mb-4">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText>
              <VRow>
                <VCol cols="12" md="3">
                  <VTextField
                    v-model="search"
                    label="Search..."
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    @keyup.enter="applyFilters"
                    clearable
                  ></VTextField>
                </VCol>
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="categoryFilter"
                    :items="[
                      { title: 'All Categories', value: '' },
                      ...Object.entries(categories || {}).map(
                        ([key, value]) => ({ title: value, value: key }),
                      ),
                    ]"
                    label="Category"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="typeFilter"
                    :items="[
                      { title: 'All Types', value: '' },
                      ...Object.entries(types || {}).map(([key, value]) => ({
                        title: value,
                        value: key,
                      })),
                    ]"
                    label="Type"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <!-- <v-col cols="12" md="2">
                                    <v-select
                                        v-model="sourceTypeFilter"
                                        :items="sourceTypeItems"
                                        label="Source"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col> -->
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="statusFilter"
                    :items="[
                      { title: 'All Status', value: '' },
                      ...Object.entries(statuses || {}).map(([key, value]) => ({
                        title: value,
                        value: key,
                      })),
                    ]"
                    label="Status"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <VCol cols="12" md="2" class="d-flex">
                  <VBtn
                    color="primary"
                    variant="flat"
                    class="w-auto"
                    @click="applyFilters"
                  >
                    Apply
                  </VBtn>
                  <VBtn variant="outlined" @click="clearFilters" class="mx-2">
                    Clear
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Bulk Actions -->
      <VRow class="mb-4" v-if="selectedItems.length > 0">
        <VCol cols="12">
          <VCard elevation="2" color="blue-grey-lighten-5">
            <VCardText>
              <VRow align="center">
                <VCol cols="auto">
                  <span class="text-body-1 font-weight-medium">
                    {{ selectedItems.length }} items selected
                  </span>
                </VCol>
                <VCol cols="auto">
                  <VSelect
                    v-model="bulkAction"
                    :items="bulkActions"
                    variant="outlined"
                    density="compact"
                    hide-details
                    style="min-width: 200px"
                  ></VSelect>
                </VCol>
                <VCol cols="auto">
                  <VBtn
                    color="primary"
                    @click="executeBulkAction"
                    :disabled="!bulkAction"
                  >
                    Execute
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Knowledge Base Table -->
      <VRow>
        <VCol cols="12">
          <VCard elevation="2">
            <VDataTable
              v-model="selectedItems"
              :headers="headers"
              :items="filteredKnowledgeBases"
              :items-per-page="15"
              class="elevation-0"
              show-select
              item-value="id"
            >
              <!-- Title Column -->
              <template #item.title="{ item }">
                <div class="d-flex align-center">
                  <VIcon
                    :icon="getSourceIcon(item.source_type)"
                    :color="item.source_type === 'file' ? 'blue' : 'green'"
                    class="mr-3"
                    size="small"
                  ></VIcon>
                  <div>
                    <div class="font-weight-medium">
                      {{ item.title }}
                    </div>
                    <small class="text-medium-emphasis" v-if="item.excerpt">
                      {{ item.excerpt.substring(0, 100) }}...
                    </small>
                  </div>
                </div>
              </template>

              <!-- Category Column -->
              <template #item.category="{ item }">
                <VChip
                  :color="
                    item.category === 'pajak'
                      ? 'blue'
                      : item.category === 'stnk'
                        ? 'green'
                        : 'grey'
                  "
                  variant="tonal"
                  size="small"
                >
                  {{ categories[item.category] || item.category }}
                </VChip>
              </template>

              <!-- Type Column -->
              <template #item.type="{ item }">
                <VChip
                  :color="
                    item.type === 'faq'
                      ? 'orange'
                      : item.type === 'sop'
                        ? 'purple'
                        : 'blue-grey'
                  "
                  variant="tonal"
                  size="small"
                >
                  {{ types[item.type] || item.type }}
                </VChip>
              </template>

              <!-- Source Type Column -->
              <template #item.source_type="{ item }">
                <VChip
                  :color="item.source_type === 'file' ? 'blue' : 'green'"
                  variant="outlined"
                  size="small"
                >
                  <VIcon
                    :icon="getSourceIcon(item.source_type)"
                    class="mr-1"
                    size="small"
                  ></VIcon>
                  {{ item.source_type === 'file' ? 'File' : 'Manual' }}
                </VChip>
              </template>

              <!-- Status Column -->
              <template #item.status="{ item }">
                <VChip
                  :color="getStatusColor(item.status)"
                  variant="tonal"
                  size="small"
                >
                  {{ statuses[item.status] || item.status }}
                </VChip>
              </template>

              <!-- Active Column -->
              <template #item.is_active="{ item }">
                <VSwitch
                  :model-value="item.is_active"
                  @change="toggleStatus(item)"
                  color="success"
                  density="compact"
                  hide-details
                ></VSwitch>
              </template>

              <!-- Views Column -->
              <template #item.view_count="{ item }">
                <VChip color="info" variant="outlined" size="small">
                  <VIcon icon="mdi-eye" class="mr-1" size="small"></VIcon>
                  {{ item.view_count || 0 }}
                </VChip>
              </template>

              <!-- Created Date Column -->
              <template #item.created_at="{ item }">
                <div>
                  {{ formatDate(item.created_at) }}
                  <div
                    class="text-caption text-medium-emphasis"
                    v-if="item.creator"
                  >
                    by {{ item.creator.name }}
                  </div>
                </div>
              </template>

              <!-- Actions Column -->
              <template #item.actions="{ item }">
                <div class="d-flex gap-2">
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    icon="mdi-eye"
                    @click="
                      $inertia.visit(route('knowledge-base.show', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    size="small"
                    color="orange"
                    variant="tonal"
                    icon="mdi-pencil"
                    @click="
                      $inertia.visit(route('knowledge-base.edit', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    v-if="item.file_path"
                    size="small"
                    color="blue"
                    variant="tonal"
                    icon="mdi-download"
                    @click="
                      window.open(route('knowledge-base.download', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    size="small"
                    color="error"
                    variant="tonal"
                    icon="mdi-delete"
                    @click="deleteItem(item)"
                  ></VBtn>
                </div>
              </template>

              <!-- No Data -->
              <template #no-data>
                <div class="text-center pa-6">
                  <VIcon size="64" color="grey">mdi-book-open-variant</VIcon>
                  <h3 class="text-h6 mt-3">No Knowledge Base Entries Found</h3>
                  <p class="text-body-2 text-medium-emphasis">
                    Try adjusting your search criteria or create a new entry.
                  </p>
                </div>
              </template>
            </VDataTable>

            <!-- Pagination -->
            <VDivider></VDivider>
            <div class="pa-4 d-flex justify-center">
              <VPagination
                :model-value="knowledgeBases.current_page"
                :length="knowledgeBases.last_page"
                @update:model-value="
                  (page) =>
                    router.get(route('knowledge-base.index'), {
                      ...filters,
                      page,
                    })
                "
                total-visible="7"
              ></VPagination>
            </div>
          </VCard>
        </VCol>
      </VRow>

      <!-- Rebuild Pinecone Dialog -->
      <VDialog v-model="syncDialog" max-width="800" persistent>
        <VCard>
          <VCardTitle class="d-flex align-center">
            <VIcon color="warning" class="mr-2">mdi-database-refresh</VIcon>
            Rebuild Vector Database
          </VCardTitle>

          <VCardText>
            <div v-if="!syncResults">
              <VAlert type="warning" variant="outlined" class="mb-4">
                <strong>Destructive Operation:</strong> This will completely
                rebuild your vector database.
              </VAlert>
              <p class="mb-4">This operation will:</p>
              <VList density="compact">
                <VListItem>
                  <VListItemTitle
                    >• Clear ALL existing vectors from Pinecone</VListItemTitle
                  >
                </VListItem>
                <VListItem>
                  <VListItemTitle
                    >• Reindex all Knowledge Base entries from
                    scratch</VListItemTitle
                  >
                </VListItem>
                <VListItem>
                  <VListItemTitle
                    >• Ensure complete data consistency</VListItemTitle
                  >
                </VListItem>
              </VList>

              <VCheckbox
                v-model="syncDryRun"
                label="Dry run (analyze only, don't make changes)"
                color="primary"
                class="mt-4"
              ></VCheckbox>
            </div>

            <!-- Sync Results -->
            <div v-if="syncResults">
              <VAlert
                :type="syncResults.success ? 'success' : 'error'"
                class="mb-4"
                prominent
              >
                <VAlertTitle>{{ syncResults.message }}</VAlertTitle>
              </VAlert>

              <div v-if="syncResults.stats">
                <h4 class="text-h6 mb-3">Sync Statistics:</h4>
                <VRow>
                  <VCol cols="6" md="4">
                    <VCard variant="outlined" class="text-center pa-3">
                      <div class="text-h4 text-primary">
                        {{ syncResults.stats.db_entries }}
                      </div>
                      <div class="text-caption">DB Entries</div>
                    </VCard>
                  </VCol>
                  <VCol cols="6" md="4">
                    <VCard variant="outlined" class="text-center pa-3">
                      <div class="text-h4 text-warning">
                        {{ syncResults.stats.vectors_cleared }}
                      </div>
                      <div class="text-caption">Vectors Cleared</div>
                    </VCard>
                  </VCol>
                  <VCol cols="6" md="4">
                    <VCard variant="outlined" class="text-center pa-3">
                      <div class="text-h4 text-success">
                        {{ syncResults.stats.vectors_indexed }}
                      </div>
                      <div class="text-caption">Vectors Indexed</div>
                    </VCard>
                  </VCol>
                </VRow>

                <div v-if="syncResults.stats.errors > 0" class="mt-4">
                  <VAlert type="error">
                    {{ syncResults.stats.errors }} errors occurred during
                    rebuild
                  </VAlert>
                </div>
              </div>

              <div
                v-if="
                  syncResults.dry_run &&
                  syncResults.stats &&
                  syncResults.stats.db_entries > 0
                "
                class="mt-4"
              >
                <VAlert type="info">
                  <VAlertTitle>Ready to Rebuild</VAlertTitle>
                  Uncheck "Dry run" and click "Rebuild Now" to perform the
                  actual vector database rebuild.
                </VAlert>
              </div>
            </div>
          </VCardText>

          <VCardActions>
            <VSpacer></VSpacer>
            <VBtn @click="closeSyncDialog" :disabled="syncProgress">
              {{ syncResults ? 'Close' : 'Cancel' }}
            </VBtn>
            <VBtn
              v-if="!syncResults"
              color="warning"
              @click="syncPinecone"
              :loading="syncProgress"
              :disabled="syncProgress"
            >
              {{ syncDryRun ? 'Preview Rebuild' : 'Rebuild Now' }}
            </VBtn>
            <VBtn
              v-if="
                syncResults &&
                syncResults.dry_run &&
                syncResults.stats &&
                syncResults.stats.db_entries > 0
              "
              color="warning"
              @click="
                syncDryRun = false;
                syncResults = null;
                syncPinecone();
              "
              :loading="syncProgress"
              :disabled="syncProgress"
            >
              Perform Rebuild
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- ─── Batch Upload Dialog ─────────────────────────────────────────── -->
      <VDialog v-model="batchDialog" max-width="780" persistent scrollable>
        <VCard>
          <!-- Title bar -->
          <VCardTitle class="d-flex align-center pa-5 pb-3">
            <VIcon color="success" size="28" class="mr-3"
              >mdi-upload-multiple</VIcon
            >
            <span class="text-h6 font-weight-bold">Batch Upload Documents</span>
            <VSpacer />
            <VBtn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="closeBatchDialog"
              :disabled="batchProcessing"
            />
          </VCardTitle>

          <VDivider />

          <!-- ── STEP: setup ─────────────────────────────────────── -->
          <VCardText v-if="batchStep === 'setup'" class="pa-5">
            <VAlert v-if="batchError" type="error" class="mb-4" closable>{{
              batchError
            }}</VAlert>

            <!-- Drop zone -->
            <div
              class="batch-dropzone rounded-lg d-flex flex-column align-center justify-center pa-6 mb-4"
              :class="{ 'batch-dropzone--active': batchDragOver }"
              @dragover.prevent="batchDragOver = true"
              @dragleave.prevent="batchDragOver = false"
              @drop="onBatchDrop"
              @click="$refs.batchFileInput.click()"
            >
              <VIcon
                size="52"
                :color="batchDragOver ? 'success' : 'grey-lighten-1'"
                class="mb-3"
              >
                {{
                  batchDragOver
                    ? 'mdi-cloud-download'
                    : 'mdi-cloud-upload-outline'
                }}
              </VIcon>
              <p class="text-body-1 font-weight-medium text-grey-darken-1 mb-1">
                {{
                  batchDragOver
                    ? 'Drop files here'
                    : 'Drag & drop files here or click to browse'
                }}
              </p>
              <p class="text-caption text-grey">
                Supports PDF, DOC, DOCX — up to 50 MB each, max 20 files
              </p>
              <input
                ref="batchFileInput"
                type="file"
                multiple
                accept=".pdf,.doc,.docx"
                class="d-none"
                @change="onBatchFileInput"
              />
            </div>

            <!-- File list -->
            <div v-if="batchFiles.length" class="mb-4">
              <div class="d-flex align-center mb-2">
                <span class="text-subtitle-2 font-weight-semibold"
                  >Selected Files</span
                >
                <VChip size="x-small" color="primary" class="ml-2">{{
                  batchFiles.length
                }}</VChip>
              </div>
              <VCard
                variant="outlined"
                class="pa-0"
                style="max-height: 200px; overflow-y: auto"
              >
                <VList density="compact" class="pa-0">
                  <VListItem v-for="(f, i) in batchFiles" :key="i" class="px-3">
                    <template #prepend>
                      <VIcon
                        size="20"
                        :color="
                          f.name.endsWith('.pdf')
                            ? 'red-darken-2'
                            : 'blue-darken-2'
                        "
                      >
                        {{
                          f.name.endsWith('.pdf')
                            ? 'mdi-file-pdf-box'
                            : 'mdi-file-word-box'
                        }}
                      </VIcon>
                    </template>
                    <VListItemTitle class="text-body-2">{{
                      f.name
                    }}</VListItemTitle>
                    <VListItemSubtitle class="text-caption">{{
                      formatFileSize(f.size)
                    }}</VListItemSubtitle>
                    <template #append>
                      <VBtn
                        icon="mdi-close"
                        size="x-small"
                        variant="text"
                        color="error"
                        @click="removeBatchFile(i)"
                      />
                    </template>
                  </VListItem>
                </VList>
              </VCard>
            </div>

            <!-- Settings -->
            <div class="text-subtitle-2 font-weight-semibold mb-3">
              Default Settings for All Files
            </div>
            <VRow dense>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="batchCategory"
                  :items="
                    Object.entries(props.categories || {}).map(([k, v]) => ({
                      title: v,
                      value: k,
                    }))
                  "
                  label="Category *"
                  variant="outlined"
                  density="comfortable"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="batchType"
                  :items="
                    Object.entries(props.types || {}).map(([k, v]) => ({
                      title: v,
                      value: k,
                    }))
                  "
                  label="Type *"
                  variant="outlined"
                  density="comfortable"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="batchStatus"
                  :items="[
                    { title: 'Published', value: 'published' },
                    { title: 'Draft', value: 'draft' },
                    { title: 'Archived', value: 'archived' },
                  ]"
                  label="Status"
                  variant="outlined"
                  density="comfortable"
                />
              </VCol>
            </VRow>
          </VCardText>

          <!-- ── STEP: processing ────────────────────────────────── -->
          <VCardText v-else-if="batchStep === 'processing'" class="pa-5">
            <!-- Overall progress -->
            <VCard variant="tonal" color="primary" class="pa-4 mb-5 rounded-lg">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-body-1 font-weight-semibold"
                  >Processing files…</span
                >
                <span class="text-body-2 font-weight-bold text-primary">
                  {{ batchProcessed }} / {{ batchFileEntries.length }}
                </span>
              </div>
              <VProgressLinear
                :model-value="batchProgress"
                color="primary"
                bg-color="primary-lighten-4"
                rounded
                height="10"
                striped
              />
              <div class="d-flex justify-space-between mt-2">
                <span
                  class="text-caption text-success"
                  v-if="batchProcessed - batchFailed > 0"
                >
                  <VIcon size="12">mdi-check-circle</VIcon>
                  {{ batchProcessed - batchFailed }} done
                </span>
                <span class="text-caption text-error" v-if="batchFailed > 0">
                  <VIcon size="12">mdi-alert-circle</VIcon>
                  {{ batchFailed }} failed
                </span>
                <span class="text-caption text-grey">
                  {{ batchProgress }}%
                </span>
              </div>
            </VCard>

            <!-- Per-file status list -->
            <VList density="compact" class="pa-0">
              <VListItem
                v-for="(f, i) in batchFileEntries"
                :key="i"
                :class="[
                  'rounded-lg mb-1',
                  {
                    'bg-success-lighten-5': f.status === 'done',
                    'bg-error-lighten-5': f.status === 'failed',
                    'bg-blue-lighten-5': f.status === 'processing',
                  },
                ]"
              >
                <template #prepend>
                  <VIcon :color="fileStatusColor(f.status)" size="22">{{
                    fileStatusIcon(f.status)
                  }}</VIcon>
                </template>
                <VListItemTitle class="text-body-2 font-weight-medium">{{
                  f.original_name
                }}</VListItemTitle>
                <VListItemSubtitle class="text-caption">
                  <span v-if="f.status === 'done'" class="text-success">
                    ✓ {{ f.kb_title || 'Saved' }}
                  </span>
                  <span v-else-if="f.status === 'failed'" class="text-error">
                    {{ f.error || 'Processing failed' }}
                  </span>
                  <span v-else-if="f.status === 'processing'" class="text-blue">
                    Extracting text and generating embedding…
                  </span>
                  <span v-else class="text-grey">Waiting…</span>
                </VListItemSubtitle>
                <template #append>
                  <VChip
                    :color="fileStatusColor(f.status)"
                    size="x-small"
                    variant="tonal"
                    class="text-capitalize"
                    >{{ f.status }}</VChip
                  >
                </template>
              </VListItem>
            </VList>
          </VCardText>

          <!-- ── STEP: completed ─────────────────────────────────── -->
          <VCardText v-else-if="batchStep === 'completed'" class="pa-5">
            <VAlert
              :type="
                batchFailed === batchFileEntries.length
                  ? 'error'
                  : batchFailed > 0
                    ? 'warning'
                    : 'success'
              "
              prominent
              class="mb-5"
            >
              <VAlertTitle>
                {{
                  batchFailed === batchFileEntries.length
                    ? 'All files failed to process'
                    : batchFailed > 0
                      ? `Completed with ${batchFailed} error(s)`
                      : 'All files processed successfully!'
                }}
              </VAlertTitle>
              {{ batchFileEntries.length - batchFailed }} of
              {{ batchFileEntries.length }} files added to the Knowledge Base.
            </VAlert>

            <!-- Summary stats -->
            <VRow dense class="mb-4">
              <VCol cols="6" md="4">
                <VCard
                  variant="tonal"
                  color="success"
                  class="text-center pa-3 rounded-lg"
                >
                  <div class="text-h4 font-weight-bold text-success">
                    {{ batchFileEntries.length - batchFailed }}
                  </div>
                  <div class="text-caption font-weight-medium">Successful</div>
                </VCard>
              </VCol>
              <VCol cols="6" md="4">
                <VCard
                  variant="tonal"
                  :color="batchFailed > 0 ? 'error' : 'grey'"
                  class="text-center pa-3 rounded-lg"
                >
                  <div
                    class="text-h4 font-weight-bold"
                    :class="batchFailed > 0 ? 'text-error' : 'text-grey'"
                  >
                    {{ batchFailed }}
                  </div>
                  <div class="text-caption font-weight-medium">Failed</div>
                </VCard>
              </VCol>
              <VCol cols="12" md="4">
                <VCard
                  variant="tonal"
                  color="primary"
                  class="text-center pa-3 rounded-lg"
                >
                  <div class="text-h4 font-weight-bold text-primary">
                    {{ batchFileEntries.length }}
                  </div>
                  <div class="text-caption font-weight-medium">Total</div>
                </VCard>
              </VCol>
            </VRow>

            <!-- Results per file -->
            <VList
              density="compact"
              class="pa-0"
              style="max-height: 260px; overflow-y: auto"
            >
              <VListItem
                v-for="(f, i) in batchFileEntries"
                :key="i"
                :class="[
                  'rounded-lg mb-1',
                  {
                    'bg-success-lighten-5': f.status === 'done',
                    'bg-error-lighten-5': f.status === 'failed',
                  },
                ]"
              >
                <template #prepend>
                  <VIcon :color="fileStatusColor(f.status)" size="22">{{
                    fileStatusIcon(f.status)
                  }}</VIcon>
                </template>
                <VListItemTitle class="text-body-2 font-weight-medium">{{
                  f.original_name
                }}</VListItemTitle>
                <VListItemSubtitle class="text-caption">
                  <span v-if="f.status === 'done'" class="text-success">{{
                    f.kb_title
                  }}</span>
                  <span v-else class="text-error">{{ f.error }}</span>
                </VListItemSubtitle>
              </VListItem>
            </VList>
          </VCardText>

          <VDivider />

          <VCardActions class="pa-4">
            <VSpacer />
            <VBtn
              variant="outlined"
              @click="closeBatchDialog"
              :disabled="batchProcessing"
            >
              {{ batchStep === 'completed' ? 'Close & Refresh' : 'Cancel' }}
            </VBtn>
            <VBtn
              v-if="batchStep === 'setup'"
              color="success"
              variant="flat"
              prepend-icon="mdi-upload"
              :disabled="!batchFiles.length || !batchCategory"
              @click="startBatchUpload"
            >
              Upload
              {{
                batchFiles.length
                  ? batchFiles.length +
                    ' file' +
                    (batchFiles.length > 1 ? 's' : '')
                  : ''
              }}
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- Delete Confirmation Dialog -->
      <VDialog v-model="confirmDelete" max-width="400">
        <VCard>
          <VCardTitle>
            <VIcon color="error" class="mr-2">mdi-alert</VIcon>
            Confirm Deletion
          </VCardTitle>
          <VCardText>
            Are you sure you want to delete "<strong>{{
              itemToDelete?.title
            }}</strong
            >"? This action cannot be undone.
          </VCardText>
          <VCardActions>
            <VSpacer></VSpacer>
            <VBtn @click="confirmDelete = false">Cancel</VBtn>
            <VBtn color="error" @click="confirmDeleteItem">Delete</VBtn>
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

/* ── Batch Upload Dialog ──────────────────────────────────────── */
.batch-dropzone {
  border: 2px dashed #bdbdbd;
  min-height: 160px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s;
  user-select: none;
}
.batch-dropzone:hover {
  border-color: rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.04);
}
.batch-dropzone--active {
  border-color: rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.08);
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
