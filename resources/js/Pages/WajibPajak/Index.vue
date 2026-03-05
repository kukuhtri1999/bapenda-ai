<template>
  <AppLayout title="Wajib Pajak">
    <div class="wp-page">
      <!-- Page Header -->
      <div class="wp-header">
        <div class="wp-header-left">
          <h2 class="wp-title">Daftar Wajib Pajak</h2>
          <p class="wp-subtitle">Kelola data wajib pajak kendaraan bermotor</p>
        </div>
        <div class="wp-header-right">
          <div class="wp-search-group">
            <input
              v-model="q"
              @input="debouncedSearch"
              placeholder="Cari nama / nopol / WA…"
              class="wp-search-input"
            />
            <button @click="search" class="wp-btn wp-btn-primary">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
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
              <span>Cari</span>
            </button>
            <button @click="reset" class="wp-btn wp-btn-outline">Reset</button>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="wp-card">
        <!-- Loading overlay -->
        <Transition name="fade">
          <div v-if="loading" class="wp-loading">
            <svg
              class="wp-spinner"
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

        <!-- Responsive table wrapper -->
        <div class="wp-table-wrapper">
          <table class="wp-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Nopol</th>
                <th>No. WA</th>
                <th>Tanggal Dibuat</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!data.data || data.data.length === 0">
                <td colspan="5" class="wp-empty">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="40"
                    height="40"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                  </svg>
                  <span>Tidak ada data ditemukan</span>
                </td>
              </tr>
              <tr v-for="row in data.data" :key="row.id" class="wp-row">
                <td data-label="ID" class="wp-td-id">{{ row.id }}</td>
                <td data-label="Nama">{{ row.nama }}</td>
                <td data-label="Nopol">
                  <span class="wp-badge-nopol">{{ row.nopol }}</span>
                </td>
                <td data-label="No. WA">{{ row.nomer_wa }}</td>
                <td data-label="Dibuat">{{ formatDate(row.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination footer -->
        <div class="wp-footer">
          <div class="wp-pagination-info">
            <template v-if="data.total > 0">
              Menampilkan <strong>{{ data.from }}</strong
              >–<strong>{{ data.to }}</strong> dari
              <strong>{{ data.total }}</strong> data
            </template>
            <template v-else>Tidak ada data</template>
          </div>
          <div class="wp-pagination-controls">
            <button
              @click="goto(data.current_page - 1)"
              :disabled="data.current_page <= 1 || loading"
              class="wp-page-btn"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
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
            <span class="wp-page-indicator"
              >{{ data.current_page }} / {{ data.last_page || 1 }}</span
            >
            <button
              @click="goto(data.current_page + 1)"
              :disabled="data.current_page >= data.last_page || loading"
              class="wp-page-btn"
            >
              Next
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
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
import AppLayout from '@/Layouts/AppLayout.vue';

const q = ref('');
const loading = ref(false);
const data = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
});

const fetchList = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/admin/wajib-pajak/list', {
      params: { page, q: q.value || undefined },
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    data.value = res.data;
  } finally {
    loading.value = false;
  }
};

let searchTimer;
const debouncedSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchList(1), 300);
};

const search = () => fetchList(1);
const reset = () => {
  q.value = '';
  fetchList(1);
};
const goto = (page) => {
  if (!page || page < 1 || page > (data.value.last_page || 1)) return;
  fetchList(page);
};

onMounted(() => fetchList(1));

const formatDate = (d) => (d ? new Date(d).toLocaleString('id-ID') : '-');
</script>

<style scoped>
/* ── Page Layout ───────────────────────────────────────────── */
.wp-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Page Header ───────────────────────────────────────────── */
.wp-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.wp-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 2px;
}

.wp-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

/* ── Search Controls ───────────────────────────────────────── */
.wp-search-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.wp-search-input {
  height: 38px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1e293b;
  background: #fff;
  outline: none;
  min-width: 200px;
  flex: 1;
  transition: border-color 0.2s;
}

.wp-search-input:focus {
  border-color: #7c3aed;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08);
}

.wp-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 38px;
  padding: 0 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  border: none;
}

.wp-btn-primary {
  background: #7c3aed;
  color: #fff;
}

.wp-btn-primary:hover {
  background: #6d28d9;
}

.wp-btn-outline {
  background: #fff;
  color: #64748b;
  border: 1px solid #e2e8f0 !important;
}

.wp-btn-outline:hover {
  background: #f8fafc;
  border-color: #cbd5e1 !important;
}

/* ── Card ──────────────────────────────────────────────────── */
.wp-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

/* ── Loading Overlay ───────────────────────────────────────── */
.wp-loading {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  z-index: 10;
  font-size: 0.875rem;
  color: #64748b;
}

.wp-spinner {
  width: 32px;
  height: 32px;
  animation: spin 1s linear infinite;
  color: #7c3aed;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── Table ─────────────────────────────────────────────────── */
.wp-table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.wp-table {
  width: 100%;
  min-width: 560px;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.wp-table thead tr {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.wp-table th {
  padding: 12px 16px;
  font-weight: 600;
  color: #64748b;
  text-align: left;
  font-size: 0.8125rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.wp-table td {
  padding: 12px 16px;
  color: #1e293b;
  vertical-align: middle;
}

.wp-row {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}

.wp-row:last-child {
  border-bottom: none;
}

.wp-row:hover {
  background: #faf5ff;
}

.wp-td-id {
  color: #94a3b8;
  font-size: 0.8125rem;
  font-family: monospace;
}

.wp-badge-nopol {
  display: inline-block;
  padding: 2px 10px;
  background: #ede9fe;
  color: #5b21b6;
  border-radius: 20px;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.05em;
}

/* ── Empty State ───────────────────────────────────────────── */
.wp-empty {
  text-align: center;
  padding: 48px 16px;
  color: #94a3b8;
}

.wp-empty {
  display: table-cell;
}

.wp-empty svg {
  display: block;
  margin: 0 auto 12px;
  opacity: 0.5;
}

/* ── Footer / Pagination ───────────────────────────────────── */
.wp-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 8px;
}

.wp-pagination-info {
  font-size: 0.8125rem;
  color: #64748b;
}

.wp-pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.wp-page-btn {
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

.wp-page-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #7c3aed;
  color: #7c3aed;
}

.wp-page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.wp-page-indicator {
  font-size: 0.8125rem;
  color: #64748b;
  padding: 0 4px;
}

/* ── Fade Transition ───────────────────────────────────────── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Mobile Responsive ─────────────────────────────────────── */
@media (max-width: 640px) {
  .wp-page {
    padding: 16px;
  }

  .wp-header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .wp-search-group {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 8px;
  }

  .wp-search-input {
    min-width: 0;
    grid-column: 1 / -1;
  }

  .wp-title {
    font-size: 1.2rem;
  }

  .wp-footer {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
