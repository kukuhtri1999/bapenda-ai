<template>
  <AppLayout title="AI Chat History">
    <div class="ch-page">
      <!-- ── Page Header ─────────────────────────────────────── -->
      <div class="ch-header mb-6">
        <div>
          <h1 class="ch-title">Riwayat Chat AI</h1>
          <p class="ch-sub">
            Telusuri dan filter semua percakapan dengan SALMA AI
          </p>
        </div>
        <span class="ch-total-badge"
          >{{ total.toLocaleString('id-ID') }} pesan</span
        >
        <span v-if="avgResponseTime !== null" class="ch-avg-badge">
          Rata-rata jawab AI: <strong>{{ avgResponseTime }}s</strong>
        </span>
      </div>

      <!-- ── Filter Bar ──────────────────────────────────────── -->
      <div class="ch-filter-card mb-5">
        <div class="ch-filter-grid">
          <div class="ch-field">
            <label class="ch-label">Cari</label>
            <div class="ch-input-wrap">
              <svg
                class="ch-input-icon"
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
              </svg>
              <input
                v-model="q"
                @input="debouncedFetch()"
                placeholder="Cari pertanyaan..."
                class="ch-input ch-input-padded"
              />
            </div>
          </div>

          <div class="ch-field">
            <label class="ch-label">Dari Tanggal</label>
            <input
              ref="startFlat"
              type="text"
              v-model="startDisplay"
              placeholder="dd/mm/yyyy"
              class="ch-input"
            />
          </div>

          <div class="ch-field">
            <label class="ch-label">Sampai Tanggal</label>
            <input
              ref="endFlat"
              type="text"
              v-model="endDisplay"
              placeholder="dd/mm/yyyy"
              class="ch-input"
            />
          </div>

          <div class="ch-field">
            <label class="ch-label">Sentimen</label>
            <select v-model="sentiment" @change="fetchRows()" class="ch-input">
              <option value="">Semua</option>
              <option value="positive">Positif</option>
              <option value="neutral">Netral</option>
              <option value="negative">Negatif</option>
            </select>
          </div>

          <div class="ch-field">
            <label class="ch-label">Topik</label>
            <select v-model="topic" @change="fetchRows()" class="ch-input">
              <option value="">Semua topik</option>
              <option v-for="t in topics" :key="t" :value="t">
                {{ formatTopic(t) }}
              </option>
            </select>
          </div>

          <div class="ch-field">
            <label class="ch-label">Per Halaman</label>
            <select
              v-model.number="perPage"
              @change="fetchRows()"
              class="ch-input"
            >
              <option v-for="n in perPageOptions" :key="n" :value="n">
                {{ n }}
              </option>
            </select>
          </div>

          <div class="ch-field ch-field-action">
            <button @click="clearFilters" class="ch-btn-outline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="1 4 1 10 7 10" />
                <path d="M3.51 15a9 9 0 1 0 .49-3.51" />
              </svg>
              Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- ── Table Card ──────────────────────────────────────── -->
      <div class="ch-table-card">
        <!-- Loading overlay -->
        <Transition name="fade">
          <div v-if="loading" class="ch-loading">
            <svg
              class="ch-spinner"
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
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
              ></path>
            </svg>
            <span>Memuat data…</span>
          </div>
        </Transition>

        <div class="ch-table-wrap">
          <table class="ch-table">
            <thead>
              <tr>
                <th class="th-id">ID</th>
                <th>Isi / Pertanyaan</th>
                <th class="th-sm">Sentimen</th>
                <th class="th-md">Topik</th>
                <th class="th-sm">Waktu Jawab</th>
                <th class="th-md">Tanggal Kirim</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id" class="ch-row">
                <td class="td-id">{{ row.id }}</td>
                <td class="td-content">{{ row.content }}</td>
                <td>
                  <span
                    v-if="row.sentiment"
                    :class="`sentiment-badge sentiment-${row.sentiment}`"
                    >{{ formatTopic(row.sentiment) }}</span
                  >
                  <span v-else class="td-empty">—</span>
                </td>
                <td>
                  <span v-if="row.topic" class="topic-badge">{{
                    formatTopic(row.topic)
                  }}</span>
                  <span v-else class="td-empty">—</span>
                </td>
                <td>
                  <span
                    v-if="row.response_time_seconds != null"
                    class="rt-badge"
                    >{{ row.response_time_seconds }}s</span
                  >
                  <span v-else class="td-empty">—</span>
                </td>
                <td class="td-date">{{ formatDateTime(row.sent_at) }}</td>
              </tr>
              <tr v-if="!loading && rows.length === 0">
                <td colspan="6" class="ch-empty">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="36"
                    height="36"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                  >
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                  </svg>
                  <span>Tidak ada data ditemukan</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination footer -->
        <div class="ch-footer">
          <div class="ch-pagination-info">
            Halaman <strong>{{ page }}</strong> dari
            <strong>{{ lastPage }}</strong> &mdash;
            <strong>{{ total.toLocaleString('id-ID') }}</strong> total
          </div>
          <div class="ch-pagination-btns">
            <button
              :disabled="page <= 1 || loading"
              @click="go(page - 1)"
              class="ch-page-btn"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="15 18 9 12 15 6" />
              </svg>
              Prev
            </button>
            <button
              :disabled="page >= lastPage || loading"
              @click="go(page + 1)"
              class="ch-page-btn"
            >
              Next
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="9 18 15 12 9 6" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';

const formatTopic = (s) => {
  if (!s) return '';
  // replace underscores and dashes with spaces, then Title Case
  const parts = s.replace(/[-_]+/g, ' ').split(' ');
  return parts
    .map((p) => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase())
    .join(' ');
};

const rows = ref([]);
const topics = ref([]);
const loading = ref(false);
const avgResponseTime = ref(null);
const q = ref('');
const topic = ref('');
const sentiment = ref('');
const perPageOptions = [10, 25, 50, 100];
const perPage = ref(10);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const startDisplay = ref('');
const endDisplay = ref('');
const startIso = ref('');
const endIso = ref('');
const startFlat = ref(null);
const endFlat = ref(null);

const formatDateTime = (iso) => {
  if (!iso) return '-';
  const d = new Date(iso);
  const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ];
  const dd = String(d.getDate()).padStart(2, '0');
  const mo = months[d.getMonth()] || '';
  const yyyy = d.getFullYear();
  const hh = String(d.getHours()).padStart(2, '0');
  const mm = String(d.getMinutes()).padStart(2, '0');
  return `${dd} ${mo} ${yyyy}, ${hh}:${mm}`;
};

const go = (p) => {
  if (p < 1 || p > lastPage.value) return;
  page.value = p;
  fetchRows();
};

let debounceTimer;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchRows, 300);
};

const fetchRows = async () => {
  loading.value = true;
  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
      q: q.value || undefined,
      topic: topic.value || undefined,
      sentiment: sentiment.value || undefined,
      start_date: startIso.value || undefined,
      end_date: endIso.value || undefined,
    };
    const res = await axios.get('/api/admin/chat-history/list', { params });
    const j = res.data;
    rows.value = j.data || [];
    page.value = j.current_page || 1;
    lastPage.value = j.last_page || 1;
    total.value = j.total || 0;
  } finally {
    loading.value = false;
  }
};

const clearFilters = () => {
  q.value = '';
  topic.value = '';
  sentiment.value = '';
  startIso.value = '';
  endIso.value = '';
  startDisplay.value = '';
  endDisplay.value = '';
  page.value = 1;
  fetchRows();
};

onMounted(async () => {
  // init topics
  try {
    const meta = await axios.get('/api/admin/chat-history/meta');
    topics.value = meta.data?.topics || [];
    avgResponseTime.value = meta.data?.avg_response_time_seconds ?? null;
  } catch {}

  // init flatpickr
  try {
    if (startFlat.value) {
      flatpickr(startFlat.value, {
        dateFormat: 'd/m/Y',
        allowInput: true,
        onChange: (dates) => {
          const d = dates[0];
          if (d) {
            startDisplay.value = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
            startIso.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
          } else {
            startDisplay.value = '';
            startIso.value = '';
          }
          fetchRows();
        },
      });
    }
    if (endFlat.value) {
      flatpickr(endFlat.value, {
        dateFormat: 'd/m/Y',
        allowInput: true,
        onChange: (dates) => {
          const d = dates[0];
          if (d) {
            endDisplay.value = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
            endIso.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
          } else {
            endDisplay.value = '';
            endIso.value = '';
          }
          fetchRows();
        },
      });
    }
  } catch {}

  fetchRows();
});
</script>

<style scoped>
/* ── Page ─────────────────────────────────────────────────── */
.ch-page {
  padding: 24px;
  max-width: 1280px;
  margin: 0 auto;
}

/* ── Page Header ──────────────────────────────────────────── */
.ch-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.ch-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 3px;
}

.ch-sub {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.ch-total-badge {
  background: #ede9fe;
  color: #5b21b6;
  border-radius: 20px;
  padding: 5px 14px;
  font-size: 0.8125rem;
  font-weight: 600;
  white-space: nowrap;
}

.ch-avg-badge {
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 20px;
  padding: 5px 14px;
  font-size: 0.8125rem;
  white-space: nowrap;
}

/* ── Filter Card ──────────────────────────────────────────── */
.ch-filter-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
}

.ch-filter-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-end;
}

.ch-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.ch-field-action {
  justify-content: flex-end;
  margin-left: auto;
}

.ch-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.ch-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.ch-input-icon {
  position: absolute;
  left: 10px;
  color: #94a3b8;
  pointer-events: none;
}

.ch-input {
  height: 36px;
  padding: 0 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1e293b;
  background: #fff;
  outline: none;
  min-width: 140px;
  transition: border-color 0.2s;
  appearance: auto;
}

.ch-input-padded {
  padding-left: 32px;
}

.ch-input:focus {
  border-color: #7c3aed;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08);
}

.ch-btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  padding: 0 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}

.ch-btn-outline:hover {
  border-color: #7c3aed;
  color: #7c3aed;
  background: #faf5ff;
}

/* ── Table Card ───────────────────────────────────────────── */
.ch-table-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

/* ── Loading ──────────────────────────────────────────────── */
.ch-loading {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  z-index: 10;
  font-size: 0.875rem;
  color: #64748b;
}

.ch-spinner {
  width: 30px;
  height: 30px;
  animation: spin 0.8s linear infinite;
  color: #7c3aed;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── Table ────────────────────────────────────────────────── */
.ch-table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.ch-table {
  width: 100%;
  min-width: 680px;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.ch-table thead tr {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.ch-table th {
  padding: 11px 14px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  text-align: left;
  white-space: nowrap;
}

.th-id {
  width: 60px;
}
.th-sm {
  width: 110px;
}
.th-md {
  width: 200px;
}

.ch-table td {
  padding: 11px 14px;
  color: #1e293b;
  vertical-align: top;
}

.ch-row {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}

.ch-row:last-child {
  border-bottom: none;
}
.ch-row:hover {
  background: #faf5ff;
}

.td-id {
  color: #94a3b8;
  font-size: 0.8rem;
  font-family: monospace;
  white-space: nowrap;
}

.td-content {
  line-height: 1.5;
  white-space: pre-wrap;
  max-width: 380px;
}

.td-date {
  font-size: 0.8rem;
  color: #64748b;
  white-space: nowrap;
}

.td-empty {
  color: #cbd5e1;
}

/* ── Sentiment Badges ─────────────────────────────────────── */
.sentiment-badge {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.sentiment-positive {
  background: #dcfce7;
  color: #15803d;
}

.sentiment-neutral {
  background: #dbeafe;
  color: #1d4ed8;
}

.sentiment-negative {
  background: #fee2e2;
  color: #dc2626;
}

/* ── Topic Badge ──────────────────────────────────────────── */
.topic-badge {
  display: inline-block;
  padding: 2px 10px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* ── Response Time Badge ──────────────────────────────────── */
.rt-badge {
  display: inline-block;
  padding: 2px 8px;
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

/* ── Empty State ──────────────────────────────────────────── */
.ch-empty {
  text-align: center !important;
  padding: 48px 16px !important;
  color: #94a3b8;
}

.ch-empty svg {
  display: block;
  margin: 0 auto 10px;
  opacity: 0.4;
}

.ch-empty span {
  display: block;
}

/* ── Footer ───────────────────────────────────────────────── */
.ch-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 14px;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 8px;
}

.ch-pagination-info {
  font-size: 0.8rem;
  color: #64748b;
}

.ch-pagination-btns {
  display: flex;
  gap: 8px;
  align-items: center;
}

.ch-page-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 32px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  color: #374151;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.ch-page-btn:hover:not(:disabled) {
  border-color: #7c3aed;
  color: #7c3aed;
  background: #faf5ff;
}

.ch-page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ── Transitions ──────────────────────────────────────────── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Mobile ───────────────────────────────────────────────── */
@media (max-width: 640px) {
  .ch-page {
    padding: 16px;
  }
  .ch-filter-grid {
    flex-direction: column;
  }
  .ch-field {
    width: 100%;
  }
  .ch-field-action {
    margin-left: 0;
  }
  .ch-input {
    width: 100%;
    min-width: 0;
  }
}
</style>
