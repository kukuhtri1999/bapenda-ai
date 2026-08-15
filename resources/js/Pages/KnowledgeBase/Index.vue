<script setup>
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';
import { ref, computed, watch, inject } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  knowledgeBases: Object,
  filters: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const page = usePage();
const $toast = inject('$toast');

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      $toast.success(flash.success);
    }
    if (flash?.error) {
      $toast.error(flash.error);
    }
  },
  { deep: true, immediate: true }
);

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const typeFilter = ref(props.filters.type || '');
const sourceTypeFilter = ref(props.filters.source_type || '');
const statusFilter = ref(props.filters.status || '');
const activeFilter = ref(props.filters.is_active || '');
const perPage = ref(props.filters.per_page || 15);
const selectedItems = ref([]);
const bulkAction = ref('');
const exportLoading = ref(false);
const confirmDelete = ref(false);
const itemToDelete = ref(null);
const syncDialog = ref(false);
const syncProgress = ref(false);
const syncResults = ref(null);
const syncDryRun = ref(true);

// ── Fetch Vector DB (Pinecone -> MySQL) State ─────────────────────────────
const fetchDialog = ref(false);
const fetchProgress = ref(false);
const fetchProgressPercent = ref(0);
const fetchProgressMessage = ref('');
const fetchCurrentStage = ref('idle'); // 'connecting' | 'listing' | 'downloading' | 'syncing' | 'completed' | 'failed'
const fetchDryRun = ref(false);
const fetchResults = ref(null);
const fetchTipIndex = ref(0);
let fetchTipTimer = null;

const fetchGoodMoodTips = [
  '⚡ SALMA AI menggunakan arsitektur Hybrid RAG yang menggabungkan kemiripan vektor Pinecone dengan MySQL Fulltext search!',
  '☕ Tarik nafas dan santai sejenak — seluruh 101+ data pengetahuan regulasi, SOP, dan FAQ Samsat sedang disinkronkan ke database lokal.',
  '🛡️ Sinkronisasi ini memastikan data di MySQL selalu selaras dengan representasi vektor dimensi 1536 di Pinecone.',
  '📊 Setelah sinkronisasi selesai, tabel Knowledge Base akan langsung terisi dengan artikel siap pakai dan akurat.',
  '🚀 Kecepatan pencarian rata-rata SALMA AI adalah di bawah 35ms untuk kueri yang terindeks.',
];

const headers = [
  { title: 'Title', key: 'title', sortable: true },
  { title: 'Category', key: 'category', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  // { title: "Source", key: "source_type", sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Active', key: 'is_active', sortable: true },
  // { title: "Views", key: "view_count", sortable: true },
  { title: 'Quality', key: 'quality_score', sortable: true },
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
  { title: 'Export to Word', value: 'export_word' },
];

// ── Quality Score & Enhance ─────────────────────────────────────────────────
// Track per-item loading states using item IDs
const scoringItems = ref(new Set());
const enhancingItems = ref(new Set());
// Local score overrides keyed by item id (for optimistic UI update)
const localScores = ref({});
// Enhance result dialog
const enhanceDialog = ref(false);
const enhanceResult = ref(null);
const enhanceError = ref(null);

const getScore = (target) => {
  if (target === null || target === undefined || target === '') return null;
  // If target is an object (item or item.raw)
  if (typeof target === 'object') {
    const id = target.id ?? target.raw?.id;
    if (id !== undefined && localScores.value[id] !== undefined) {
      return localScores.value[id];
    }
    const val = target.quality_score ?? target.raw?.quality_score ?? target.columns?.quality_score;
    if (val !== undefined && val !== null && val !== '') {
      const num = parseFloat(val);
      return isNaN(num) ? null : num;
    }
    return null;
  }
  // If target is already a primitive number or numeric string
  const num = parseFloat(target);
  return isNaN(num) ? null : num;
};

const formatQualityScore = (target) => {
  const score = getScore(target);
  if (score === null || score === undefined) return null;
  const normalized = score > 1 ? score / 100 : score;
  return (normalized * 100).toFixed(1) + '%';
};

const getScoreRaw = (target) => {
  const score = getScore(target);
  if (score === null || score === undefined) return '—';
  const normalized = score > 1 ? score / 100 : score;
  return normalized.toFixed(4);
};

const scoreColorClass = (target) => {
  const score = getScore(target);
  if (score === null || score === undefined) return 'qs-none';
  const val = score > 1 ? score / 100 : score;
  if (val >= 0.8) return 'qs-high'; // Green (>= 80%)
  if (val >= 0.6) return 'qs-mid';  // Amber (60% - 79.9%)
  return 'qs-low';                  // Red (< 60%)
};

const computeScore = async (target) => {
  const item = target?.raw ?? target;
  const id = item?.id;
  if (!id || scoringItems.value.has(id)) return;

  scoringItems.value = new Set([...scoringItems.value, id]);
  try {
    const csrfToken = document.head.querySelector(
      'meta[name="csrf-token"]',
    )?.content;
    const res = await axios.post(
      route('knowledge-base.score', id),
      {},
      { headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } },
    );
    if (res.data.success) {
      localScores.value = { ...localScores.value, [id]: res.data.score };
      item.quality_score = res.data.score;
      if ($toast) $toast.success(res.data.message || `Skor Pinecone diperbarui: ${res.data.percentage}`);
    } else {
      if ($toast) $toast.error(res.data.message || 'Gagal menghitung skor.');
    }
  } catch (err) {
    console.error('Score error:', err);
    if ($toast) $toast.error(err.response?.data?.message || 'Gagal memperbarui skor.');
  } finally {
    const s = new Set(scoringItems.value);
    s.delete(id);
    scoringItems.value = s;
  }
};

const enhanceItem = async (target) => {
  const item = target?.raw ?? target;
  const id = item?.id;
  if (!id || enhancingItems.value.has(id)) return;

  enhanceResult.value = null;
  enhanceError.value = null;
  enhancingItems.value = new Set([...enhancingItems.value, id]);
  try {
    const csrfToken = document.head.querySelector(
      'meta[name="csrf-token"]',
    )?.content;
    const res = await axios.post(
      route('knowledge-base.enhance', id),
      {},
      { headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } },
    );
    if (res.data.success) {
      enhanceResult.value = res.data;
      if (res.data.quality_score !== undefined) {
        localScores.value = {
          ...localScores.value,
          [id]: res.data.quality_score,
        };
        item.quality_score = res.data.quality_score;
      }
      if (res.data.title) item.title = res.data.title;
      enhanceDialog.value = true;
      if ($toast) $toast.success(`✨ ${res.data.message}`);
      router.reload({ only: ['knowledgeBases'] });
    } else {
      enhanceError.value = res.data.message || 'Enhancement failed';
      enhanceDialog.value = true;
      if ($toast) $toast.error(res.data.message || 'Gagal meningkatkan konten.');
    }
  } catch (err) {
    enhanceError.value = err.response?.data?.message || err.message || 'Request failed';
    enhanceDialog.value = true;
    if ($toast) $toast.error(err.response?.data?.message || 'Gagal meningkatkan konten dengan GPT-5.6 Terra.');
  } finally {
    const s = new Set(enhancingItems.value);
    s.delete(id);
    enhancingItems.value = s;
  }
};

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
      per_page: perPage.value,
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
  perPage.value = 15;
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

  if (bulkAction.value === 'export_word') {
    const count = selectedItems.value.length;
    exportLoading.value = true;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('knowledge-base.bulk-action');

    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_word';
    form.appendChild(actionInput);

    selectedItems.value.forEach((id) => {
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'ids[]';
      idInput.value = id;
      form.appendChild(idInput);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Dismiss loading after a generous delay (browser triggers download and navigates back)
    setTimeout(() => {
      exportLoading.value = false;
    }, 8000);

    selectedItems.value = [];
    bulkAction.value = '';
    return;
  }

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

// ── Fetch Vector DB Functions ──────────────────────────────────────────────
const openFetchDialog = () => {
  fetchDialog.value = true;
  fetchProgress.value = false;
  fetchProgressPercent.value = 0;
  fetchProgressMessage.value = '';
  fetchCurrentStage.value = 'idle';
  fetchDryRun.value = false;
  fetchResults.value = null;
  fetchTipIndex.value = 0;
};

const startFetchPinecone = async () => {
  try {
    fetchProgress.value = true;
    fetchCurrentStage.value = 'connecting';
    fetchProgressPercent.value = 15;
    fetchProgressMessage.value = 'Menghubungkan ke Pinecone Vector Database Cluster...';
    fetchResults.value = null;

    if (fetchTipTimer) clearInterval(fetchTipTimer);
    fetchTipTimer = setInterval(() => {
      fetchTipIndex.value = (fetchTipIndex.value + 1) % fetchGoodMoodTips.length;
    }, 3500);

    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

    const response = await axios.post(
      route('knowledge-base.fetch-pinecone'),
      {
        dry_run: fetchDryRun.value,
      },
      {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json',
        },
      },
    );

    if (fetchTipTimer) clearInterval(fetchTipTimer);
    fetchProgressPercent.value = 100;
    fetchCurrentStage.value = 'completed';
    fetchProgressMessage.value = 'Sinkronisasi Pinecone ke MySQL selesai!';
    fetchResults.value = response.data;

    if (!fetchDryRun.value) {
      router.reload({ only: ['knowledgeBases'] });
    }
  } catch (error) {
    if (fetchTipTimer) clearInterval(fetchTipTimer);
    console.error('Fetch Pinecone error:', error);
    fetchCurrentStage.value = 'failed';
    fetchResults.value = {
      success: false,
      message: error.response?.data?.message || 'Gagal menarik data dari Pinecone Vector DB.',
      stats: null,
    };
  } finally {
    fetchProgress.value = false;
  }
};

const closeFetchDialog = () => {
  if (fetchTipTimer) clearInterval(fetchTipTimer);
  fetchDialog.value = false;
  if (fetchResults.value?.success && !fetchDryRun.value) {
    router.reload({ only: ['knowledgeBases'] });
  }
  fetchResults.value = null;
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

const kbSteps = [
  { title: 'Knowledge Base', intro: 'Halaman ini adalah pusat pengelolaan basis pengetahuan SALMA AI. Semua artikel, dokumen, dan informasi yang digunakan AI untuk menjawab pertanyaan dikelola di sini.' },
  { element: '#tour-kb-header', title: 'Header & Tombol Aksi', intro: 'Di sini Anda bisa klik "Tambah KB" untuk membuat entri baru, atau "Sinkronisasi Vektor" untuk memperbarui data AI setelah perubahan.' },
  { element: '#tour-kb-filter', title: 'Filter & Pencarian', intro: 'Cari konten berdasarkan kata kunci, filter berdasarkan kategori, tipe, status aktif/tidak aktif. Gunakan dropdown untuk mengubah jumlah data per halaman.' },
  { element: '#tour-kb-table', title: 'Daftar Knowledge Base', intro: 'Setiap baris menampilkan judul, kategori, tipe (manual/PDF/URL), status aktif, dan tanggal. Klik ikon untuk melihat detail, mengedit, atau menghapus entri.' },
  { element: '#tour-kb-bulk', title: 'Aksi Massal', intro: 'Centang beberapa item sekaligus, lalu gunakan toolbar aksi massal ini untuk mengaktifkan, menonaktifkan, atau menghapus beberapa entri sekaligus.' },
];
const { startTour } = useTour(kbSteps);

</script>

<template>
  <AppLayout title="Knowledge Base Management">
    <div class="kb-page">
      <!-- ── Export loading overlay ──────────────────────────────────────────── -->
      <VOverlay
        v-model="exportLoading"
        class="d-flex align-center justify-center"
        persistent
        z-index="9999"
      >
        <VCard class="pa-6 text-center" rounded="xl" style="min-width:260px">
          <VProgressCircular indeterminate color="primary" size="52" class="mb-4" />
          <div class="text-h6 font-weight-semibold mb-1">Generating Word File</div>
          <div class="text-body-2 text-medium-emphasis">
            Processing your selected entries…<br>
            This may take a moment for large exports.
          </div>
        </VCard>
      </VOverlay>

      <!-- ── Page header ──────────────────────────────────────────────────── -->
      <div id="tour-kb-header" class="kb-header mb-5">
        <div class="d-flex align-center justify-space-between flex-wrap gap-3">
          <div class="d-flex align-center gap-3">
            <div class="kb-header-icon">
              <VIcon size="22" color="white">mdi-book-open-variant</VIcon>
            </div>
            <div>
              <h1 class="text-h5 font-weight-bold text-grey-darken-4">
                Knowledge Base
              </h1>
              <p class="text-caption text-medium-emphasis mb-0">
                Manage AI knowledge base entries and content
              </p>
            </div>
          </div>
          <div class="d-flex align-center gap-2 flex-wrap">
            <VBtn
              variant="flat"
              color="indigo-darken-1"
              size="small"
              prepend-icon="mdi-cloud-download"
              class="elevation-1"
              @click="openFetchDialog"
              >Fetch Vector DB</VBtn
            >
            <VBtn
              variant="outlined"
              color="grey-darken-1"
              size="small"
              prepend-icon="mdi-sync"
              @click="openSyncDialog"
              >Rebuild Vector DB</VBtn
            >
            <VBtn
              variant="outlined"
              color="success"
              size="small"
              prepend-icon="mdi-upload-multiple"
              @click="openBatchDialog"
              >Batch Upload</VBtn
            >
            <VBtn
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
              @click="$inertia.visit(route('knowledge-base.create'))"
              >Add New Entry</VBtn
            >
          </div>
        </div>
      </div>

      <!-- ── Filters ──────────────────────────────────────────────────────── -->
      <div id="tour-kb-filter" class="kb-section mb-4 pa-4">
        <VRow dense align="center">
          <VCol cols="12" sm="4" md="3">
            <VTextField
              v-model="search"
              placeholder="Search entries..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              @keyup.enter="applyFilters"
              clearable
            />
          </VCol>
          <VCol cols="6" sm="4" md="2">
            <VSelect
              v-model="categoryFilter"
              :items="[
                { title: 'All Categories', value: '' },
                ...Object.entries(categories || {}).map(([k, v]) => ({
                  title: v,
                  value: k,
                })),
              ]"
              label="Category"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="6" sm="4" md="2">
            <VSelect
              v-model="typeFilter"
              :items="[
                { title: 'All Types', value: '' },
                ...Object.entries(types || {}).map(([k, v]) => ({
                  title: v,
                  value: k,
                })),
              ]"
              label="Type"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="6" sm="4" md="2">
            <VSelect
              v-model="statusFilter"
              :items="[
                { title: 'All Status', value: '' },
                ...Object.entries(statuses || {}).map(([k, v]) => ({
                  title: v,
                  value: k,
                })),
              ]"
              label="Status"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="6" sm="4" md="2">
            <VSelect
              v-model="perPage"
              :items="[
                { title: '5 per page', value: 5 },
                { title: '10 per page', value: 10 },
                { title: '15 per page', value: 15 },
                { title: '25 per page', value: 25 },
                { title: '50 per page', value: 50 },
                { title: '100 per page', value: 100 }
              ]"
              label="Show"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="applyFilters"
            />
          </VCol>
          <VCol cols="6" sm="auto" class="d-flex gap-2">
            <VBtn
              color="primary"
              size="small"
              variant="flat"
              @click="applyFilters"
              >Apply</VBtn
            >
            <VBtn size="small" variant="outlined" @click="clearFilters"
              >Clear</VBtn
            >
          </VCol>
        </VRow>
      </div>

      <!-- ── Bulk action bar ──────────────────────────────────────────────── -->
      <Transition name="slide-down">
        <div v-if="selectedItems.length > 0" id="tour-kb-bulk" class="kb-bulk-bar mb-3">
          <div class="d-flex align-center gap-3 flex-wrap">
            <VIcon color="primary" size="18"
              >mdi-checkbox-multiple-marked</VIcon
            >
            <span class="text-body-2 font-weight-medium"
              >{{ selectedItems.length }} selected</span
            >
            <VSelect
              v-model="bulkAction"
              :items="bulkActions"
              variant="outlined"
              density="compact"
              hide-details
              style="min-width: 180px; max-width: 220px"
            />
            <VBtn
              size="small"
              color="primary"
              variant="flat"
              :disabled="!bulkAction"
              @click="executeBulkAction"
            >
              Execute
            </VBtn>
            <VSpacer />
            <VBtn
              size="small"
              variant="text"
              color="error"
              @click="selectedItems = []"
            >
              <VIcon size="16" class="mr-1">mdi-close</VIcon>
              Deselect all
            </VBtn>
          </div>
        </div>
      </Transition>

      <!-- ── Data table ───────────────────────────────────────────────────── -->
      <div id="tour-kb-table" class="kb-section">
        <VDataTable
          v-model="selectedItems"
          :headers="headers"
          :items="filteredKnowledgeBases"
          :items-per-page="-1"
          class="kb-table"
          show-select
          item-value="id"
          hover
          hide-default-footer
        >
          <!-- Title column -->
          <template #item.title="{ item }">
            <div class="d-flex align-center gap-2 py-1">
              <VIcon
                :icon="getSourceIcon(item.source_type)"
                :color="item.source_type === 'file' ? 'blue' : 'teal'"
                size="16"
              />
              <div>
                <div class="text-body-2 font-weight-medium text-grey-darken-4">
                  {{ item.title }}
                </div>
                <div
                  class="text-caption text-medium-emphasis"
                  v-if="item.excerpt"
                >
                  {{ item.excerpt.substring(0, 80) }}…
                </div>
              </div>
            </div>
          </template>

          <!-- Category column -->
          <template #item.category="{ item }">
            <VChip
              size="x-small"
              :color="
                item.category === 'pajak'
                  ? 'blue'
                  : item.category === 'stnk'
                    ? 'teal'
                    : 'grey'
              "
              variant="tonal"
              class="font-weight-medium"
              >{{ categories[item.category] || item.category }}</VChip
            >
          </template>

          <!-- Type column -->
          <template #item.type="{ item }">
            <VChip
              size="x-small"
              :color="
                item.type === 'faq'
                  ? 'orange'
                  : item.type === 'sop'
                    ? 'purple'
                    : item.type === 'tambahan_sistem'
                      ? 'deep-orange'
                      : 'blue-grey'
              "
              variant="tonal"
              class="font-weight-medium"
              >{{ types[item.type] || item.type }}</VChip
            >
            <VChip
              v-if="item.type === 'tambahan_sistem'"
              size="x-small"
              color="red"
              variant="flat"
              label
              class="ml-1 font-weight-bold"
              >Koreksi AI</VChip
            >
          </template>

          <!-- Status column -->
          <template #item.status="{ item }">
            <VChip
              size="x-small"
              :color="getStatusColor(item.status)"
              variant="tonal"
              class="font-weight-medium"
              >{{ statuses[item.status] || item.status }}</VChip
            >
          </template>

          <!-- Active toggle column -->
          <template #item.is_active="{ item }">
            <VSwitch
              :model-value="item.is_active"
              @change="toggleStatus(item)"
              color="success"
              density="compact"
              hide-details
              inset
            />
          </template>

          <!-- Date column -->
          <template #item.created_at="{ item }">
            <div class="text-body-2">{{ formatDate(item.created_at) }}</div>
            <div class="text-caption text-medium-emphasis" v-if="item.creator">
              by {{ item.creator.name }}
            </div>
          </template>

          <!-- Quality Score column -->
          <template #item.quality_score="{ item, value }">
            <div class="d-flex align-center gap-1">
              <span
                v-if="formatQualityScore(value ?? item) !== null"
                :class="['qs-badge', scoreColorClass(value ?? item)]"
                :title="`Pinecone Vector Match Score: ${getScoreRaw(value ?? item)}`"
                >{{ formatQualityScore(value ?? item) }}</span
              >
              <span v-else class="qs-badge qs-none">—</span>
              <VBtn
                :loading="scoringItems.has(item?.id ?? item?.raw?.id)"
                size="x-small"
                icon
                variant="text"
                color="grey"
                title="Perbarui skor dari Pinecone Vector DB"
                @click="computeScore(item)"
              >
                <VIcon size="13">mdi-refresh</VIcon>
              </VBtn>
            </div>
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex gap-1 align-center">
              <VBtn
                size="x-small"
                icon
                variant="text"
                color="primary"
                @click="$inertia.visit(route('knowledge-base.show', item.id))"
                ><VIcon size="16">mdi-eye</VIcon></VBtn
              >
              <VBtn
                size="x-small"
                icon
                variant="text"
                color="orange-darken-1"
                @click="$inertia.visit(route('knowledge-base.edit', item.id))"
                ><VIcon size="16">mdi-pencil</VIcon></VBtn
              >
              <VBtn
                v-if="item.file_path"
                size="x-small"
                icon
                variant="text"
                color="blue"
                @click="window.open(route('knowledge-base.download', item.id))"
                ><VIcon size="16">mdi-download</VIcon></VBtn
              >
              <!-- Enhance with AI GPT-5.6 Terra -->
              <VTooltip text="Enhance dengan AI GPT-5.6 Terra" location="top">
                <template #activator="{ props: tip }">
                  <VBtn
                    v-bind="tip"
                    size="x-small"
                    icon
                    variant="text"
                    :color="
                      enhancingItems.has(item.id) ? 'grey' : 'deep-purple'
                    "
                    :loading="enhancingItems.has(item.id)"
                    @click="enhanceItem(item)"
                    ><VIcon size="16">mdi-auto-fix</VIcon></VBtn
                  >
                </template>
              </VTooltip>
              <VBtn
                size="x-small"
                icon
                variant="text"
                color="error"
                @click="deleteItem(item)"
                ><VIcon size="16">mdi-delete</VIcon></VBtn
              >
            </div>
          </template>

          <!-- Empty state -->
          <template #no-data>
            <div class="text-center py-12">
              <VIcon size="52" color="grey-lighten-2"
                >mdi-book-open-variant</VIcon
              >
              <div
                class="text-subtitle-1 mt-3 text-medium-emphasis font-weight-medium"
              >
                No entries found
              </div>
              <div class="text-caption text-medium-emphasis mt-1">
                Try adjusting your filters or add a new entry
              </div>
              <VBtn
                class="mt-4"
                size="small"
                color="primary"
                prepend-icon="mdi-plus"
                @click="$inertia.visit(route('knowledge-base.create'))"
              >
                Add First Entry
              </VBtn>
            </div>
          </template>
        </VDataTable>

        <!-- Pagination -->
        <VDivider />
        <div class="pa-3 d-flex flex-wrap align-center justify-space-between gap-2 px-4">
          <span class="text-caption text-medium-emphasis">
            Showing {{ knowledgeBases.from ?? 0 }}–{{ knowledgeBases.to ?? 0 }} of {{ knowledgeBases.total ?? 0 }} entries
          </span>
          <VPagination
            :model-value="knowledgeBases.current_page"
            :length="knowledgeBases.last_page"
            @update:model-value="
              (page) =>
                router.get(route('knowledge-base.index'), {
                  search: search,
                  category: categoryFilter,
                  type: typeFilter,
                  source_type: sourceTypeFilter,
                  status: statusFilter,
                  is_active: activeFilter,
                  per_page: perPage,
                  page,
                })
            "
            total-visible="7"
            size="small"
          />
        </div>
      </div>

      <!-- ── Rebuild Vector DB Dialog ─────────────────────────────────────── -->
      <VDialog v-model="syncDialog" max-width="640" persistent>
        <VCard rounded="lg" border>
          <VCardTitle class="d-flex align-center px-5 pt-5 pb-0">
            <div
              class="kb-dialog-icon mr-3"
              style="background: rgba(var(--v-theme-warning), 0.12)"
            >
              <VIcon color="warning" size="20">mdi-database-refresh</VIcon>
            </div>
            <span class="text-h6 font-weight-bold"
              >Rebuild Vector Database</span
            >
            <VSpacer />
            <VBtn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="closeSyncDialog"
              :disabled="syncProgress"
            />
          </VCardTitle>

          <VCardText class="px-5 pt-4 pb-2">
            <div v-if="!syncResults">
              <VAlert
                type="warning"
                variant="tonal"
                density="compact"
                class="mb-4"
              >
                <strong>Destructive Operation:</strong> This will completely
                rebuild your vector database.
              </VAlert>
              <VList density="compact" class="pa-0 mb-3">
                <VListItem
                  v-for="step in [
                    'Clear ALL existing vectors from Pinecone',
                    'Reindex all Knowledge Base entries from scratch',
                    'Ensure complete data consistency',
                  ]"
                  :key="step"
                  class="px-0"
                >
                  <template #prepend
                    ><VIcon size="16" color="warning" class="mr-2"
                      >mdi-alert-circle-outline</VIcon
                    ></template
                  >
                  <VListItemTitle class="text-body-2">{{
                    step
                  }}</VListItemTitle>
                </VListItem>
              </VList>
              <VCheckbox
                v-model="syncDryRun"
                label="Dry run (analyze only, don't make changes)"
                color="primary"
                density="compact"
                hide-details
              />
            </div>
            <div v-if="syncResults">
              <VAlert
                :type="syncResults.success ? 'success' : 'error'"
                variant="tonal"
                class="mb-4"
                prominent
              >
                <VAlertTitle>{{ syncResults.message }}</VAlertTitle>
              </VAlert>
              <div v-if="syncResults.stats">
                <div class="text-subtitle-2 font-weight-semibold mb-3">
                  Statistics
                </div>
                <div class="d-flex gap-3">
                  <div class="stat-chip">
                    <div class="text-h5 font-weight-bold text-primary">
                      {{ syncResults.stats.db_entries }}
                    </div>
                    <div class="text-caption">DB Entries</div>
                  </div>
                  <div class="stat-chip">
                    <div class="text-h5 font-weight-bold text-warning">
                      {{ syncResults.stats.vectors_cleared }}
                    </div>
                    <div class="text-caption">Cleared</div>
                  </div>
                  <div class="stat-chip">
                    <div class="text-h5 font-weight-bold text-success">
                      {{ syncResults.stats.vectors_indexed }}
                    </div>
                    <div class="text-caption">Indexed</div>
                  </div>
                </div>
              </div>
              <VAlert
                v-if="
                  syncResults.dry_run &&
                  syncResults.stats &&
                  syncResults.stats.db_entries > 0
                "
                type="info"
                variant="tonal"
                density="compact"
                class="mt-4"
              >
                Uncheck "Dry run" and click <strong>Rebuild Now</strong> to
                proceed.
              </VAlert>
            </div>
          </VCardText>

          <VCardActions class="px-5 pb-4 pt-2">
            <VSpacer />
            <VBtn
              variant="outlined"
              @click="closeSyncDialog"
              :disabled="syncProgress"
            >
              {{ syncResults ? 'Close' : 'Cancel' }}
            </VBtn>
            <VBtn
              v-if="!syncResults"
              color="warning"
              variant="flat"
              @click="syncPinecone"
              :loading="syncProgress"
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
              variant="flat"
              @click="
                syncDryRun = false;
                syncResults = null;
                syncPinecone();
              "
              :loading="syncProgress"
            >
              Perform Rebuild
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- ── Fetch Vector DB Dialog with Interactive Good Mood Experience ──── -->
      <VDialog v-model="fetchDialog" max-width="640" persistent>
        <VCard rounded="xl" border class="overflow-hidden">
          <!-- Header gradient bar -->
          <div
            style="
              height: 6px;
              background: linear-gradient(90deg, #6366f1, #06b6d4, #10b981);
            "
          ></div>

          <VCardTitle class="d-flex align-center px-6 pt-5 pb-3">
            <div
              class="kb-dialog-icon mr-3"
              style="background: rgba(99, 102, 241, 0.12); color: #6366f1"
            >
              <VIcon size="22">mdi-cloud-download</VIcon>
            </div>
            <div>
              <span class="text-h6 font-weight-bold">Fetch Vector Database</span>
              <p class="text-caption text-medium-emphasis mb-0">
                Tarik & sinkronkan seluruh Knowledge Base dari Pinecone ke MySQL
              </p>
            </div>
            <VSpacer />
            <VBtn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="closeFetchDialog"
              :disabled="fetchProgress"
            />
          </VCardTitle>
          <VDivider />

          <VCardText class="px-6 py-5">
            <!-- State 1: Ready / Confirmation Form -->
            <div v-if="!fetchProgress && !fetchResults">
              <VAlert
                type="info"
                variant="tonal"
                density="compact"
                class="mb-4"
              >
                <strong>Sinkronisasi Data Dua Arah:</strong> Mengunduh seluruh
                representasi vektor dan metadata artikel yang tersimpan di
                Pinecone Cloud, lalu memperbarui atau membuat entri di MySQL
                secara aman.
              </VAlert>

              <div class="pa-4 rounded-lg mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0">
                <div class="text-subtitle-2 font-weight-semibold mb-2 d-flex align-center">
                  <VIcon size="18" color="indigo" class="mr-2">mdi-checkbox-marked-circle-outline</VIcon>
                  Tahapan Operasi:
                </div>
                <div class="text-body-2 text-grey-darken-2 pl-6">
                  1. Menghubungkan ke cluster Pinecone <code>bapenda-kb</code>.<br />
                  2. Memindai seluruh Vector IDs & mengunduh metadata lengkap.<br />
                  3. Menggabungkan artikel multi-part & memperbarui tabel MySQL.<br />
                  4. Menghasilkan skor kualitas & indeks pencarian otomatis.
                </div>
              </div>

              <VCheckbox
                v-model="fetchDryRun"
                label="Dry run (Pratinjau saja, jangan simpan perubahan ke database)"
                color="primary"
                density="compact"
                hide-details
              />
            </div>

            <!-- State 2: Active Loading & Progress ("Good Mood") -->
            <div v-if="fetchProgress" class="py-4 text-center">
              <!-- Orbit Animation -->
              <div class="d-flex justify-center mb-4">
                <div
                  class="d-flex align-center justify-center rounded-circle elevation-2"
                  style="
                    width: 72px;
                    height: 72px;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    color: white;
                    animation: pulse 2s infinite;
                  "
                >
                  <VIcon size="36" class="mdi-spin">mdi-sync</VIcon>
                </div>
              </div>

              <div class="text-h6 font-weight-bold text-grey-darken-3 mb-1">
                {{ fetchProgressMessage || 'Sedang Menyinkronkan Data...' }}
              </div>
              <div class="text-caption text-medium-emphasis mb-4">
                Mohon tunggu beberapa detik, sistem sedang mengambil dokumen dari vector cloud.
              </div>

              <!-- Animated Progress Bar -->
              <VProgressLinear
                v-model="fetchProgressPercent"
                color="indigo"
                height="10"
                rounded
                striped
                indeterminate
                class="mb-4"
              />

              <!-- Rotating Good Mood Box -->
              <div
                class="pa-3 rounded-lg text-left d-flex align-start gap-3 mt-4"
                style="background: #eef2ff; border: 1px solid #c7d2fe"
              >
                <VIcon color="indigo" size="20" class="mt-1">mdi-lightbulb-on</VIcon>
                <div class="text-caption text-indigo-darken-4 font-weight-medium">
                  {{ fetchGoodMoodTips[fetchTipIndex] }}
                </div>
              </div>
            </div>

            <!-- State 3: Results Summary -->
            <div v-if="fetchResults && !fetchProgress">
              <VAlert
                :type="fetchResults.success ? 'success' : 'error'"
                variant="tonal"
                class="mb-4"
                prominent
              >
                <VAlertTitle class="font-weight-bold">{{ fetchResults.message }}</VAlertTitle>
              </VAlert>

              <div v-if="fetchResults.stats">
                <div class="text-subtitle-2 font-weight-semibold mb-3">
                  Ringkasan Sinkronisasi
                </div>
                <div class="d-flex gap-3 mb-4 flex-wrap">
                  <div class="stat-chip flex-1" style="min-width: 110px">
                    <div class="text-h5 font-weight-bold text-indigo">
                      {{ fetchResults.stats.total_vectors || 0 }}
                    </div>
                    <div class="text-caption">Total Vektor</div>
                  </div>
                  <div class="stat-chip flex-1" style="min-width: 110px">
                    <div class="text-h5 font-weight-bold text-success">
                      {{ fetchResults.stats.db_created || 0 }}
                    </div>
                    <div class="text-caption">Entri Baru</div>
                  </div>
                  <div class="stat-chip flex-1" style="min-width: 110px">
                    <div class="text-h5 font-weight-bold text-warning">
                      {{ fetchResults.stats.db_updated || 0 }}
                    </div>
                    <div class="text-caption">Diperbarui</div>
                  </div>
                </div>

                <!-- Category Breakdown -->
                <div v-if="fetchResults.stats.categories && Object.keys(fetchResults.stats.categories).length" class="mb-3">
                  <div class="text-caption font-weight-bold text-medium-emphasis mb-2">
                    DISTRIBUSI KATEGORI:
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <VChip
                      v-for="(count, cat) in fetchResults.stats.categories"
                      :key="cat"
                      size="small"
                      color="indigo"
                      variant="tonal"
                    >
                      <strong>{{ cat }}</strong>: {{ count }}
                    </VChip>
                  </div>
                </div>
              </div>
            </div>
          </VCardText>

          <VDivider />
          <VCardActions class="px-6 py-4 justify-end gap-2">
            <VBtn
              variant="outlined"
              color="grey-darken-1"
              @click="closeFetchDialog"
              :disabled="fetchProgress"
            >
              {{ fetchResults ? 'Tutup' : 'Batal' }}
            </VBtn>

            <VBtn
              v-if="!fetchResults"
              color="indigo-darken-1"
              variant="flat"
              prepend-icon="mdi-cloud-download"
              :loading="fetchProgress"
              @click="startFetchPinecone"
            >
              {{ fetchDryRun ? 'Pratinjau Sinkronisasi' : 'Tarik Data Sekarang' }}
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- ── Batch Upload Dialog ─────────────────────────────────────────── -->
      <VDialog v-model="batchDialog" max-width="720" persistent scrollable>
        <VCard rounded="lg" border>
          <VCardTitle class="d-flex align-center px-5 pt-5 pb-3">
            <div
              class="kb-dialog-icon mr-3"
              style="background: rgba(var(--v-theme-success), 0.12)"
            >
              <VIcon color="success" size="20">mdi-upload-multiple</VIcon>
            </div>
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

          <!-- setup step -->
          <VCardText v-if="batchStep === 'setup'" class="pa-5">
            <VAlert
              v-if="batchError"
              type="error"
              variant="tonal"
              density="compact"
              class="mb-4"
              closable
              >{{ batchError }}</VAlert
            >
            <div
              class="batch-dropzone rounded-lg d-flex flex-column align-center justify-center pa-6 mb-4"
              :class="{ 'batch-dropzone--active': batchDragOver }"
              @dragover.prevent="batchDragOver = true"
              @dragleave.prevent="batchDragOver = false"
              @drop="onBatchDrop"
              @click="$refs.batchFileInput.click()"
            >
              <VIcon
                size="44"
                :color="batchDragOver ? 'success' : 'grey-lighten-1'"
                class="mb-2"
              >
                {{
                  batchDragOver
                    ? 'mdi-cloud-download'
                    : 'mdi-cloud-upload-outline'
                }}
              </VIcon>
              <p class="text-body-2 font-weight-medium text-grey-darken-1 mb-1">
                {{
                  batchDragOver
                    ? 'Drop files here'
                    : 'Drag & drop files or click to browse'
                }}
              </p>
              <p class="text-caption text-grey-darken-1">
                PDF, DOC, DOCX — max 50 MB each
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

            <div v-if="batchFiles.length" class="mb-4">
              <div class="d-flex align-center mb-2">
                <span class="text-subtitle-2 font-weight-semibold"
                  >Selected Files</span
                >
                <VChip size="x-small" color="primary" class="ml-2">{{
                  batchFiles.length
                }}</VChip>
              </div>
              <div class="kb-file-list">
                <div v-for="(f, i) in batchFiles" :key="i" class="kb-file-row">
                  <VIcon
                    size="18"
                    :color="
                      f.name.endsWith('.pdf') ? 'red-darken-2' : 'blue-darken-2'
                    "
                  >
                    {{
                      f.name.endsWith('.pdf')
                        ? 'mdi-file-pdf-box'
                        : 'mdi-file-word-box'
                    }}
                  </VIcon>
                  <div class="flex-1 min-w-0">
                    <div class="text-body-2 text-truncate">{{ f.name }}</div>
                    <div class="text-caption text-medium-emphasis">
                      {{ formatFileSize(f.size) }}
                    </div>
                  </div>
                  <VBtn
                    icon="mdi-close"
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="removeBatchFile(i)"
                  />
                </div>
              </div>
            </div>

            <div class="text-subtitle-2 font-weight-semibold mb-3">
              Default Settings
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
                  density="compact"
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
                  density="compact"
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
                  density="compact"
                />
              </VCol>
            </VRow>
          </VCardText>

          <!-- processing step -->
          <VCardText v-else-if="batchStep === 'processing'" class="pa-5">
            <div class="kb-progress-card mb-5">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-body-2 font-weight-semibold"
                  >Processing files…</span
                >
                <span class="text-body-2 font-weight-bold text-primary"
                  >{{ batchProcessed }} / {{ batchFileEntries.length }}</span
                >
              </div>
              <VProgressLinear
                :model-value="batchProgress"
                color="primary"
                rounded
                height="8"
              />
              <div class="d-flex justify-space-between mt-2">
                <span
                  class="text-caption text-success"
                  v-if="batchProcessed - batchFailed > 0"
                  ><VIcon size="12">mdi-check-circle</VIcon>
                  {{ batchProcessed - batchFailed }} done</span
                >
                <span class="text-caption text-error" v-if="batchFailed > 0"
                  ><VIcon size="12">mdi-alert-circle</VIcon>
                  {{ batchFailed }} failed</span
                >
                <span class="text-caption text-medium-emphasis"
                  >{{ batchProgress }}%</span
                >
              </div>
            </div>
            <div class="kb-file-list">
              <div
                v-for="(f, i) in batchFileEntries"
                :key="i"
                class="kb-file-row"
              >
                <VIcon :color="fileStatusColor(f.status)" size="18">{{
                  fileStatusIcon(f.status)
                }}</VIcon>
                <div class="flex-1 min-w-0">
                  <div class="text-body-2 font-weight-medium text-truncate">
                    {{ f.original_name }}
                  </div>
                  <div class="text-caption">
                    <span v-if="f.status === 'done'" class="text-success"
                      >✓ {{ f.kb_title || 'Saved' }}</span
                    >
                    <span
                      v-else-if="f.status === 'failed'"
                      class="text-error"
                      >{{ f.error || 'Failed' }}</span
                    >
                    <span
                      v-else-if="f.status === 'processing'"
                      class="text-blue"
                      >Extracting &amp; indexing…</span
                    >
                    <span v-else class="text-grey-darken-1">Waiting…</span>
                  </div>
                </div>
                <VChip
                  :color="fileStatusColor(f.status)"
                  size="x-small"
                  variant="tonal"
                  class="text-capitalize"
                  >{{ f.status }}</VChip
                >
              </div>
            </div>
          </VCardText>

          <!-- completed step -->
          <VCardText v-else-if="batchStep === 'completed'" class="pa-5">
            <VAlert
              :type="
                batchFailed === batchFileEntries.length
                  ? 'error'
                  : batchFailed > 0
                    ? 'warning'
                    : 'success'
              "
              variant="tonal"
              prominent
              class="mb-5"
            >
              <VAlertTitle>
                {{
                  batchFailed === batchFileEntries.length
                    ? 'All files failed'
                    : batchFailed > 0
                      ? `Done with ${batchFailed} error(s)`
                      : 'All files processed!'
                }}
              </VAlertTitle>
              {{ batchFileEntries.length - batchFailed }} of
              {{ batchFileEntries.length }} files added to the Knowledge Base.
            </VAlert>
            <div class="d-flex gap-3 mb-4">
              <div class="stat-chip flex-1">
                <div class="text-h4 font-weight-bold text-success">
                  {{ batchFileEntries.length - batchFailed }}
                </div>
                <div class="text-caption">Successful</div>
              </div>
              <div class="stat-chip flex-1">
                <div
                  class="text-h4 font-weight-bold"
                  :class="batchFailed > 0 ? 'text-error' : 'text-grey'"
                >
                  {{ batchFailed }}
                </div>
                <div class="text-caption">Failed</div>
              </div>
              <div class="stat-chip flex-1">
                <div class="text-h4 font-weight-bold text-primary">
                  {{ batchFileEntries.length }}
                </div>
                <div class="text-caption">Total</div>
              </div>
            </div>
            <div
              class="kb-file-list"
              style="max-height: 220px; overflow-y: auto"
            >
              <div
                v-for="(f, i) in batchFileEntries"
                :key="i"
                class="kb-file-row"
              >
                <VIcon :color="fileStatusColor(f.status)" size="18">{{
                  fileStatusIcon(f.status)
                }}</VIcon>
                <div class="flex-1 min-w-0">
                  <div class="text-body-2 font-weight-medium text-truncate">
                    {{ f.original_name }}
                  </div>
                  <div class="text-caption">
                    <span v-if="f.status === 'done'" class="text-success">{{
                      f.kb_title
                    }}</span>
                    <span v-else class="text-error">{{ f.error }}</span>
                  </div>
                </div>
              </div>
            </div>
          </VCardText>

          <VDivider />
          <VCardActions class="px-5 py-3">
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

      <!-- ── Delete confirmation ──────────────────────────────────────────── -->
      <VDialog v-model="confirmDelete" max-width="380">
        <VCard rounded="lg" border>
          <VCardText class="pa-5">
            <div class="d-flex align-center gap-3 mb-3">
              <div
                class="kb-dialog-icon"
                style="background: rgba(var(--v-theme-error), 0.12)"
              >
                <VIcon color="error" size="20">mdi-delete-alert</VIcon>
              </div>
              <span class="text-h6 font-weight-bold">Delete Entry</span>
            </div>
            <p class="text-body-2 text-medium-emphasis">
              Are you sure you want to delete
              <strong class="text-grey-darken-3">{{
                itemToDelete ? itemToDelete.title : ''
              }}</strong
              >? This action cannot be undone.
            </p>
          </VCardText>
          <VCardActions class="px-5 pb-4 pt-0">
            <VSpacer />
            <VBtn variant="outlined" @click="confirmDelete = false"
              >Cancel</VBtn
            >
            <VBtn color="error" variant="flat" @click="confirmDeleteItem"
              >Delete</VBtn
            >
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- ── AI Enhance Result Dialog ─────────────────────────────────────── -->
      <VDialog v-model="enhanceDialog" max-width="520">
        <VCard rounded="lg" border>
          <VCardTitle class="d-flex align-center px-5 pt-5 pb-2 gap-3">
            <div
              class="kb-dialog-icon"
              :style="{
                background: enhanceError
                  ? '#fef2f2'
                  : 'linear-gradient(135deg,#7c3aed,#a855f7)',
              }"
            >
              <VIcon :color="enhanceError ? 'error' : 'white'" size="18">
                {{ enhanceError ? 'mdi-alert-circle' : 'mdi-auto-fix' }}
              </VIcon>
            </div>
            <span
              class="text-h6 font-weight-bold"
              style="font-size: 1rem !important"
            >
              {{
                enhanceError
                  ? 'Enhancement Gagal'
                  : 'Konten Berhasil Ditingkatkan!'
              }}
            </span>
          </VCardTitle>
          <VCardText class="px-5 pb-4">
            <template v-if="enhanceError">
              <p class="text-body-2 text-error mb-0">{{ enhanceError }}</p>
            </template>
            <template v-else-if="enhanceResult">
              <div class="d-flex align-center gap-3 mb-4">
                <div class="qs-result-score">
                  <span class="qs-result-num"
                    >{{
                      enhanceResult.quality_score != null
                        ? (enhanceResult.quality_score * 100).toFixed(0)
                        : '—'
                    }}%</span
                  >
                  <span class="qs-result-label">Quality Score</span>
                </div>
                <p class="text-body-2 mb-0 flex-1">
                  {{
                    enhanceResult.changes_summary ||
                    'Konten berhasil ditingkatkan dengan AI dan telah disimpan ke database.'
                  }}
                </p>
              </div>
              <VAlert
                type="success"
                variant="tonal"
                border="start"
                density="compact"
                rounded="lg"
                class="text-caption"
              >
                Konten telah di-re-index ke vector database. Score baru sudah
                terefleksi di tabel.
              </VAlert>
            </template>
          </VCardText>
          <VCardActions class="px-5 pb-4 pt-0 justify-end">
            <VBtn
              color="primary"
              variant="flat"
              rounded="lg"
              @click="enhanceDialog = false"
              >Tutup</VBtn
            >
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
      <TourButton @start="startTour" />
  </AppLayout>
</template>

<style scoped>
/* ── Page layout ─────────────────────────────────────────────────────── */
.kb-page {
  padding: 4px 0;
}

/* ── Header ──────────────────────────────────────────────────────────── */
.kb-header {
  padding: 4px 0;
}

.kb-header-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ── Flat bordered section card ──────────────────────────────────────── */
.kb-section {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #ffffff;
  overflow: hidden;
}

/* ── Filter bar ──────────────────────────────────────────────────────── */
/* filter row is in a kb-section wrapper with internal padding */
.kb-section > .v-row,
.kb-section > form,
.kb-section > div:not(.kb-table) {
  padding: 16px 20px;
}

/* ── Bulk action bar ─────────────────────────────────────────────────── */
.kb-bulk-bar {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 10px 16px;
}

/* ── Table overrides ─────────────────────────────────────────────────── */
.kb-table {
  background: transparent;
}

.kb-table :deep(.v-data-table-header__cell) {
  background: #f9fafb !important;
  border-bottom: 1px solid #e5e7eb !important;
  font-size: 11px !important;
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6b7280 !important;
  padding: 10px 16px !important;
}

.kb-table :deep(td) {
  border-bottom: 1px solid #f3f4f6 !important;
  padding: 10px 16px !important;
}

.kb-table :deep(tr:last-child td) {
  border-bottom: none !important;
}

.kb-table :deep(tr:hover td) {
  background: #fafafa !important;
}

.gap-1 {
  gap: 4px;
}
.gap-2 {
  gap: 8px;
}
.gap-3 {
  gap: 12px;
}

/* ── Stat chip (in dialogs) ──────────────────────────────────────────── */
.stat-chip {
  flex: 1;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  text-align: center;
}

/* ── Dialog icon badge ───────────────────────────────────────────────── */
.kb-dialog-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ── File list (batch upload) ────────────────────────────────────────── */
.kb-file-list {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.kb-file-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-bottom: 1px solid #f3f4f6;
  background: #fff;
}

.kb-file-row:last-child {
  border-bottom: none;
}

.kb-file-row:hover {
  background: #f9fafb;
}

/* ── Batch progress card ─────────────────────────────────────────────── */
.kb-progress-card {
  background: #f0f4ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  padding: 16px;
}

/* ── Batch dropzone ──────────────────────────────────────────────────── */
.batch-dropzone {
  border: 2px dashed #d1d5db;
  min-height: 140px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s;
  user-select: none;
  background: #fafafa;
}

.batch-dropzone:hover {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.04);
}

.batch-dropzone--active {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.08);
}

/* ── Transition ──────────────────────────────────────────────────────── */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Utility ─────────────────────────────────────────────────────────── */
.flex-1 {
  flex: 1;
}
.min-w-0 {
  min-width: 0;
}

/* ── Quality Score Badge ─────────────────────────────────────────────── */
.qs-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
  line-height: 1.6;
}
.qs-high {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #bbf7d0;
}
.qs-mid {
  background: #fef9c3;
  color: #a16207;
  border: 1px solid #fde68a;
}
.qs-low {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fecaca;
}
.qs-none {
  background: #f1f5f9;
  color: #94a3b8;
  border: 1px solid #e2e8f0;
}

/* ── Enhance Result Dialog ───────────────────────────────────────────── */
.qs-result-score {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  border-radius: 10px;
  padding: 10px 16px;
  min-width: 80px;
  flex-shrink: 0;
}
.qs-result-num {
  font-size: 1.5rem;
  font-weight: 800;
  color: #7c3aed;
  line-height: 1;
}
.qs-result-label {
  font-size: 0.68rem;
  color: #7c3aed;
  font-weight: 600;
  text-transform: uppercase;
  margin-top: 3px;
}
</style>
