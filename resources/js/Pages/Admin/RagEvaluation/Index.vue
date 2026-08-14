<template>
  <AppLayout title="RAG Evaluation Suite">
    <div class="eval-page">
      <!-- ── Page Header ─────────────────────────────────────── -->
      <div class="eval-header mb-6">
        <div>
          <h1 class="eval-title">RAG Evaluation Suite & Benchmark Engine</h1>
          <p class="eval-sub">
            Uji kualitas, groundedness (anti-halusinasi), dan relevansi respon AI SALMA menggunakan standar industri <strong>RAG Triad</strong>
          </p>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            color="primary"
            variant="flat"
            prepend-icon="mdi-play-circle-outline"
            @click="openRunModal"
            :loading="isRunning"
          >
            Jalankan Benchmark Evaluasi
          </VBtn>
        </div>
      </div>

      <!-- ── RAG Triad Metric KPI Cards ─────────────────────── -->
      <VRow class="mb-6" dense>
        <VCol cols="12" sm="6" lg="3">
          <div class="eval-kpi-card kpi-overall">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="kpi-label">Overall RAG Score</span>
              <VIcon color="white" size="20">mdi-trophy-outline</VIcon>
            </div>
            <div class="kpi-score-row">
              <div class="kpi-value">{{ metrics.latest_overall_score }}%</div>
              <VChip size="x-small" color="white" variant="flat" class="text-primary font-weight-bold">
                {{ getRatingLabel(metrics.latest_overall_score / 100) }}
              </VChip>
            </div>
            <VProgressLinear
              :model-value="metrics.latest_overall_score"
              color="white"
              height="6"
              rounded
              class="mt-3 opacity-90"
            />
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="eval-kpi-card">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="kpi-sublabel">1. Faithfulness / Grounded</span>
              <VIcon color="#10b981" size="20">mdi-shield-check-outline</VIcon>
            </div>
            <div class="kpi-subvalue text-emerald">{{ metrics.latest_faithfulness }}%</div>
            <p class="kpi-desc">Tingkat akurasi fakta & anti-halusinasi</p>
            <VProgressLinear
              :model-value="metrics.latest_faithfulness"
              color="success"
              height="4"
              rounded
              class="mt-2"
            />
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="eval-kpi-card">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="kpi-sublabel">2. Answer Relevance</span>
              <VIcon color="#3b82f6" size="20">mdi-target</VIcon>
            </div>
            <div class="kpi-subvalue text-blue">{{ metrics.latest_answer_relevance }}%</div>
            <p class="kpi-desc">Menjawab langsung pertanyaan wajib pajak</p>
            <VProgressLinear
              :model-value="metrics.latest_answer_relevance"
              color="primary"
              height="4"
              rounded
              class="mt-2"
            />
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="eval-kpi-card">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="kpi-sublabel">3. Context Relevance</span>
              <VIcon color="#8b5cf6" size="20">mdi-book-search-outline</VIcon>
            </div>
            <div class="kpi-subvalue text-purple">{{ metrics.latest_context_relevance }}%</div>
            <p class="kpi-desc">Kesesuaian dokumen KB yang ditarik</p>
            <VProgressLinear
              :model-value="metrics.latest_context_relevance"
              color="secondary"
              height="4"
              rounded
              class="mt-2"
            />
          </div>
        </VCol>
      </VRow>

      <!-- ── Tabs Navigation ─────────────────────────────────── -->
      <VCard class="rounded-xl border shadow-sm mb-6">
        <VTabs v-model="activeTab" color="primary" align-tabs="start" class="px-4 pt-2">
          <VTab value="latest-run" prepend-icon="mdi-chart-box-outline">
            Hasil Evaluasi Terakhir (Run #{{ latestRun?.id || '-' }})
          </VTab>
          <VTab value="test-suite" prepend-icon="mdi-format-list-checks">
            Golden Test Cases ({{ tests.length }} Kasus Uji)
          </VTab>
          <VTab value="history" prepend-icon="mdi-history">
            Riwayat Benchmark ({{ historyRuns.total || historyRuns.data.length }} Run)
          </VTab>
        </VTabs>

        <VDivider />

        <!-- ── TAB 1: Latest Run Details ──────────────────────── -->
        <VWindow v-model="activeTab" class="pa-4">
          <VWindowItem value="latest-run">
            <div v-if="!latestRun || !latestRun.results_payload" class="text-center py-12">
              <VIcon size="64" color="grey-lighten-1">mdi-timer-sand</VIcon>
              <h3 class="text-h6 font-weight-bold mt-4 text-grey-darken-1">Belum Ada Hasil Benchmark</h3>
              <p class="text-caption text-grey mt-1">
                Klik tombol "Jalankan Benchmark Evaluasi" untuk memulai pengujian otomatis performa RAG.
              </p>
            </div>

            <div v-else>
              <div class="d-flex justify-space-between align-center mb-4">
                <div class="d-flex align-center gap-2">
                  <VChip size="small" color="primary" variant="flat">
                    Model: {{ latestRun.model_used }}
                  </VChip>
                  <VChip size="small" color="secondary" variant="tonal">
                    {{ latestRun.total_tests }} Test Items
                  </VChip>
                  <VChip size="small" color="info" variant="tonal">
                    Avg Latency: {{ latestRun.avg_latency_seconds }}s
                  </VChip>
                </div>
                <span class="text-caption text-medium-emphasis">
                  Waktu: {{ formatDate(latestRun.created_at) }}
                </span>
              </div>

              <VTable hover class="eval-table border rounded-lg">
                <thead>
                  <tr>
                    <th style="width: 35%">Pertanyaan Wajib Pajak</th>
                    <th class="text-center" style="width: 10%">Bahasa</th>
                    <th class="text-center" style="width: 12%">Faithfulness</th>
                    <th class="text-center" style="width: 12%">Relevance</th>
                    <th class="text-center" style="width: 12%">RAG Score</th>
                    <th class="text-center" style="width: 10%">Latency</th>
                    <th class="text-end" style="width: 9%">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in latestRun.results_payload" :key="idx">
                    <td>
                      <div class="font-weight-medium text-body-2 text-high-emphasis">
                        {{ item.query }}
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        Topik: {{ item.expected_topic || 'Umum' }}
                      </div>
                    </td>

                    <td class="text-center">
                      <VChip size="x-small" :color="item.language === 'jv' ? 'amber-darken-2' : 'blue'" variant="tonal">
                        {{ item.language === 'jv' ? 'Basa Jawa' : 'Indonesia' }}
                      </VChip>
                    </td>

                    <td class="text-center">
                      <VChip size="small" :color="getScoreColor(item.faithfulness_score)" variant="tonal" class="font-weight-bold">
                        {{ Number((item.faithfulness_score || 0) * 100).toFixed(0) }}%
                      </VChip>
                    </td>

                    <td class="text-center">
                      <VChip size="small" :color="getScoreColor(item.answer_relevance_score)" variant="tonal" class="font-weight-bold">
                        {{ Number((item.answer_relevance_score || 0) * 100).toFixed(0) }}%
                      </VChip>
                    </td>

                    <td class="text-center">
                      <VChip size="small" :color="getScoreColor(item.overall_score)" variant="flat" class="font-weight-bold">
                        {{ Number((item.overall_score || 0) * 100).toFixed(0) }}%
                      </VChip>
                    </td>

                    <td class="text-center text-caption font-mono">
                      {{ item.latency_seconds }}s
                    </td>

                    <td class="text-end">
                      <VBtn
                        icon="mdi-eye-outline"
                        size="small"
                        variant="tonal"
                        color="primary"
                        title="Lihat Detail Respon & Penilaian Judge"
                        @click="inspectTestItem(item)"
                      />
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </VWindowItem>

          <!-- ── TAB 2: Golden Test Suite Management ──────────── -->
          <VWindowItem value="test-suite">
            <div class="d-flex justify-space-between align-center mb-4">
              <div class="text-subtitle-1 font-weight-bold">
                Dataset Standar Emas (Golden Benchmark Queries)
              </div>
              <VBtn
                color="primary"
                variant="flat"
                size="small"
                prepend-icon="mdi-plus"
                @click="openAddTestModal"
              >
                Tambah Kasus Uji Baru
              </VBtn>
            </div>

            <VTable hover class="eval-table border rounded-lg">
              <thead>
                <tr>
                  <th style="width: 5%">#</th>
                  <th style="width: 40%">Pertanyaan Kasus Uji</th>
                  <th class="text-center" style="width: 10%">Bahasa</th>
                  <th style="width: 15%">Kategori Topik</th>
                  <th style="width: 20%">Expected Ground Truth</th>
                  <th class="text-end" style="width: 10%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(test, i) in tests" :key="test.id">
                  <td class="text-caption text-medium-emphasis">{{ i + 1 }}</td>
                  <td class="font-weight-medium text-body-2">{{ test.query }}</td>
                  <td class="text-center">
                    <VChip size="x-small" :color="test.language === 'jv' ? 'amber-darken-2' : 'blue'" variant="tonal">
                      {{ test.language === 'jv' ? 'Basa Jawa' : 'Indonesia' }}
                    </VChip>
                  </td>
                  <td>
                    <VChip size="x-small" color="secondary" variant="outlined">
                      {{ test.expected_topic || 'Umum' }}
                    </VChip>
                  </td>
                  <td class="text-caption text-medium-emphasis text-truncate" style="max-width: 250px;" :title="test.ground_truth">
                    {{ test.ground_truth || '-' }}
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-end gap-1">
                      <VBtn icon="mdi-pencil-outline" size="x-small" variant="text" @click="editTest(test)" />
                      <VBtn icon="mdi-delete-outline" size="x-small" color="error" variant="text" @click="deleteTest(test)" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VWindowItem>

          <!-- ── TAB 3: Benchmark Runs History ────────────────── -->
          <VWindowItem value="history">
            <VTable hover class="eval-table border rounded-lg">
              <thead>
                <tr>
                  <th style="width: 10%">Run ID</th>
                  <th style="width: 15%">Model AI</th>
                  <th class="text-center" style="width: 10%">Total Tests</th>
                  <th class="text-center" style="width: 12%">Faithfulness</th>
                  <th class="text-center" style="width: 12%">Relevance</th>
                  <th class="text-center" style="width: 12%">Overall Score</th>
                  <th class="text-center" style="width: 12%">Avg Latency</th>
                  <th class="text-end" style="width: 17%">Waktu Pengujian</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="run in historyRuns.data" :key="run.id">
                  <td class="font-weight-bold">#{{ run.id }}</td>
                  <td>
                    <VChip size="x-small" color="primary" variant="tonal">{{ run.model_used }}</VChip>
                  </td>
                  <td class="text-center">{{ run.total_tests }}</td>
                  <td class="text-center font-weight-medium text-emerald">
                    {{ Number(run.avg_faithfulness_score * 100).toFixed(0) }}%
                  </td>
                  <td class="text-center font-weight-medium text-blue">
                    {{ Number(run.avg_answer_relevance_score * 100).toFixed(0) }}%
                  </td>
                  <td class="text-center">
                    <VChip size="small" :color="getScoreColor(run.overall_score)" variant="flat" class="font-weight-bold">
                      {{ Number(run.overall_score * 100).toFixed(0) }}%
                    </VChip>
                  </td>
                  <td class="text-center text-caption font-mono">{{ run.avg_latency_seconds }}s</td>
                  <td class="text-end text-caption text-medium-emphasis">{{ formatDate(run.created_at) }}</td>
                </tr>
              </tbody>
            </VTable>
          </VWindowItem>
        </VWindow>
      </VCard>

      <!-- ── Run Benchmark Trigger Modal ────────────────────── -->
      <VDialog v-model="runModal.show" max-width="500">
        <VCard class="rounded-xl">
          <VCardTitle class="pa-6 pb-2">
            <div class="text-h6 font-weight-bold">Jalankan Benchmark Evaluasi RAG</div>
            <div class="text-caption text-medium-emphasis">Pengujian otomatis RAG Triad menggunakan LLM-as-a-Judge</div>
          </VCardTitle>
          <VDivider class="my-2" />
          <VCardText class="pa-6">
            <p class="text-body-2 text-medium-emphasis mb-4">
              Sistem akan menjalankan kueri pengujian dari Golden Test Set, menghasilkan respon melalui RAG pipeline, dan mengevaluasi 3 pilar RAG Triad.
            </p>
            <VSelect
              v-model="runModal.limit"
              :items="[
                { title: 'Uji Cepat (3 Kasus Uji)', value: 3 },
                { title: 'Uji Standar (5 Kasus Uji)', value: 5 },
                { title: 'Uji Lengkap (Semua Kasus Uji)', value: 0 }
              ]"
              label="Jumlah Pengujian"
              variant="outlined"
              density="comfortable"
            />
          </VCardText>
          <VDivider />
          <VCardActions class="pa-6 d-flex justify-end gap-2">
            <VBtn variant="text" @click="runModal.show = false" :disabled="isRunning">Batal</VBtn>
            <VBtn
              color="primary"
              variant="flat"
              prepend-icon="mdi-play"
              @click="executeBenchmarkRun"
              :loading="isRunning"
            >
              Mulai Evaluasi
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- ── Test Item Inspector Modal ──────────────────────── -->
      <VDialog v-model="inspectModal.show" max-width="750">
        <VCard class="rounded-xl" v-if="inspectModal.item">
          <VCardTitle class="pa-6 pb-2 d-flex justify-space-between align-center">
            <div>
              <div class="text-h6 font-weight-bold">Detail Evaluasi Kasus Uji</div>
              <div class="text-caption text-medium-emphasis">Skor RAG Triad & Analisis LLM Judge</div>
            </div>
            <VBtn icon="mdi-close" variant="text" size="small" @click="inspectModal.show = false" />
          </VCardTitle>
          <VDivider class="my-2" />
          <VCardText class="pa-6 d-flex flex-column gap-4">
            <div>
              <div class="text-caption font-weight-bold text-medium-emphasis mb-1">PERTANYAAN PENGGUNA</div>
              <div class="pa-3 rounded-lg bg-grey-lighten-4 font-weight-medium text-body-2">
                "{{ inspectModal.item.query }}"
              </div>
            </div>

            <div v-if="inspectModal.item.ground_truth">
              <div class="text-caption font-weight-bold text-medium-emphasis mb-1">EXPECTED GROUND TRUTH</div>
              <div class="pa-3 rounded-lg bg-blue-lighten-5 text-caption text-blue-darken-3">
                {{ inspectModal.item.ground_truth }}
              </div>
            </div>

            <div>
              <div class="text-caption font-weight-bold text-medium-emphasis mb-1">JAWABAN ASISTEN AI (SALMA)</div>
              <div class="pa-3 rounded-lg border text-body-2" style="white-space: pre-wrap; line-height: 1.6;">
                {{ inspectModal.item.ai_answer }}
              </div>
            </div>

            <VRow dense>
              <VCol cols="4">
                <div class="pa-3 rounded-lg border text-center">
                  <div class="text-caption text-medium-emphasis">Faithfulness</div>
                  <div class="text-h6 font-weight-bold text-emerald">
                    {{ Number((inspectModal.item.faithfulness_score || 0) * 100).toFixed(0) }}%
                  </div>
                </div>
              </VCol>
              <VCol cols="4">
                <div class="pa-3 rounded-lg border text-center">
                  <div class="text-caption text-medium-emphasis">Answer Relevance</div>
                  <div class="text-h6 font-weight-bold text-blue">
                    {{ Number((inspectModal.item.answer_relevance_score || 0) * 100).toFixed(0) }}%
                  </div>
                </div>
              </VCol>
              <VCol cols="4">
                <div class="pa-3 rounded-lg border text-center">
                  <div class="text-caption text-medium-emphasis">Context Relevance</div>
                  <div class="text-h6 font-weight-bold text-purple">
                    {{ Number((inspectModal.item.context_relevance_score || 0) * 100).toFixed(0) }}%
                  </div>
                </div>
              </VCol>
            </VRow>

            <div v-if="inspectModal.item.reasoning">
              <div class="text-caption font-weight-bold text-medium-emphasis mb-1">ALASAN PENILAIAN JUDGE</div>
              <div class="pa-3 rounded-lg bg-amber-lighten-5 text-caption text-amber-darken-4">
                💡 {{ inspectModal.item.reasoning }}
              </div>
            </div>
          </VCardText>
        </VCard>
      </VDialog>

      <!-- ── Add/Edit Test Case Dialog ──────────────────────── -->
      <VDialog v-model="testDialog.show" max-width="600">
        <VCard class="rounded-xl">
          <VCardTitle class="pa-6 pb-2">
            <div class="text-h6 font-weight-bold">
              {{ testDialog.isEdit ? 'Edit Kasus Uji' : 'Tambah Kasus Uji Baru' }}
            </div>
          </VCardTitle>
          <VDivider class="my-2" />
          <VCardText class="pa-6 d-flex flex-column gap-3">
            <VTextarea
              v-model="testDialog.form.query"
              label="Pertanyaan Wajib Pajak"
              variant="outlined"
              rows="3"
            />
            <VRow dense>
              <VCol cols="6">
                <VSelect
                  v-model="testDialog.form.language"
                  :items="[
                    { title: 'Bahasa Indonesia', value: 'id' },
                    { title: 'Basa Jawa', value: 'jv' }
                  ]"
                  label="Bahasa"
                  variant="outlined"
                  density="comfortable"
                />
              </VCol>
              <VCol cols="6">
                <VTextField
                  v-model="testDialog.form.expected_topic"
                  label="Topik / Kategori"
                  variant="outlined"
                  density="comfortable"
                />
              </VCol>
            </VRow>
            <VTextarea
              v-model="testDialog.form.ground_truth"
              label="Expected Ground Truth (Fakta Kunci)"
              variant="outlined"
              rows="3"
            />
            <VTextField
              v-model="testDialog.form.tags"
              label="Tags (pisahkan koma)"
              variant="outlined"
              density="comfortable"
            />
          </VCardText>
          <VDivider />
          <VCardActions class="pa-6 d-flex justify-end gap-2">
            <VBtn variant="text" @click="testDialog.show = false">Batal</VBtn>
            <VBtn color="primary" variant="flat" @click="saveTestCase">Simpan</VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  latestRun: Object,
  historyRuns: Object,
  tests: Array,
  metrics: Object,
});

const activeTab = ref('latest-run');
const isRunning = ref(false);

const runModal = reactive({
  show: false,
  limit: 3,
});

const inspectModal = reactive({
  show: false,
  item: null,
});

const testDialog = reactive({
  show: false,
  isEdit: false,
  id: null,
  form: {
    query: '',
    language: 'id',
    expected_topic: '',
    ground_truth: '',
    tags: '',
    is_active: true,
  }
});

const openRunModal = () => {
  runModal.show = true;
};

const executeBenchmarkRun = async () => {
  isRunning.value = true;
  runModal.show = false;
  try {
    const res = await axios.post(route('admin.rag-evaluation.run'), {
      limit: runModal.limit
    });
    if (res.data.success) {
      router.reload();
    }
  } catch (e) {
    console.error('Benchmark execution error:', e);
  } finally {
    isRunning.value = false;
  }
};

const inspectTestItem = (item) => {
  inspectModal.item = item;
  inspectModal.show = true;
};

const openAddTestModal = () => {
  testDialog.isEdit = false;
  testDialog.id = null;
  testDialog.form = {
    query: '',
    language: 'id',
    expected_topic: '',
    ground_truth: '',
    tags: '',
    is_active: true,
  };
  testDialog.show = true;
};

const editTest = (test) => {
  testDialog.isEdit = true;
  testDialog.id = test.id;
  testDialog.form = { ...test };
  testDialog.show = true;
};

const saveTestCase = async () => {
  try {
    if (testDialog.isEdit) {
      await axios.put(route('admin.rag-evaluation.tests.update', testDialog.id), testDialog.form);
    } else {
      await axios.post(route('admin.rag-evaluation.tests.store'), testDialog.form);
    }
    testDialog.show = false;
    router.reload();
  } catch (e) {
    console.error('Failed to save test case', e);
  }
};

const deleteTest = async (test) => {
  if (!confirm(`Hapus kasus uji: "${test.query}"?`)) return;
  try {
    await axios.delete(route('admin.rag-evaluation.tests.destroy', test.id));
    router.reload();
  } catch (e) {
    console.error('Failed to delete test', e);
  }
};

const getRatingLabel = (score) => {
  if (score >= 0.90) return 'EXCELLENT';
  if (score >= 0.80) return 'GOOD';
  if (score >= 0.70) return 'ACCEPTABLE';
  return 'NEEDS TUNING';
};

const getScoreColor = (score) => {
  if (!score) return 'grey';
  if (score >= 0.85) return 'success';
  if (score >= 0.70) return 'warning';
  return 'error';
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
.eval-page {
  padding: 24px;
}

.eval-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.eval-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

.eval-sub {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 4px;
}

.eval-kpi-card {
  background: white;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
}

.kpi-overall {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
}

.kpi-label {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.025em;
  opacity: 0.9;
}

.kpi-score-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.kpi-value {
  font-size: 1.875rem;
  font-weight: 800;
  line-height: 1;
}

.kpi-sublabel {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.kpi-subvalue {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.kpi-desc {
  font-size: 0.6875rem;
  color: #94a3b8;
  margin-top: 2px;
}

.text-emerald { color: #10b981; }
.text-blue { color: #3b82f6; }
.text-purple { color: #8b5cf6; }

.eval-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 0.8125rem;
}
</style>
