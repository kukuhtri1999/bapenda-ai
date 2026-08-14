<template>
  <AppLayout title="AI Knowledge Gaps">
    <div class="kg-page">
      <!-- ── Page Header ─────────────────────────────────────── -->
      <div class="kg-header mb-6">
        <div>
          <h1 class="kg-title">AI Knowledge Gaps & Gap Harvesting</h1>
          <p class="kg-sub">
            Deteksi otomatis pertanyaan wajib pajak yang belum terjawab atau memiliki tingkat relevansi rendah di basis pengetahuan
          </p>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            @click="refreshData"
            variant="tonal"
            color="primary"
            prepend-icon="mdi-refresh"
            :loading="isLoading"
          >
            Refresh Data
          </VBtn>
        </div>
      </div>

      <!-- ── Metric Cards ──────────────────────────────────── -->
      <VRow class="mb-6" dense>
        <VCol cols="12" sm="6" lg="3">
          <div class="kg-metric-card">
            <div class="kg-metric-icon ki-total">
              <VIcon color="white" size="22">mdi-help-rhombus-outline</VIcon>
            </div>
            <div class="kg-metric-body">
              <div class="kg-metric-value">{{ stats.total_gaps }}</div>
              <div class="kg-metric-label">Total Pertanyaan Gap</div>
            </div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="kg-metric-card">
            <div class="kg-metric-icon ki-pending">
              <VIcon color="white" size="22">mdi-alert-circle-outline</VIcon>
            </div>
            <div class="kg-metric-body">
              <div class="kg-metric-value">{{ stats.pending_gaps }}</div>
              <div class="kg-metric-label">Menunggu Draf KB</div>
            </div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="kg-metric-card">
            <div class="kg-metric-icon ki-resolved">
              <VIcon color="white" size="22">mdi-check-decagram-outline</VIcon>
            </div>
            <div class="kg-metric-body">
              <div class="kg-metric-value">{{ stats.resolved_gaps }}</div>
              <div class="kg-metric-label">Sudah Terselesaikan</div>
            </div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="kg-metric-card">
            <div class="kg-metric-icon ki-top">
              <VIcon color="white" size="22">mdi-fire</VIcon>
            </div>
            <div class="kg-metric-body">
              <div class="kg-metric-value text-truncate" style="max-width: 180px;" :title="stats.top_gap">
                {{ stats.top_gap }}
              </div>
              <div class="kg-metric-label">Pertanyaan Terbanyak</div>
            </div>
          </div>
        </VCol>
      </VRow>

      <!-- ── Filter Card ────────────────────────────────────── -->
      <div class="kg-filter-card mb-6">
        <VRow dense align="center">
          <VCol cols="12" md="4">
            <VTextField
              v-model="search"
              placeholder="Cari pertanyaan gap atau session ID..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @keyup.enter="applyFilters"
              @click:clear="clearSearch"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedStatus"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="applyFilters"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedSource"
              :items="sourceOptions"
              label="Sumber Gap"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="applyFilters"
            />
          </VCol>

          <VCol cols="12" md="2" class="d-flex justify-end">
            <VBtn
              color="primary"
              variant="flat"
              prepend-icon="mdi-filter"
              @click="applyFilters"
              block
            >
              Terapkan
            </VBtn>
          </VCol>
        </VRow>
      </div>

      <!-- ── Knowledge Gaps Table ───────────────────────────── -->
      <div class="kg-table-card">
        <div class="kg-table-header">
          <div class="d-flex align-center">
            <VIcon color="#1261e0" class="me-2">mdi-lightbulb-alert-outline</VIcon>
            <span class="font-weight-bold text-subtitle-1">Daftar Knowledge Gaps Terdeteksi</span>
          </div>
          <span class="text-caption text-medium-emphasis">
            Menampilkan {{ gaps.data.length }} dari {{ gaps.total }} gap
          </span>
        </div>

        <div v-if="gaps.data.length === 0" class="text-center py-12">
          <VIcon size="64" color="grey-lighten-1">mdi-clipboard-check-outline</VIcon>
          <h3 class="text-h6 font-weight-bold mt-4 text-grey-darken-1">Tidak Ada Knowledge Gap</h3>
          <p class="text-caption text-grey mt-1">
            Semua pertanyaan pengguna saat ini berhasil dijawab oleh basis pengetahuan dengan relevansi baik.
          </p>
        </div>

        <VTable v-else hover class="kg-table">
          <thead>
            <tr>
              <th style="width: 40%">Pertanyaan Wajib Pajak</th>
              <th class="text-center" style="width: 12%">Frekuensi</th>
              <th class="text-center" style="width: 14%">Skor Kemiripan</th>
              <th class="text-center" style="width: 12%">Status</th>
              <th class="text-center" style="width: 10%">Terakhir Ditanyakan</th>
              <th class="text-end" style="width: 12%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="gap in gaps.data" :key="gap.id">
              <td>
                <div class="font-weight-medium text-body-2 text-high-emphasis">
                  "{{ gap.query }}"
                </div>
                <div class="d-flex align-center gap-2 mt-1">
                  <VChip size="x-small" :color="getSourceColor(gap.source)" variant="tonal">
                    {{ getSourceLabel(gap.source) }}
                  </VChip>
                  <span v-if="gap.session_id" class="text-caption text-medium-emphasis">
                    Sesi: {{ gap.session_id.substring(0, 12) }}...
                  </span>
                </div>
              </td>

              <td class="text-center">
                <VChip size="small" color="primary" variant="flat" class="font-weight-bold">
                  {{ gap.frequency }}x
                </VChip>
              </td>

              <td class="text-center">
                <VChip
                  size="small"
                  :color="getScoreColor(gap.similarity_score)"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  {{ gap.similarity_score !== null ? Number(gap.similarity_score).toFixed(2) : 'N/A' }}
                  ({{ getScoreLabel(gap.similarity_score) }})
                </VChip>
              </td>

              <td class="text-center">
                <VChip
                  size="small"
                  :color="getStatusColor(gap.status)"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  {{ getStatusLabel(gap.status) }}
                </VChip>
              </td>

              <td class="text-center text-caption text-medium-emphasis">
                {{ formatDate(gap.last_seen_at || gap.created_at) }}
              </td>

              <td class="text-end">
                <div class="d-flex justify-end align-center gap-1">
                  <VBtn
                    icon="mdi-robot-outline"
                    size="small"
                    variant="tonal"
                    color="primary"
                    title="Buat Draf KB dengan AI"
                    @click="openDraftModal(gap)"
                    :loading="generatingGapId === gap.id"
                  />

                  <VMenu location="bottom end">
                    <template #activator="{ props: menuProps }">
                      <VBtn
                        icon="mdi-dots-vertical"
                        size="small"
                        variant="text"
                        v-bind="menuProps"
                      />
                    </template>
                    <VList density="compact">
                      <VListItem
                        v-if="gap.status !== 'resolved'"
                        prepend-icon="mdi-check"
                        title="Tandai Selesai"
                        @click="resolveGap(gap)"
                      />
                      <VListItem
                        v-if="gap.status !== 'dismissed'"
                        prepend-icon="mdi-eye-off-outline"
                        title="Abaikan Gap"
                        @click="dismissGap(gap)"
                      />
                      <VListItem
                        prepend-icon="mdi-delete-outline"
                        title="Hapus"
                        class="text-error"
                        @click="deleteGap(gap)"
                      />
                    </VList>
                  </VMenu>
                </div>
              </td>
            </tr>
          </tbody>
        </VTable>

        <!-- ── Pagination ───────────────────────────────────── -->
        <div v-if="gaps.links && gaps.links.length > 3" class="kg-pagination d-flex justify-space-between align-center p-4 border-t">
          <div class="text-caption text-medium-emphasis">
            Halaman {{ gaps.current_page }} dari {{ gaps.last_page }}
          </div>
          <div class="d-flex gap-1">
            <template v-for="(link, i) in gaps.links" :key="i">
              <VBtn
                v-if="link.url"
                :variant="link.active ? 'flat' : 'text'"
                :color="link.active ? 'primary' : 'default'"
                size="small"
                @click="router.get(link.url)"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>

      <!-- ── AI Draft Preview & Generator Modal ────────────── -->
      <VDialog v-model="draftModal.show" max-width="800" persistent>
        <VCard class="rounded-xl">
          <VCardTitle class="d-flex align-center justify-space-between px-6 pt-6 pb-2">
            <div class="d-flex align-center gap-2">
              <div class="ki-modal-icon">
                <VIcon color="primary" size="24">mdi-robot-outline</VIcon>
              </div>
              <div>
                <div class="text-h6 font-weight-bold">Draf Artikel Knowledge Base AI</div>
                <div class="text-caption text-medium-emphasis">Dihasilkan oleh model AI dari pertanyaan wajib pajak</div>
              </div>
            </div>
            <VBtn icon="mdi-close" variant="text" size="small" @click="draftModal.show = false" />
          </VCardTitle>

          <VDivider class="my-2" />

          <VCardText class="px-6 py-4">
            <div v-if="draftModal.loading" class="text-center py-10">
              <VProgressCircular indeterminate color="primary" size="56" class="mb-4" />
              <div class="text-subtitle-1 font-weight-bold">Sedang Menyusun Draf Artikel...</div>
              <div class="text-caption text-medium-emphasis">
                AI menganalisis pertanyaan dan menyusun jawaban lengkap terstruktur
              </div>
            </div>

            <div v-else-if="draftModal.draft" class="d-flex flex-column gap-4">
              <VTextField
                v-model="draftModal.draft.title"
                label="Judul Artikel"
                variant="outlined"
                density="comfortable"
              />

              <VTextField
                v-model="draftModal.draft.question"
                label="Pertanyaan Wajib Pajak"
                variant="outlined"
                density="comfortable"
              />

              <VRow dense>
                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="draftModal.draft.category"
                    :items="categoryItemList"
                    label="Kategori"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="draftModal.draft.type"
                    :items="typeItemList"
                    label="Tipe Konten"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>
              </VRow>

              <VTextarea
                v-model="draftModal.draft.answer"
                label="Ringkasan Jawaban"
                variant="outlined"
                rows="3"
                hint="Jawaban ringkas yang cepat dibaca"
                persistent-hint
              />

              <VTextarea
                v-model="draftModal.draft.content"
                label="Konten Lengkap (Markdown)"
                variant="outlined"
                rows="6"
                hint="Panduan prosedur detail atau persyaratan lengkap"
                persistent-hint
              />

              <VTextField
                v-model="draftModal.draft.tags"
                label="Tags (dipisahkan koma)"
                variant="outlined"
                density="comfortable"
              />

              <VTextarea
                v-model="draftModal.draft.ai_instructions"
                label="Instruksi Khusus Chatbot AI (Opsional)"
                variant="outlined"
                rows="2"
                prepend-inner-icon="mdi-robot"
                hint="Petunjuk khusus saat chatbot membaca referensi ini"
                persistent-hint
              />
            </div>
          </VCardText>

          <VDivider />

          <VCardActions class="px-6 py-4 d-flex justify-space-between">
            <VBtn variant="text" @click="draftModal.show = false">
              Tutup
            </VBtn>
            <div class="d-flex gap-2">
              <VBtn
                variant="tonal"
                color="secondary"
                prepend-icon="mdi-refresh"
                @click="regenerateDraft"
                :disabled="draftModal.loading"
              >
                Generate Ulang
              </VBtn>
              <VBtn
                variant="flat"
                color="primary"
                prepend-icon="mdi-arrow-right-bold"
                @click="proceedToCreateKB"
                :disabled="draftModal.loading || !draftModal.draft"
              >
                Gunakan & Buka Form KB
              </VBtn>
            </div>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  gaps: Object,
  stats: Object,
  filters: Object,
  categories: Object,
  types: Object,
});

const isLoading = ref(false);
const generatingGapId = ref(null);
const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || 'pending');
const selectedSource = ref(props.filters?.source || '');

const statusOptions = [
  { title: 'Menunggu Draf (Pending)', value: 'pending' },
  { title: 'Terselesaikan (Resolved)', value: 'resolved' },
  { title: 'Diabaikan (Dismissed)', value: 'dismissed' },
  { title: 'Semua Status', value: 'all' },
];

const sourceOptions = [
  { title: 'Semua Sumber', value: '' },
  { title: 'Kemiripan Rendah (Low Confidence)', value: 'low_confidence' },
  { title: 'Fallback AI', value: 'fallback' },
  { title: 'Feedback Negatif (Flagged Chat)', value: 'flagged_chat' },
];

const categoryItemList = computed(() => {
  if (!props.categories) return [];
  return Object.entries(props.categories).map(([k, v]) => ({ title: v, value: k }));
});

const typeItemList = computed(() => {
  if (!props.types) return [];
  return Object.entries(props.types).map(([k, v]) => ({ title: v, value: k }));
});

// ── AI Draft Modal State ──────────────────────────────────────────────────
const draftModal = reactive({
  show: false,
  loading: false,
  gap: null,
  draft: null,
});

const applyFilters = () => {
  router.get(route('admin.knowledge-gaps.index'), {
    search: search.value,
    status: selectedStatus.value,
    source: selectedSource.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearSearch = () => {
  search.value = '';
  applyFilters();
};

const refreshData = () => {
  isLoading.value = true;
  router.reload({
    onFinish: () => { isLoading.value = false; }
  });
};

const openDraftModal = async (gap) => {
  draftModal.gap = gap;
  draftModal.show = true;

  if (gap.suggested_draft) {
    draftModal.draft = { ...gap.suggested_draft };
    draftModal.loading = false;
    return;
  }

  draftModal.loading = true;
  generatingGapId.value = gap.id;

  try {
    const res = await axios.post(route('admin.knowledge-gaps.draft', gap.id));
    if (res.data.success) {
      draftModal.draft = res.data.draft;
    }
  } catch (e) {
    console.error('Failed to generate draft', e);
  } finally {
    draftModal.loading = false;
    generatingGapId.value = null;
  }
};

const regenerateDraft = async () => {
  if (!draftModal.gap) return;
  draftModal.loading = true;
  try {
    const res = await axios.post(route('admin.knowledge-gaps.draft', draftModal.gap.id));
    if (res.data.success) {
      draftModal.draft = res.data.draft;
    }
  } catch (e) {
    console.error('Failed to regenerate draft', e);
  } finally {
    draftModal.loading = false;
  }
};

const proceedToCreateKB = () => {
  if (!draftModal.draft) return;
  const draft = draftModal.draft;
  draftModal.show = false;

  // Navigate to knowledge-base.create with prefilled query parameters
  router.get(route('knowledge-base.create'), {
    prefill_title: draft.title,
    prefill_question: draft.question,
    prefill_answer: draft.answer,
    prefill_content: draft.content,
    prefill_category: draft.category,
    prefill_type: draft.type,
    prefill_tags: draft.tags,
    prefill_ai_instructions: draft.ai_instructions,
    from_gap_id: draftModal.gap?.id,
  });
};

const resolveGap = async (gap) => {
  try {
    await axios.post(route('admin.knowledge-gaps.resolve', gap.id));
    refreshData();
  } catch (e) {
    console.error('Failed to resolve gap', e);
  }
};

const dismissGap = async (gap) => {
  try {
    await axios.post(route('admin.knowledge-gaps.dismiss', gap.id));
    refreshData();
  } catch (e) {
    console.error('Failed to dismiss gap', e);
  }
};

const deleteGap = async (gap) => {
  if (!confirm('Apakah Anda yakin ingin menghapus knowledge gap ini?')) return;
  try {
    await axios.delete(route('admin.knowledge-gaps.destroy', gap.id));
    refreshData();
  } catch (e) {
    console.error('Failed to delete gap', e);
  }
};

const getSourceColor = (source) => {
  switch (source) {
    case 'fallback': return 'error';
    case 'flagged_chat': return 'warning';
    case 'low_confidence': return 'purple';
    default: return 'secondary';
  }
};

const getSourceLabel = (source) => {
  switch (source) {
    case 'fallback': return 'Fallback Response';
    case 'flagged_chat': return 'Feedback Negatif';
    case 'low_confidence': return 'Relevansi Rendah';
    default: return source;
  }
};

const getScoreColor = (score) => {
  if (score === null) return 'grey';
  if (score < 0.3) return 'error';
  if (score < 0.5) return 'warning';
  return 'success';
};

const getScoreLabel = (score) => {
  if (score === null) return 'N/A';
  if (score < 0.3) return 'Sangat Rendah';
  if (score < 0.5) return 'Rendah';
  return 'Cukup';
};

const getStatusColor = (status) => {
  switch (status) {
    case 'resolved': return 'success';
    case 'dismissed': return 'grey';
    case 'pending': return 'warning';
    default: return 'primary';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'resolved': return 'Terselesaikan';
    case 'dismissed': return 'Diabaikan';
    case 'pending': return 'Menunggu Draf';
    default: return status;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<style scoped>
.kg-page {
  padding: 24px;
}

.kg-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.kg-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

.kg-sub {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 4px;
}

.kg-metric-card {
  background: white;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
}

.kg-metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ki-total { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.ki-pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
.ki-resolved { background: linear-gradient(135deg, #10b981, #047857); }
.ki-top { background: linear-gradient(135deg, #ec4899, #be185d); }

.ki-modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: #e0f2fe;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kg-metric-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.kg-metric-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.kg-filter-card {
  background: white;
  border-radius: 12px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.kg-table-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.kg-table-header {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
}

.kg-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 0.8125rem;
}
</style>
