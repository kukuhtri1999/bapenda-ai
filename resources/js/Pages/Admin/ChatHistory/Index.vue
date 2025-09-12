<template>
  <AppLayout title="AI Chat History">
    <div class="p-6">
      <div class="flex flex-wrap items-end gap-3 mb-4">
        <div>
          <label class="block text-xs text-gray-600">Search</label>
          <input
            v-model="q"
            @input="debouncedFetch()"
            placeholder="Cari pertanyaan..."
            class="px-3 py-2 border rounded w-64"
          />
        </div>
        <div>
          <label class="block text-xs text-gray-600">Start date</label>
          <input
            ref="startFlat"
            type="text"
            v-model="startDisplay"
            placeholder="dd/mm/yyyy"
            class="px-3 py-2 border rounded w-40"
          />
        </div>
        <div>
          <label class="block text-xs text-gray-600">End date</label>
          <input
            ref="endFlat"
            type="text"
            v-model="endDisplay"
            placeholder="dd/mm/yyyy"
            class="px-3 py-2 border rounded w-40"
          />
        </div>
        <div>
          <label class="block text-xs text-gray-600">Sentiment</label>
          <select
            v-model="sentiment"
            @change="fetchRows()"
            class="px-3 py-2 border rounded w-40"
          >
            <option value="">All</option>
            <option value="positive">Positive</option>
            <option value="neutral">Neutral</option>
            <option value="negative">Negative</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600">Topic</label>
          <select
            v-model="topic"
            @change="fetchRows()"
            class="px-3 py-2 border rounded w-56"
          >
            <option value="">All topics</option>
            <option v-for="t in topics" :key="t" :value="t">
              {{ formatTopic(t) }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600">Per page</label>
          <select
            v-model.number="perPage"
            @change="fetchRows()"
            class="px-3 py-2 border rounded w-28"
          >
            <option v-for="n in perPageOptions" :key="n" :value="n">
              {{ n }}
            </option>
          </select>
        </div>
        <div class="ml-auto">
          <button @click="clearFilters" class="px-3 py-2 border rounded">
            Clear
          </button>
        </div>
      </div>

      <div class="bg-white rounded shadow overflow-auto relative">
        <Transition name="fade">
          <div
            v-if="loading"
            class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-10"
          >
            <div class="flex flex-col items-center gap-3">
              <svg
                class="animate-spin h-8 w-8 text-blue-600"
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
              <span class="text-sm text-gray-600">Loading messages…</span>
            </div>
          </div>
        </Transition>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-left">
            <tr>
              <th class="p-3 w-16">ID</th>
              <th class="p-3">Content / Question</th>
              <th class="p-3 w-28">Sentiment</th>
              <th class="p-3 w-56">Topic</th>
              <th class="p-3 w-56">Sent At</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id" class="border-t align-top">
              <td class="p-3 text-gray-600">{{ row.id }}</td>
              <td class="p-3 whitespace-pre-wrap">{{ row.content }}</td>
              <td class="p-3 capitalize">{{ row.sentiment || '-' }}</td>
              <td class="p-3">
                {{ row.topic ? formatTopic(row.topic) : '-' }}
              </td>
              <td class="p-3">{{ formatDateTime(row.sent_at) }}</td>
            </tr>
            <tr v-if="!loading && rows.length === 0">
              <td colspan="5" class="p-6 text-center text-gray-500">No data</td>
            </tr>
          </tbody>
        </table>
        <div class="p-3 flex items-center justify-between">
          <div class="text-xs text-gray-600">
            Page {{ page }} of {{ lastPage }} — {{ total }} total
          </div>
          <div class="flex gap-2">
            <button
              class="px-3 py-1 border rounded"
              :disabled="page <= 1 || loading"
              @click="go(page - 1)"
            >
              Prev
            </button>
            <button
              class="px-3 py-1 border rounded"
              :disabled="page >= lastPage || loading"
              @click="go(page + 1)"
            >
              Next
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
/* Simple, clean table UI */
th,
td {
  vertical-align: top;
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
