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

      <div class="mt-6">
        <h3 class="font-medium mb-2">Recent Reports</h3>
        <ul>
          <li v-for="r in reports" :key="r.id" class="p-3 border-b">
            <div class="flex justify-between">
              <div>
                <div class="font-medium">Report #{{ r.id }}</div>
                <div class="text-sm text-gray-500">
                  {{ r.start_date }} → {{ r.end_date }}
                </div>
              </div>
              <div class="text-right">
                <div>{{ r.chat_count }} chats</div>
                <div class="text-sm">{{ r.status }}</div>
              </div>
            </div>
          </li>
        </ul>
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

      <div
        v-if="
          reportSummary &&
          reportSummary.status === 'completed' &&
          reportSummary.summary_json
        "
        class="mt-6 bg-white p-4 rounded shadow"
      >
        <h3 class="font-medium mb-3">AI Insights</h3>
        <div class="grid grid-cols-2 gap-6">
          <div>
            <h4 class="font-medium">Top Topics</h4>
            <div
              v-if="
                reportSummary.summary_json &&
                reportSummary.summary_json.topics &&
                reportSummary.summary_json.topics.length
              "
            >
              <div v-for="t in topicBars" :key="t.label" class="my-2">
                <div class="flex justify-between text-sm">
                  <div>{{ t.label }}</div>
                  <div class="text-gray-500">{{ t.count }}</div>
                </div>
                <div class="w-full bg-gray-200 rounded h-3 mt-1">
                  <div
                    :style="{ width: t.width + '%' }"
                    class="bg-blue-600 h-3 rounded"
                  ></div>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500">No topics found.</div>
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

            <h4 class="font-medium mt-4">Top Cities</h4>
            <div
              v-if="
                reportSummary.summary_json?.geo_counts &&
                Object.keys(reportSummary.summary_json.geo_counts).length
              "
            >
              <div
                v-for="(c, city) in reportSummary.summary_json.geo_counts"
                :key="city"
                class="flex justify-between text-sm my-1"
              >
                <div>{{ city }}</div>
                <div class="text-gray-500">{{ c }}</div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500">No geo data.</div>
          </div>
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
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const startDate = ref('');
const endDate = ref('');
const reports = ref([]);
const reportSummary = ref(null);
const showLoading = ref(false);
const loading = ref(false);
const fastMode = ref(true);
const pollToken = ref(0);

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

// (single confirmStart above includes sample flag and starts polling tokenized)
</script>

<style scoped>
/* minimal styles */
</style>
