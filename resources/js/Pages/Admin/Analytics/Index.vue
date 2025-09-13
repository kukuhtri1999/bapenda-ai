<template>
  <AppLayout title="AI Chat Analytics">
    <div class="p-6">
      <div class="flex gap-4 mb-4">
        <div class="w-1/2">
          <label class="block text-sm text-gray-600"
            >Start date (dd/mm/yyyy)</label
          >
          <input
            ref="startFlat"
            type="text"
            v-model="startDisplay"
            placeholder="dd/mm/yyyy"
            class="mt-1 p-2 border rounded w-full bg-white"
          />
        </div>
        <div class="w-1/2">
          <label class="block text-sm text-gray-600"
            >End date (dd/mm/yyyy)</label
          >
          <input
            ref="endFlat"
            type="text"
            v-model="endDisplay"
            placeholder="dd/mm/yyyy"
            class="mt-1 p-2 border rounded w-full bg-white"
          />
        </div>
      </div>

      <div class="flex gap-3 items-center">
        <button
          @click="confirmStart"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded"
        >
          Start Analysis
        </button>
        <button @click="loadReports" class="px-4 py-2 bg-gray-200 rounded">
          Refresh Reports
        </button>
        <label class="ml-4 inline-flex items-center text-sm text-gray-600">
          <input type="checkbox" v-model="fastMode" class="mr-2" />
          Fast (sample)
        </label>

        <!-- pre-count box -->
        <div
          v-if="preCount"
          class="ml-6 border-2 border-red-500 text-red-600 px-3 py-2 rounded"
        >
          {{ preCount.message_count }} messages Found
        </div>
        <!-- <div class="mb-3 text-sm text-gray-700" v-if="preCount">
          Data in range: {{ preCount.chat_count }} chats,
          {{ preCount.message_count }} messages
        </div> -->
      </div>

      <div
        v-if="
          reportSummary &&
          reportSummary.status === 'completed' &&
          reportSummary.summary_json
        "
        class="mt-6 bg-white p-4 rounded shadow"
      >
        <h3 class="font-medium mb-3">AI Insights</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="col-span-1 lg:col-span-1">
            <h4 class="font-medium mb-2">Chats per Category</h4>
            <div
              v-if="
                reportSummary?.summary_json?.categories &&
                reportSummary.summary_json.categories.length
              "
              class="mb-4"
            >
              <ApexChart
                type="bar"
                height="520"
                :options="categoryChartOptions"
                :series="categorySeries"
              />
            </div>
            <div v-else class="text-sm text-gray-500 mb-4">
              No category data.
            </div>
          </div>

          <div>
            <h4 class="font-medium">Sentiments</h4>
            <div class="mt-2">
              <div
                v-for="(v, k) in reportSummary.summary_json?.sentiments || {}"
                :key="k"
                class="flex items-center justify-between text-sm mb-2"
              >
                <div class="capitalize">{{ k }}</div>
                <div class="text-gray-600">{{ v }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6">
          <h4 class="font-medium">Dokumen Insight (AI)</h4>

          <div
            class="mt-3 text-sm text-gray-900 bg-white border rounded p-3 leading-7 space-y-3"
          >
            <p v-for="(p, i) in paragraphize(combinedInsightText)" :key="i">
              {{ p }}
            </p>
          </div>
        </div>

        <div class="mt-8">
          <h4 class="font-medium mb-2">AI Analysis (Top strategies)</h4>
          <div v-if="reportSummary.summary_json?.top_topics_strategies">
            <div
              v-for="(s, key) in reportSummary.summary_json
                .top_topics_strategies"
              :key="key"
              class="mb-4 border rounded p-3"
            >
              <div class="font-semibold">{{ s.label || s.topic_key }}</div>
              <div class="text-sm text-gray-500" v-if="s.count">
                Count: {{ s.count }}
              </div>
              <div class="mt-2 text-sm space-y-2">
                <p v-for="(p, i) in paragraphize(s.detailed_strategy)" :key="i">
                  {{ p }}
                </p>
              </div>
              <div class="mt-3">
                <div class="font-medium text-sm">Implementation Steps</div>
                <div
                  v-if="
                    Array.isArray(s.implementation_steps) &&
                    s.implementation_steps.length
                  "
                  class="space-y-3 mt-2"
                >
                  <div
                    v-for="(st, idx) in s.implementation_steps"
                    :key="idx"
                    class="text-sm border rounded p-3 bg-gray-50"
                  >
                    <template v-if="typeof st === 'string'">
                      <div class="font-medium">Step {{ idx + 1 }}</div>
                      <div class="mt-1 whitespace-pre-line">{{ st }}</div>
                    </template>
                    <template v-else>
                      <div class="flex items-start justify-between">
                        <div class="font-medium">
                          {{ st.title || `Step ${idx + 1}` }}
                        </div>
                        <div
                          class="text-xs text-gray-500"
                          v-if="st.estimated_time || st.effort"
                        >
                          <span v-if="st.estimated_time">{{
                            st.estimated_time
                          }}</span>
                          <span v-if="st.estimated_time && st.effort"> • </span>
                          <span v-if="st.effort">Effort: {{ st.effort }}</span>
                        </div>
                      </div>
                      <div class="mt-1 whitespace-pre-line">
                        {{ st.description || '' }}
                      </div>
                      <div v-if="st.example" class="mt-2 text-xs text-gray-600">
                        <div class="uppercase tracking-wide">Example</div>
                        <pre
                          class="mt-1 whitespace-pre-wrap bg-white border rounded p-2"
                          >{{ st.example }}</pre
                        >
                      </div>
                    </template>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500 mt-1">
                  No steps provided.
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-500">
            No top-topic strategies available.
          </div>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-medium mb-2">Recent Reports</h3>
        <ul>
          <li v-for="r in reports" :key="r.id" class="p-3 border-b">
            <div class="flex justify-between">
              <div>
                <div class="font-medium">Report #{{ r.id }}</div>
                <div class="text-sm text-gray-500">
                  {{ humanDate(r.start_date) }} → {{ humanDate(r.end_date) }}
                </div>
              </div>
              <div class="text-right">
                <div>{{ r.chat_count }} chats</div>
                <div class="text-sm">{{ r.status }}</div>
                <div class="mt-2">
                  <button
                    @click="openReport(r.id)"
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded"
                  >
                    View report
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>
        <div
          class="flex items-center justify-between mt-3"
          v-if="reportLastPage > 1"
        >
          <div class="text-sm text-gray-600">
            Page {{ reportPage }} of {{ reportLastPage }} —
            {{ reportTotal }} total
          </div>
          <div class="flex items-center gap-2">
            <button
              class="px-3 py-1 rounded bg-gray-200 text-sm"
              :disabled="reportPage <= 1"
              @click="loadReports(reportPage - 1)"
            >
              Prev
            </button>
            <button
              class="px-3 py-1 rounded bg-gray-200 text-sm"
              :disabled="reportPage >= reportLastPage"
              @click="loadReports(reportPage + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Report modal -->
      <div
        v-if="modalOpen"
        class="fixed inset-0 bg-black/50 flex items-start justify-center p-6 z-[4200]"
      >
        <div
          class="bg-white w-full max-w-5xl rounded shadow-lg overflow-auto max-h-[90vh] z-[4300] mt-12"
        >
          <div class="flex items-center justify-between p-4 border-b">
            <div class="font-semibold">
              Report #{{ modalReport.id }} —
              {{ humanDate(modalReport.start_date) }} →
              {{ humanDate(modalReport.end_date) }}
            </div>
            <button @click="closeModal" class="px-3 py-1 bg-gray-200 rounded">
              Close
            </button>
          </div>
          <div class="p-4">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
              <div>
                <h4 class="font-medium mb-2">Charts</h4>
                <div v-if="modalReport.summary_json?.categories?.length">
                  <ApexChart
                    type="bar"
                    height="420"
                    :options="modalCategoryOptions"
                    :series="modalCategorySeries"
                  />
                </div>
                <div v-else class="text-sm text-gray-500">
                  No category data.
                </div>
              </div>
            </div>
            <div class="mt-6">
              <h4 class="font-medium">Overview AI Insight</h4>
              <div
                class="mt-2 text-sm text-gray-800 bg-gray-50 p-3 rounded space-y-3"
              >
                <p
                  v-for="(p, i) in paragraphize(modalCombinedInsightText)"
                  :key="i"
                >
                  {{ p }}
                </p>
              </div>
            </div>
            <div>
              <h4 class="font-medium mb-2 mt-8">
                AI Analysis (Top strategies)
              </h4>
              <div v-if="modalReport.summary_json?.top_topics_strategies">
                <div
                  v-for="(s, key) in modalReport.summary_json
                    .top_topics_strategies"
                  :key="key"
                  class="mb-4 border rounded p-3"
                >
                  <div class="font-semibold">
                    {{ s.label || s.topic_key }}
                  </div>
                  <div class="text-sm text-gray-500">Count: {{ s.count }}</div>
                  <div class="mt-2 text-sm space-y-2">
                    <p
                      v-for="(p, i) in paragraphize(s.detailed_strategy)"
                      :key="i"
                    >
                      {{ p }}
                    </p>
                  </div>
                  <div class="mt-3">
                    <div class="font-medium text-sm">Implementation Steps</div>
                    <div
                      v-if="
                        Array.isArray(s.implementation_steps) &&
                        s.implementation_steps.length
                      "
                      class="space-y-3 mt-2"
                    >
                      <div
                        v-for="(st, idx) in s.implementation_steps"
                        :key="idx"
                        class="text-sm border rounded p-3 bg-gray-50"
                      >
                        <template v-if="typeof st === 'string'">
                          <div class="font-medium">Step {{ idx + 1 }}</div>
                          <div class="mt-1 whitespace-pre-line">{{ st }}</div>
                        </template>
                        <template v-else>
                          <div class="flex items-start justify-between">
                            <div class="font-medium">
                              {{ st.title || `Step ${idx + 1}` }}
                            </div>
                            <div
                              class="text-xs text-gray-500"
                              v-if="st.estimated_time || st.effort"
                            >
                              <span v-if="st.estimated_time">{{
                                st.estimated_time
                              }}</span>
                              <span v-if="st.estimated_time && st.effort">
                                •
                              </span>
                              <span v-if="st.effort"
                                >Effort: {{ st.effort }}</span
                              >
                            </div>
                          </div>
                          <div class="mt-1 whitespace-pre-line">
                            {{ st.description || '' }}
                          </div>
                          <div
                            v-if="st.example"
                            class="mt-2 text-xs text-gray-600"
                          >
                            <div class="uppercase tracking-wide">Example</div>
                            <pre
                              class="mt-1 whitespace-pre-wrap bg-white border rounded p-2"
                              >{{ st.example }}</pre
                            >
                          </div>
                        </template>
                      </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 mt-1">
                      No steps provided.
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500">
                No top-topic strategies available.
              </div>
            </div>
            <div
              v-if="modalReport.summary_json?.detailed_analysis"
              class="mt-6"
            >
              <h4 class="font-medium">Analisis Terperinci (AI)</h4>
              <div
                class="mt-2 whitespace-pre-line text-sm text-gray-800 bg-gray-50 p-3 rounded"
              >
                {{ modalReport.summary_json.detailed_analysis }}
              </div>
            </div>
            <div v-if="modalPerTopicInsightsLimited.length" class="mt-6">
              <h4 class="font-medium">Insight Per Topik (AI)</h4>
              <div class="mt-3 space-y-4">
                <div
                  v-for="(p, idx) in modalPerTopicInsightsLimited"
                  :key="p.topic_key || p.label || idx"
                  class="border rounded p-3"
                >
                  <div class="font-semibold">
                    {{ p.label || p.topic_key || `Topik ${idx + 1}` }}
                  </div>
                  <div v-if="p.count" class="text-xs text-gray-500">
                    Count: {{ p.count }}
                  </div>
                  <div class="mt-2 whitespace-pre-line text-sm text-gray-800">
                    {{ p.long_insight || p.insight || p.text }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="showLoading"
        class="fixed inset-0 bg-black/40 flex items-center justify-center"
      >
        <div class="bg-white p-6 rounded shadow w-96 text-center">
          <div class="mb-4">
            <svg
              class="mx-auto animate-spin h-12 w-12 text-blue-600"
              viewBox="0 0 24 24"
              fill="none"
            >
              <circle
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
                stroke-opacity="0.25"
              />
              <path
                d="M22 12a10 10 0 00-10-10"
                stroke="currentColor"
                stroke-width="4"
                stroke-linecap="round"
              />
            </svg>
          </div>
          <div class="text-lg font-medium">
            Analyzing taxpayer conversations… please wait.
          </div>
          <div class="text-sm text-gray-500 mt-2">This may take a while.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import {
  ref, onMounted, computed, watch,
} from 'vue';
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import flatpickr from 'flatpickr';
import AppLayout from '@/Layouts/AppLayout.vue';
import 'flatpickr/dist/flatpickr.min.css';

const ApexChart = VueApexCharts;

const startDate = ref('');
const endDate = ref('');
const startIso = ref(null); // yyyy-mm-dd
const endIso = ref(null);
// Date objects used by Datepicker
const startDateObj = ref(null);
const endDateObj = ref(null);
const startDateInput = ref(null);
const endDateInput = ref(null);
const startDisplay = ref('');
const endDisplay = ref('');
const startFlat = ref(null);
const endFlat = ref(null);
const reports = ref([]);
const reportSummary = ref(null);
const displayFormat = 'dd/MM/yyyy';
const onIsoChange = () => {
  /* noop - watcher handles updates */
};
const showLoading = ref(false);
const loading = ref(false);
const fastMode = ref(false);
const pollToken = ref(0);
const modalOpen = ref(false);
const modalReport = ref({});
const preCount = ref(null);
const reportPage = ref(1);
const reportPerPage = ref(5);
const reportTotal = ref(0);
const reportLastPage = ref(1);

const loadReports = async (page = 1) => {
  const url = `/api/admin/analytics/reports?page=${page}&per_page=${reportPerPage.value}`;
  const res = await fetch(url);
  const j = await res.json();
  reports.value = j.data || [];
  reportPage.value = j.current_page || page;
  reportPerPage.value = j.per_page || reportPerPage.value;
  reportTotal.value = j.total || (j.data ? j.data.length : 0);
  reportLastPage.value = j.last_page || 1;
};

const updatePreCount = async () => {
  preCount.value = null;
  if (!startIso.value || !endIso.value) return;
  if (startIso.value > endIso.value) return;
  try {
    const res = await axios.get('/api/admin/analytics/count', {
      params: { start_date: startIso.value, end_date: endIso.value },
    });
    preCount.value = res.data;
  } catch (e) {
    console.warn('Pre-count failed', e?.message || e);
  }
};

const confirmStart = async () => {
  if (!startIso.value || !endIso.value) return alert('Pilih tanggal mulai dan selesai.');
  if (startIso.value > endIso.value) return alert('Start harus sebelum End.');
  if (
    !confirm(`Start analysis for ${startDisplay.value} → ${endDisplay.value}?`)
  ) return;
  loading.value = true;
  showLoading.value = true;
  try {
    const res = await axios.post('/api/admin/analytics/start', {
      start_date: startIso.value,
      end_date: endIso.value,
      sample: fastMode.value,
    });
    const j = res.data;
    reportSummary.value = j;
    loading.value = false;
    // If sync mode and summary is present, show it immediately
    if (j.processing_mode === 'sync') {
      if (j.summary_json) {
        showLoading.value = false;
        await loadReports();
        return;
      }
      if (j.status === 'completed') {
        // fetch the saved report to ensure we have summary_json
        const r = await fetch(`/api/admin/analytics/reports/${j.report_id}`);
        reportSummary.value = await r.json();
        showLoading.value = false;
        await loadReports();
        return;
      }
    }
    pollToken.value++;
    pollReport(j.report_id, pollToken.value);
  } catch (e) {
    loading.value = false;
    showLoading.value = false;
    alert(
      `Failed to start analysis: ${e?.response?.data?.message || e.message}`,
    );
  }
};

const pollReport = async (id, token) => {
  const myToken = token;

  const tick = async () => {
    // stop if token has changed (a new poll started)
    if (pollToken.value !== myToken) return;
    const r = await fetch(`/api/admin/analytics/reports/${id}`);
    const data = await r.json();
    reportSummary.value = data;
    if (data.status === 'completed' || data.status === 'failed') {
      // If summary_json missing in the immediate response, re-fetch once
      if (!data.summary_json) {
        try {
          await new Promise((r) => setTimeout(r, 500));
          const r2 = await fetch(`/api/admin/analytics/reports/${id}`);
          const d2 = await r2.json();
          reportSummary.value = d2;
        } catch (e) {
          /* ignore */
        }
      }
      showLoading.value = false;
      await loadReports();
      return;
    }
    setTimeout(tick, 3000);
  };
  tick();
};

onMounted(() => {
  loadReports();
  // initialize flatpickr on the two inputs
  try {
    if (startFlat.value) {
      flatpickr(startFlat.value, {
        dateFormat: 'd/m/Y',
        allowInput: true,
        defaultDate: startIso.value || null,
        onChange: (selectedDates) => {
          const d = selectedDates[0] || null;
          startDateObj.value = d;
          if (d) {
            startDisplay.value = formatDisplay(d);
            startIso.value = toIso(d);
          } else {
            startDisplay.value = '';
            startIso.value = null;
          }
          updatePreCount();
        },
      });
    }
    if (endFlat.value) {
      flatpickr(endFlat.value, {
        dateFormat: 'd/m/Y',
        allowInput: true,
        defaultDate: endIso.value || null,
        onChange: (selectedDates) => {
          const d = selectedDates[0] || null;
          endDateObj.value = d;
          if (d) {
            endDisplay.value = formatDisplay(d);
            endIso.value = toIso(d);
          } else {
            endDisplay.value = '';
            endIso.value = null;
          }
          updatePreCount();
        },
      });
    }
  } catch (e) {
    console.warn('flatpickr init failed', e);
  }
});

// helpers: format Date -> dd/MM/yyyy and to ISO yyyy-mm-dd
const formatDisplay = (d) => {
  if (!d) return '';
  const day = String(d.getDate()).padStart(2, '0');
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const yr = d.getFullYear();
  return `${day}/${mo}/${yr}`;
};

const toIso = (d) => {
  if (!d) return null;
  const day = String(d.getDate()).padStart(2, '0');
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const yr = d.getFullYear();
  return `${yr}-${mo}-${day}`;
};

// (native date inputs are used; handlers below manage display ↔ ISO sync)

// parse dd/mm/yyyy -> yyyy-mm-dd or return null
const parseDisplayToIso = (s) => {
  if (!s) return null;
  const m = s.trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (!m) return null;
  const d = m[1].padStart(2, '0');
  const mo = m[2].padStart(2, '0');
  const y = m[3];
  // simple validation
  const iso = `${y}-${mo}-${d}`;
  const dt = new Date(iso);
  if (isNaN(dt.getTime())) return null;
  return iso;
};

const onStartDisplayInput = () => {
  const iso = parseDisplayToIso(startDisplay.value);
  startIso.value = iso;
};

const onEndDisplayInput = () => {
  const iso = parseDisplayToIso(endDisplay.value);
  endIso.value = iso;
};

const openStartPicker = () => {
  const el = startDateInput.value;
  if (!el) return;
  // modern browsers support showPicker()
  if (typeof el.showPicker === 'function') {
    try {
      el.showPicker();
      return;
    } catch (e) {
      /* fallthrough */
    }
  }
  // fallback: focus the input to trigger the native UI
  try {
    el.focus();
  } catch (e) {
    /* ignore */
  }
};

const openEndPicker = () => {
  const el = endDateInput.value;
  if (!el) return;
  if (typeof el.showPicker === 'function') {
    try {
      el.showPicker();
      return;
    } catch (e) {
      /* fallthrough */
    }
  }
  try {
    el.focus();
  } catch (e) {
    /* ignore */
  }
};

// convert Date object -> ISO (yyyy-mm-dd)
const dateToIso = (d) => {
  if (!d) return null;
  const yy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yy}-${mm}-${dd}`;
};

watch([startDateObj, endDateObj], () => {
  startIso.value = dateToIso(startDateObj.value);
  endIso.value = dateToIso(endDateObj.value);
  startDisplay.value = startIso.value
    ? `${startIso.value.split('-')[2]}/${startIso.value.split('-')[1]}/${startIso.value.split('-')[0]}`
    : '';
  endDisplay.value = endIso.value
    ? `${endIso.value.split('-')[2]}/${endIso.value.split('-')[1]}/${endIso.value.split('-')[0]}`
    : '';
  updatePreCount();
});

const humanDate = (iso) => {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    const months = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember',
    ];
    const day = String(d.getDate()).padStart(2, '0');
    return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
  } catch (e) {
    return iso;
  }
};

const openReport = async (id) => {
  modalOpen.value = true;
  modalReport.value = { id };
  const r = await fetch(`/api/admin/analytics/reports/${id}`);
  modalReport.value = await r.json();
};

const closeModal = () => {
  modalOpen.value = false;
  modalReport.value = {};
};

const modalCategoryOptions = computed(() => {
  const cats = (modalReport.value?.summary_json?.categories || [])
    .slice()
    .sort((a, b) => b.count - a.count);
  const labels = cats.map((c) => c.label || c.name);
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 6 } },
    dataLabels: { enabled: false },
    xaxis: { categories: labels },
  };
});

const modalCategorySeries = computed(() => [
  {
    name: 'Messages',
    data: (modalReport.value?.summary_json?.categories || []).map(
      (c) => c.count,
    ),
  },
]);

const topicBars = computed(() => {
  const topics = reportSummary.value?.summary_json?.topics || [];
  if (!topics.length) return [];
  const max = Math.max(...topics.map((t) => t.count), 1);
  return topics.map((t) => ({
    label: t.label,
    count: t.count,
    width: Math.round((t.count / max) * 100),
  }));
});

const categoryChartOptions = computed(() => {
  const cats = (reportSummary.value?.summary_json?.categories || [])
    .slice()
    .sort((a, b) => b.count - a.count);
  const labels = cats.map((c) => c.label || c.name);
  const data = cats.map((c) => c.count);
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
      bar: { horizontal: true, distributed: false, borderRadius: 6 },
    },
    dataLabels: { enabled: false },
    xaxis: { categories: labels, labels: { style: { fontSize: '13px' } } },
    yaxis: { labels: { style: { fontSize: '13px' } } },
    responsive: [{ breakpoint: 1024, options: { chart: { height: 420 } } }],
    colors: ['#2563eb'],
  };
});

const categorySeries = computed(() => {
  const cats = (reportSummary.value?.summary_json?.categories || [])
    .slice()
    .sort((a, b) => b.count - a.count);
  return [{ name: 'Messages', data: cats.map((c) => c.count) }];
});

// (single confirmStart above includes sample flag and starts polling tokenized)

// UI constants and helpers
const topCount = 3;

const topDetailedRecs = computed(() => {
  const recs = reportSummary.value?.summary_json?.recommendations_detailed || [];
  return recs.slice(0, topCount);
});

const perTopicInsightsLimited = computed(() => {
  const pti = reportSummary.value?.summary_json?.per_topic_insights;
  const strategies = reportSummary.value?.summary_json?.top_topics_strategies || {};
  const entries = pti && !Array.isArray(pti) ? Object.entries(pti) : [];
  return entries.slice(0, topCount).map(([key, text]) => ({
    topic_key: key,
    label: strategies?.[key]?.label || key,
    count: strategies?.[key]?.count,
    text,
  }));
});

const modalPerTopicInsightsLimited = computed(() => {
  const pti = modalReport.value?.summary_json?.per_topic_insights;
  const strategies = modalReport.value?.summary_json?.top_topics_strategies || {};
  const entries = pti && !Array.isArray(pti) ? Object.entries(pti) : [];
  return entries.slice(0, topCount).map(([key, text]) => ({
    topic_key: key,
    label: strategies?.[key]?.label || key,
    count: strategies?.[key]?.count,
    text,
  }));
});

// Single combined insight text (AI-only; no static fallbacks)
const combinedInsightText = computed(() => {
  // Prefer root-level persisted fields when present
  const rootTxt = reportSummary.value?.combined_top_insight;
  if (rootTxt && typeof rootTxt === 'string' && rootTxt.trim().length > 0) return rootTxt;
  const txt = reportSummary.value?.summary_json?.combined_top_insight;
  if (txt && typeof txt === 'string' && txt.trim().length > 0) return txt;
  const rootSummary = reportSummary.value?.insight_summary;
  if (
    rootSummary
    && typeof rootSummary === 'string'
    && rootSummary.trim().length > 0
  ) return rootSummary;
  const detailed = reportSummary.value?.summary_json?.detailed_analysis;
  if (detailed && typeof detailed === 'string' && detailed.trim().length > 0) return detailed;
  const summary = reportSummary.value?.summary_json?.insight_summary;
  if (summary && typeof summary === 'string' && summary.trim().length > 0) return summary;
  return 'Menunggu hasil analisis AI… jalankan Start Analysis atau refresh laporan.';
});

const modalCombinedInsightText = computed(() => {
  const rootTxt = modalReport.value?.combined_top_insight;
  if (rootTxt && typeof rootTxt === 'string' && rootTxt.trim().length > 0) return rootTxt;
  const txt = modalReport.value?.summary_json?.combined_top_insight;
  if (txt && typeof txt === 'string' && txt.trim().length > 0) return txt;
  const rootSummary = modalReport.value?.insight_summary;
  if (
    rootSummary
    && typeof rootSummary === 'string'
    && rootSummary.trim().length > 0
  ) return rootSummary;
  const detailed = modalReport.value?.summary_json?.detailed_analysis;
  if (detailed && typeof detailed === 'string' && detailed.trim().length > 0) return detailed;
  const summary = modalReport.value?.summary_json?.insight_summary;
  if (summary && typeof summary === 'string' && summary.trim().length > 0) return summary;
  return 'Menunggu hasil analisis AI…';
});

// Split long AI text into readable paragraphs. Prefer double newlines; fallback to sentence chunks.
const paragraphize = (text) => {
  if (!text || typeof text !== 'string') return [];
  const t = text.trim();
  if (!t) return [];
  // If the text already has blank lines, split on them
  const byDoubleNewline = t.split(/\n\s*\n+/);
  const nonEmpty = byDoubleNewline
    .map((p) => p.trim())
    .filter((p) => p.length > 0);
  if (nonEmpty.length > 1) return nonEmpty;
  // Otherwise chunk by sentences (~3-4 sentences per paragraph)
  const sentences = t.split(/(?<=[.!?])\s+(?=[A-ZÀ-ÖØ-Þ0-9])/u);
  const out = [];
  let buf = [];
  sentences.forEach((s) => {
    buf.push(s);
    if (buf.join(' ').length > 400 || buf.length >= 4) {
      out.push(buf.join(' '));
      buf = [];
    }
  });
  if (buf.length) out.push(buf.join(' '));
  return out;
};
</script>

<style scoped>
/* minimal styles */
</style>
