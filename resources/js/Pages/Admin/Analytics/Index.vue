<template>
  <AppLayout title="AI Chat Analytics">
    <div class="p-6">
      <h2 class="text-xl font-semibold mb-4">AI Chat Analytics</h2>

      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm text-gray-600">Start date</label>
          <input
            type="date"
            v-model="startDate"
            class="mt-1 p-2 border rounded w-full"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-600">End date</label>
          <input
            type="date"
            v-model="endDate"
            class="mt-1 p-2 border rounded w-full"
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
      </div>

      <div
        v-if="reportSummary"
        class="mt-4 p-4 bg-white rounded shadow space-y-1"
      >
        <div>
          {{ reportSummary.chat_count }} chats ({{
            reportSummary.message_count || '—'
          }}
          messages) between {{ startDate }} - {{ endDate }}
        </div>
        <div class="text-sm">
          Mode: {{ reportSummary.processing_mode || 'queued' }}
        </div>
        <div class="mt-1">Status: {{ reportSummary.status }}</div>
        <div
          v-if="
            reportSummary.processing_mode === 'sync' &&
            reportSummary.status === 'completed'
          "
          class="text-xs text-green-600"
        >
          Processed instantly (sync)
        </div>
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

            <h4 class="font-medium mt-4">Recommendations (Summary)</h4>
            <div class="mt-2">
              <ul class="list-decimal pl-6">
                <li
                  v-for="rec in reportSummary.summary_json?.recommendations ||
                  []"
                  :key="rec"
                  class="text-sm mb-1"
                >
                  {{ rec }}
                </li>
              </ul>
            </div>

            <div
              v-if="
                reportSummary.summary_json?.recommendations_detailed?.length
              "
              class="mt-6"
            >
              <h4 class="font-medium">Rekomendasi Detail (AI)</h4>
              <div class="mt-3 space-y-3">
                <div
                  v-for="(d, idx) in reportSummary.summary_json
                    .recommendations_detailed"
                  :key="idx"
                  class="border rounded p-3 bg-white"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <div class="font-semibold">
                        {{ d.label || d.category }}
                      </div>
                      <div class="text-xs text-gray-500">
                        Count: {{ d.count }}
                      </div>
                    </div>
                    <div class="text-xs">
                      <span
                        class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 mr-2"
                        >Priority: {{ d.priority || 'medium' }}</span
                      >
                      <span
                        class="px-2 py-0.5 rounded bg-gray-100 text-gray-700"
                        >Effort: {{ d.effort_estimate || 'sedang' }}</span
                      >
                    </div>
                  </div>
                  <div class="text-sm text-gray-800 mt-2 whitespace-pre-line">
                    {{ d.rationale }}
                  </div>
                  <ul
                    v-if="d.actions?.length"
                    class="list-disc pl-5 mt-2 text-sm"
                  >
                    <li v-for="(a, i) in d.actions" :key="i">{{ a }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <div
              v-if="reportSummary.summary_json?.insight_summary"
              class="mt-6"
            >
              <h4 class="font-medium">Insight Ringkas (AI)</h4>
              <div
                class="mt-2 whitespace-pre-line text-sm text-gray-800 bg-gray-50 p-3 rounded"
              >
                {{ reportSummary.summary_json.insight_summary }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="reportSummary.summary_json?.detailed_analysis" class="mt-8">
          <h4 class="font-semibold text-lg">Analisis Terperinci (AI)</h4>
          <p class="text-sm text-gray-500">
            ~1000 kata tentang hasil analitik, strategi, dan rekomendasi aksi.
          </p>
          <div
            class="mt-3 whitespace-pre-line text-gray-900 bg-white border rounded p-4 leading-7"
          >
            {{ reportSummary.summary_json.detailed_analysis }}
          </div>
        </div>

        <!-- <div class="mt-4">
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
              height="360"
              :options="categoryChartOptions"
              :series="categorySeries"
            />
          </div>
          <div v-else class="text-sm text-gray-500 mb-4">No category data.</div>
        </div>

        <div class="mt-4">
          <h4 class="font-medium">Recommendations</h4>
          <ul class="list-disc pl-6 mt-2">
            <li
              v-for="rec in reportSummary.summary_json?.recommendations || []"
              :key="rec"
              class="text-sm"
            >
              {{ rec }}
            </li>
          </ul>
        </div>

        <div
          v-if="
            reportSummary.summary_json?.per_chat &&
            reportSummary.summary_json.per_chat.length
          "
          class="mt-4"
        >
          <h4 class="font-medium">Sample per-chat classification</h4>
          <div class="overflow-auto mt-2">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-600">
                  <th class="pr-4">Chat ID</th>
                  <th class="pr-4">Category</th>
                  <th class="pr-4">Confidence</th>
                  <th>Sentiment</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="c in reportSummary.summary_json.per_chat.slice(0, 20)"
                  :key="c.chat_id"
                >
                  <td class="pr-4">{{ c.chat_id }}</td>
                  <td class="pr-4">{{ c.category }}</td>
                  <td class="pr-4">{{ (c.confidence || 0).toFixed(2) }}</td>
                  <td>{{ c.sentiment }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div> -->
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
      </div>

      <!-- Report modal -->
      <div
        v-if="modalOpen"
        class="fixed inset-0 bg-black/50 flex items-start justify-center p-6"
      >
        <div
          class="bg-white w-full max-w-5xl rounded shadow-lg overflow-auto max-h-[90vh]"
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
              <div>
                <h4 class="font-medium mb-2">AI Analysis (Top strategies)</h4>
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
                    <div class="text-sm text-gray-500">
                      Count: {{ s.count }}
                    </div>
                    <div class="mt-2 whitespace-pre-line text-sm">
                      {{ s.detailed_strategy }}
                    </div>
                    <div class="mt-3">
                      <div class="font-medium text-sm">
                        Implementation Steps
                      </div>
                      <ul class="list-decimal pl-6 text-sm">
                        <li
                          v-for="(st, idx) in s.implementation_steps || []"
                          :key="idx"
                        >
                          {{ st }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">
                  No top-topic strategies available.
                </div>
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
          <div class="text-sm text-gray-500 mt-2">
            This may take a while. You can close this modal and check reports
            later.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import AppLayout from '@/Layouts/AppLayout.vue';

const ApexChart = VueApexCharts;

const startDate = ref('');
const endDate = ref('');
const reports = ref([]);
const reportSummary = ref(null);
const showLoading = ref(false);
const loading = ref(false);
const fastMode = ref(true);
const pollToken = ref(0);
const modalOpen = ref(false);
const modalReport = ref({});

const loadReports = async () => {
  const res = await fetch('/api/admin/analytics/reports');
  reports.value = await res.json();
};

const confirmStart = async () => {
  if (!startDate.value || !endDate.value) return alert('Pilih tanggal mulai dan selesai.');
  if (startDate.value > endDate.value) return alert('Start harus sebelum End.');
  if (!confirm(`Start analysis for ${startDate.value} → ${endDate.value}?`)) return;
  loading.value = true;
  showLoading.value = true;
  const res = await fetch('/api/admin/analytics/start', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      start_date: startDate.value,
      end_date: endDate.value,
      sample: fastMode.value,
    }),
  });
  const j = await res.json();
  reportSummary.value = j;
  loading.value = false;
  if (
    j.processing_mode === 'sync'
    && (j.status === 'completed' || j.summary_json)
  ) {
    showLoading.value = false;
    await loadReports();
    return;
  }
  pollToken.value++;
  pollReport(j.report_id, pollToken.value);
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
</script>

<style scoped>
/* minimal styles */
</style>
