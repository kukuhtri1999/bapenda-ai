<template>
  <!-- <Head title="Feedback Management" /> -->
  <AppLayout title="Feedback Management">
    <div class="fb-page">
      <!-- ── Page Header ─────────────────────────────────────── -->
      <div id="tour-fb-header" class="fb-header mb-6">
        <div>
          <h1 class="fb-title">Feedback Management</h1>
          <p class="fb-sub">
            Monitor dan analisis feedback pengguna dari sesi chat SALMA AI
          </p>
        </div>
        <VBtn
          @click="exportFeedback"
          :loading="isExporting"
          variant="flat"
          color="primary"
          prepend-icon="mdi-download"
          class="fb-export-btn"
        >
          Export CSV
        </VBtn>
      </div>

      <!-- ── Metric Cards ──────────────────────────────────── -->
      <VRow id="tour-fb-stats" class="mb-6" dense>
        <VCol
          v-for="(stat, index) in statisticsCards"
          :key="index"
          cols="12"
          sm="6"
          lg="3"
        >
          <div class="fb-metric-card">
            <div :class="`fb-metric-icon fi-${index}`">
              <VIcon color="white" size="22">{{ stat.icon }}</VIcon>
            </div>
            <div class="fb-metric-body">
              <div class="fb-metric-value">{{ stat.value }}</div>
              <div class="fb-metric-label">{{ stat.title }}</div>
            </div>
          </div>
        </VCol>
      </VRow>

      <!-- ── Charts Row ────────────────────────────────────── -->
      <VRow class="mb-6" dense>
        <VCol cols="12" md="6">
          <div id="tour-fb-distribution" class="fb-card">
            <div class="fb-card-header">
              <VIcon size="18" class="me-2" color="#7c3aed"
                >mdi-chart-bar</VIcon
              >
              <span class="fb-card-title">Rating Distribution</span>
            </div>
            <div class="fb-card-body">
              <div
                v-for="rating in [5, 4, 3, 2, 1]"
                :key="rating"
                class="rating-bar-row mb-3"
              >
                <div class="d-flex align-center gap-2">
                  <span class="rb-star">{{ rating }}⭐</span>
                  <div class="rb-track">
                    <div
                      class="rb-fill"
                      :style="`width: ${getRatingPercentage(rating)}%`"
                      :class="`rb-${rating}`"
                    ></div>
                  </div>
                  <span class="rb-count"
                    >({{ stats.rating_distribution[rating] || 0 }})</span
                  >
                </div>
              </div>
            </div>
          </div>
        </VCol>

        <VCol cols="12" md="6">
          <div class="fb-card h-100">
            <div class="fb-card-header">
              <VIcon size="18" class="me-2" color="#7c3aed"
                >mdi-clock-outline</VIcon
              >
              <span class="fb-card-title">Aktivitas Terkini</span>
            </div>
            <div
              class="fb-card-body d-flex flex-column align-center justify-center text-center py-6"
            >
              <div class="fb-recent-num">{{ stats.recent_feedbacks }}</div>
              <div class="fb-recent-label">Feedback 7 hari terakhir</div>
            </div>
          </div>
        </VCol>
      </VRow>

      <!-- ── Filter Bar ────────────────────────────────────── -->
      <div class="fb-filter-card mb-5">
        <div class="fb-filter-grid">
          <div class="fb-field fb-field-wide">
            <label class="fb-label">Cari</label>
            <div class="fb-input-wrap">
              <VIcon size="16" class="fb-input-icon">mdi-magnify</VIcon>
              <input
                v-model="filters.search"
                placeholder="Cari feedback, session ID..."
                class="fb-input fb-input-padded"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <div class="fb-field">
            <label class="fb-label">Rating</label>
            <select
              v-model="filters.rating"
              @change="applyFilters"
              class="fb-input"
            >
              <option :value="null">Semua Rating</option>
              <option
                v-for="r in ratingOptions"
                :key="r.value"
                :value="r.value"
              >
                {{ r.title }}
              </option>
            </select>
          </div>

          <div class="fb-field">
            <label class="fb-label">Dari Tanggal</label>
            <input v-model="filters.date_from" type="date" class="fb-input" />
          </div>

          <div class="fb-field">
            <label class="fb-label">Sampai Tanggal</label>
            <input v-model="filters.date_to" type="date" class="fb-input" />
          </div>

          <div class="fb-field fb-field-actions">
            <VBtn
              @click="applyFilters"
              color="primary"
              variant="flat"
              size="small"
              prepend-icon="mdi-filter"
              class="fb-apply-btn"
            >
              Terapkan
            </VBtn>
            <VBtn
              @click="clearFilters"
              variant="outlined"
              size="small"
              prepend-icon="mdi-filter-off"
            >
              Reset
            </VBtn>
          </div>
        </div>
      </div>

      <!-- ── Feedback Table ────────────────────────────────── -->
      <div class="fb-table-card">
        <div class="fb-table-header">
          <div class="d-flex align-center gap-2">
            <VIcon size="18" color="#7c3aed">mdi-format-list-bulleted</VIcon>
            <span class="fb-card-title">Daftar Feedback</span>
          </div>
          <span class="fb-count-badge">{{ feedbacks.total }} Total</span>
        </div>

        <VDataTable
          :headers="headers"
          :items="feedbacks.data"
          :loading="loading"
          item-key="id"
          class="fb-dt elevation-0"
          :items-per-page="-1"
          hide-default-footer
        >
          <template v-slot:item.session_id="{ item }">
            <span class="fb-session-id"
              >{{ item.session_id.substring(0, 12) }}…</span
            >
          </template>

          <template v-slot:item.rating="{ item }">
            <div class="d-flex align-center gap-2">
              <span class="fb-stars"
                >{{ '★'.repeat(item.rating)
                }}{{ '☆'.repeat(5 - item.rating) }}</span
              >
              <span :class="`fb-rating-chip fb-r${item.rating}`"
                >{{ item.rating }}/5</span
              >
            </div>
          </template>

          <template v-slot:item.feedback_text="{ item }">
            <div v-if="item.feedback_text" class="fb-text-cell">
              {{
                item.feedback_text.length > 100
                  ? item.feedback_text.substring(0, 100) + '…'
                  : item.feedback_text
              }}
            </div>
            <span v-else class="fb-no-text">—</span>
          </template>

          <template v-slot:item.created_at="{ item }">
            <span class="fb-date-cell">{{ formatDate(item.created_at) }}</span>
          </template>

          <template v-slot:item.actions="{ item }">
            <div class="d-flex align-center gap-1 justify-end">
              <button
                v-if="item.rating <= 3"
                class="fb-draft-btn"
                title="Draf Solusi KB dengan AI"
                @click="draftFromFeedback(item)"
                :disabled="draftingFeedbackId === item.id"
              >
                <VIcon size="16" color="#1261e0">mdi-robot-outline</VIcon>
              </button>
              <button class="fb-view-btn" title="Lihat Detail" @click="viewDetail(item.id)">
                <VIcon size="16">mdi-eye</VIcon>
              </button>
            </div>
          </template>
        </VDataTable>

        <VDivider />
        <div class="fb-table-footer">
          <span class="fb-footer-info">
            Menampilkan {{ feedbacks.from || 0 }}–{{ feedbacks.to || 0 }} dari
            {{ feedbacks.total }} entri
          </span>
          <VPagination
            v-if="feedbacks.last_page > 1"
            v-model="currentPage"
            :length="feedbacks.last_page"
            @update:model-value="changePage"
            total-visible="7"
            size="small"
            color="primary"
          />
        </div>
      </div>
    </div>
      <TourButton @start="startTour" />
  </AppLayout>
</template>

<script setup>
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';
import {
  ref, computed, onMounted, watch,
} from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  feedbacks: Object,
  stats: Object,
  filters: Object,
});

// Reactive data
const loading = ref(false);
const isExporting = ref(false);
const currentPage = ref(props.feedbacks.current_page || 1);

const filters = ref({
  search: props.filters.search || '',
  rating: props.filters.rating || null,
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  sort_by: props.filters.sort_by || 'created_at',
  sort_order: props.filters.sort_order || 'desc',
});

// Table headers
const headers = ref([
  { title: 'Session ID', key: 'session_id', sortable: true },
  { title: 'Rating', key: 'rating', sortable: true },
  { title: 'Feedback Text', key: 'feedback_text', sortable: false },
  { title: 'Date', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]);

// Rating options for filter
const ratingOptions = ref([
  { title: '⭐ (1 Star)', value: 1 },
  { title: '⭐⭐ (2 Stars)', value: 2 },
  { title: '⭐⭐⭐ (3 Stars)', value: 3 },
  { title: '⭐⭐⭐⭐ (4 Stars)', value: 4 },
  { title: '⭐⭐⭐⭐⭐ (5 Stars)', value: 5 },
]);

// Computed statistics cards
const statisticsCards = computed(() => [
  {
    title: 'Total Feedback',
    value: props.stats.total_feedbacks,
    icon: 'mdi-message-text',
    color: 'blue-lighten-5',
    iconColor: 'blue',
    textColor: 'text-blue',
    subtitleColor: 'text-blue-darken-2',
  },
  {
    title: 'Average Rating',
    value: `${props.stats.average_rating}/5`,
    icon: 'mdi-star',
    color: 'amber-lighten-5',
    iconColor: 'amber',
    textColor: 'text-amber-darken-2',
    subtitleColor: 'text-amber-darken-3',
  },
  {
    title: 'Recent Feedback',
    value: props.stats.recent_feedbacks,
    icon: 'mdi-clock-outline',
    color: 'green-lighten-5',
    iconColor: 'green',
    textColor: 'text-green',
    subtitleColor: 'text-green-darken-2',
  },
  {
    title: 'Satisfaction Rate',
    value: `${getSatisfactionRate()}%`,
    icon: 'mdi-emoticon-happy',
    color: 'purple-lighten-5',
    iconColor: 'purple',
    textColor: 'text-purple',
    subtitleColor: 'text-purple-darken-2',
  },
]);

// Methods
const getRatingPercentage = (rating) => {
  const total = props.stats.total_feedbacks;
  if (total === 0) return 0;
  const count = props.stats.rating_distribution[rating] || 0;
  return (count / total) * 100;
};

const getRatingColor = (rating) => {
  const colors = {
    1: 'red',
    2: 'orange',
    3: 'yellow',
    4: 'light-green',
    5: 'green',
  };
  return colors[rating] || 'grey';
};

const getSatisfactionRate = () => {
  const total = props.stats.total_feedbacks;
  if (total === 0) return 0;
  const satisfied = (props.stats.rating_distribution[4] || 0)
    + (props.stats.rating_distribution[5] || 0);
  return Math.round((satisfied / total) * 100);
};

const formatDate = (dateString) => new Date(dateString).toLocaleDateString('id-ID', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

const applyFilters = () => {
  loading.value = true;
  router.get('/admin/feedback', filters.value, {
    preserveState: true,
    onFinish: () => {
      loading.value = false;
    },
  });
};

const clearFilters = () => {
  filters.value = {
    search: '',
    rating: null,
    date_from: '',
    date_to: '',
    sort_by: 'created_at',
    sort_order: 'desc',
  };
  applyFilters();
};

const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

const changePage = (page) => {
  currentPage.value = page;
  router.get(
    '/admin/feedback',
    { ...filters.value, page },
    {
      preserveState: true,
    },
  );
};

const draftingFeedbackId = ref(null);

const draftFromFeedback = async (item) => {
  draftingFeedbackId.value = item.id;
  try {
    const res = await axios.post(route('admin.feedback.draft-kb', item.id));
    if (res.data.success && res.data.draft) {
      const d = res.data.draft;
      router.get(route('knowledge-base.create'), {
        prefill_title: d.title,
        prefill_question: d.question,
        prefill_answer: d.answer,
        prefill_content: d.content,
        prefill_category: d.category,
        prefill_type: d.type,
        prefill_tags: d.tags,
        prefill_ai_instructions: d.ai_instructions,
      });
    }
  } catch (e) {
    console.error('Failed to generate draft from feedback', e);
  } finally {
    draftingFeedbackId.value = null;
  }
};

const viewDetail = (feedbackId) => {
  router.get(`/admin/feedback/${feedbackId}`);
};

const exportFeedback = async () => {
  isExporting.value = true;
  try {
    window.open(
      `/admin/feedback/export/csv?${new URLSearchParams(filters.value).toString()}`,
    );
  } catch (error) {
    console.error('Export failed:', error);
  } finally {
    isExporting.value = false;
  }
};

onMounted(() => {
  // Any initialization logic
});

const fbSteps = [
  { title: 'Feedback Management', intro: 'Halaman ini menampilkan semua penilaian dan komentar dari pengguna setelah menggunakan layanan SALMA AI.' },
  { element: '#tour-fb-header', title: 'Judul & Export', intro: 'Klik tombol "Export" untuk mengunduh seluruh data feedback dalam format Excel/CSV untuk keperluan pelaporan.' },
  { element: '#tour-fb-stats', title: 'Statistik Ringkasan', intro: 'Kartu statistik menampilkan total feedback yang masuk, rata-rata rating bintang, dan persentase pengguna yang puas.' },
  { element: '#tour-fb-distribution', title: 'Distribusi Rating', intro: 'Grafik batang menampilkan berapa banyak pengguna yang memberikan rating 1 hingga 5 bintang, beserta persentasenya.' },
  { title: 'Daftar Ulasan', intro: 'Gulir ke bawah untuk melihat daftar lengkap feedback pengguna, termasuk nama, nomor kendaraan, teks komentar, dan tanggal diberikan.' },
];
const { startTour } = useTour(fbSteps);

</script>

<style scoped>
/* ── Gap utility ──────────────────────────────────────────── */
.gap-2 > * + * {
  margin-left: 8px;
}

/* ── Page ─────────────────────────────────────────────────── */
.fb-page {
  padding: 24px;
  max-width: 1280px;
  margin: 0 auto;
}

/* ── Header ───────────────────────────────────────────────── */
.fb-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.fb-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 3px;
}

.fb-sub {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.fb-export-btn {
  text-transform: none !important;
  border-radius: 8px !important;
  font-weight: 600 !important;
}

/* ── Metric Cards ─────────────────────────────────────────── */
.fb-metric-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition:
    box-shadow 0.2s,
    transform 0.2s;
}

.fb-metric-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
  transform: translateY(-2px);
}

.fb-metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.fi-0 {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}
.fi-1 {
  background: linear-gradient(135deg, #b45309, #f59e0b);
}
.fi-2 {
  background: linear-gradient(135deg, #15803d, #22c55e);
}
.fi-3 {
  background: linear-gradient(135deg, #7c3aed, #a855f7);
}

.fb-metric-value {
  font-size: 1.625rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.1;
}

.fb-metric-label {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 3px;
}

/* ── Info Cards ───────────────────────────────────────────── */
.fb-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
}

.fb-card-header {
  display: flex;
  align-items: center;
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
}

.fb-card-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
}

.fb-card-body {
  padding: 16px 18px;
}

/* ── Rating Bars ──────────────────────────────────────────── */
.rb-star {
  font-size: 0.8rem;
  width: 36px;
  flex-shrink: 0;
}

.rb-track {
  flex: 1;
  height: 10px;
  background: #f1f5f9;
  border-radius: 5px;
  overflow: hidden;
}

.rb-fill {
  height: 100%;
  border-radius: 5px;
  transition: width 0.6s ease;
}

.rb-5 {
  background: #22c55e;
}
.rb-4 {
  background: #86efac;
}
.rb-3 {
  background: #fbbf24;
}
.rb-2 {
  background: #f97316;
}
.rb-1 {
  background: #ef4444;
}

.rb-count {
  font-size: 0.75rem;
  color: #94a3b8;
  white-space: nowrap;
  min-width: 36px;
  text-align: right;
}

/* ── Recent Activity ──────────────────────────────────────── */
.fb-recent-num {
  font-size: 3rem;
  font-weight: 800;
  color: #7c3aed;
  line-height: 1;
}

.fb-recent-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 8px;
}

/* ── Filter Bar ───────────────────────────────────────────── */
.fb-filter-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
}

.fb-filter-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-end;
}

.fb-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.fb-field-wide {
  flex: 2;
  min-width: 200px;
}
.fb-field-actions {
  display: flex;
  gap: 8px;
  align-items: flex-end;
  margin-left: auto;
}

.fb-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.fb-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.fb-input-icon {
  position: absolute;
  left: 9px;
  color: #94a3b8;
  pointer-events: none;
}

.fb-input {
  height: 36px;
  padding: 0 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1e293b;
  background: #fff;
  outline: none;
  width: 100%;
  transition: border-color 0.2s;
  appearance: auto;
}

.fb-input-padded {
  padding-left: 32px;
}

.fb-input:focus {
  border-color: #7c3aed;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08);
}

.fb-apply-btn {
  text-transform: none !important;
  border-radius: 8px !important;
}

/* ── Table Card ───────────────────────────────────────────── */
.fb-table-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
}

.fb-table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 8px;
}

.fb-count-badge {
  background: #ede9fe;
  color: #5b21b6;
  border-radius: 20px;
  padding: 3px 12px;
  font-size: 0.78rem;
  font-weight: 600;
}

/* VDataTable override to match design */
.fb-dt :deep(.v-data-table__thead th) {
  background: #f8fafc !important;
  font-size: 0.72rem !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important;
  color: #64748b !important;
  padding: 10px 14px !important;
  border-bottom: 1px solid #e2e8f0 !important;
}

.fb-dt :deep(.v-data-table__tbody tr) {
  border-bottom: 1px solid #f1f5f9 !important;
}

.fb-dt :deep(.v-data-table__tbody tr:hover > td) {
  background: #faf5ff !important;
}

.fb-dt :deep(.v-data-table__tbody td) {
  padding: 10px 14px !important;
  font-size: 0.875rem !important;
}

/* ── Cell Styles ──────────────────────────────────────────── */
.fb-session-id {
  font-family: monospace;
  font-size: 0.8rem;
  background: #f1f5f9;
  color: #475569;
  padding: 2px 8px;
  border-radius: 4px;
}

.fb-stars {
  font-size: 1rem;
  letter-spacing: 1px;
  color: #f59e0b;
}

.fb-rating-chip {
  display: inline-block;
  padding: 1px 8px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  margin-left: 4px;
}

.fb-r5,
.fb-r4 {
  background: #dcfce7;
  color: #15803d;
}

.fb-r3 {
  background: #fef9c3;
  color: #854d0e;
}

.fb-r1,
.fb-r2 {
  background: #fee2e2;
  color: #dc2626;
}

.fb-text-cell {
  max-width: 280px;
  line-height: 1.5;
  word-break: break-word;
  font-size: 0.85rem;
}

.fb-no-text {
  color: #cbd5e1;
}

.fb-date-cell {
  font-size: 0.8rem;
  color: #64748b;
  white-space: nowrap;
}

.fb-view-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  color: #7c3aed;
  cursor: pointer;
  transition: all 0.15s;
}

.fb-view-btn:hover {
  background: #ede9fe;
  border-color: #c4b5fd;
}

/* ── Footer ───────────────────────────────────────────────── */
.fb-table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 18px;
  flex-wrap: wrap;
  gap: 8px;
}

.fb-footer-info {
  font-size: 0.8rem;
  color: #64748b;
}

/* ── Mobile ───────────────────────────────────────────────── */
@media (max-width: 640px) {
  .fb-page {
    padding: 16px;
  }
  .fb-filter-grid {
    flex-direction: column;
  }
  .fb-field,
  .fb-field-wide {
    width: 100%;
  }
  .fb-field-actions {
    margin-left: 0;
  }
}
</style>
