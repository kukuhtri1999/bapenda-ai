<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const metrics = computed(() => page.props.metrics || {});

const resolvePath = (obj, path) => {
  if (!obj || !path) return undefined;
  return path
    .split('.')
    .reduce(
      (o, k) => (o && Object.prototype.hasOwnProperty.call(o, k) ? o[k] : undefined),
      obj,
    );
};

const getTotalFromCandidates = (candidates = [], fallback = 0) => {
  for (const p of candidates) {
    let v = resolvePath(metrics.value, p);
    if (v === undefined) v = resolvePath(page.props, p);
    if (v === undefined) continue;
    // if nested object with total
    if (
      typeof v === 'object'
      && v !== null
      && Object.prototype.hasOwnProperty.call(v, 'total')
    ) v = v.total;
    const n = Number(v);
    if (!Number.isNaN(n)) return n;
  }
  return fallback;
};

const animatedValue = (target, duration = 800) => {
  const value = ref(0);
  let start;
  const step = (ts) => {
    if (!start) start = ts;
    const progress = Math.min((ts - start) / duration, 1);
    value.value = Math.floor(progress * target);
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
  return value;
};

// Candidates for totals (try a few likely keys so server naming differences are tolerated)
const wpAllTotal = getTotalFromCandidates([
  'wajib_pajak.total',
  'wajib_pajak_total',
  'wajibPajak.total',
  'wajibPajakTotal',
  'wajib_pajak',
]);
const chatAllTotal = getTotalFromCandidates([
  'chat_messages.total',
  'chat_messages_total',
  'chatMessages.total',
  'chatMessagesTotal',
  'chat_messages',
]);
const analyticsAllTotal = getTotalFromCandidates([
  'analytics.total',
  'analytics_total',
  'analytics_reports.total',
  'analytics_reports_total',
  'insights.total',
  'insights_total',
  'analyticsReportsTotal',
]);
const kbAllTotal = getTotalFromCandidates([
  'knowledge_base.total',
  'knowledge_base_total',
  'knowledgeBases.total',
  'knowledge_bases_total',
  'kb_total',
  'knowledge_base_count',
]);

const wpTotal = animatedValue(wpAllTotal || 0);
const chatTotal = animatedValue(chatAllTotal || 0, 1000);
const analyticsTotal = animatedValue(analyticsAllTotal || 0, 1000);
const kbTotal = animatedValue(kbAllTotal || 0, 1000);

const quickActions = [
  {
    title: 'Kelola Pengguna',
    description: 'Tambah, edit, atau hapus pengguna sistem',
    icon: 'mdi-account-cog',
    color: 'primary',
    route: 'users.index',
  },
  {
    title: 'Riwayat Chat',
    description: 'Telusuri dan filter pesan AI',
    icon: 'mdi-history',
    color: 'blue',
    route: 'admin.chat-history.index',
  },
  {
    title: 'Impor Pesan Chat',
    description: 'Unggah riwayat chat dari file XLSX',
    icon: 'mdi-file-excel',
    color: 'green',
    route: 'admin.chat-import',
  },
  {
    title: 'Analitik AI',
    description: 'Lihat insight dan rekomendasi',
    icon: 'mdi-chart-areaspline',
    color: 'orange',
    route: 'admin.analytics',
  },
];

onMounted(() => {
  // Any additional client-only effects
});
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="pa-10">
      <!-- Welcome Header -->
      <div class="mb-8">
        <h1 class="text-h4 font-weight-bold text-grey-800 mb-2">
          Selamat datang, {{ $page.props.auth.user.name }}!
        </h1>
        <p class="text-body-1 text-grey-600">
          Berikut ringkasan data sistem Anda saat ini.
        </p>
      </div>

      <!-- Domain Metrics (Modern Cards) -->
      <VRow class="mb-8">
        <VCol cols="12" sm="6" lg="3">
          <VCard class="metric-card h-100" elevation="0">
            <VCardText class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <div class="metric-icon-wrapper purple">
                  <VIcon color="white" size="24">mdi-account-group</VIcon>
                </div>
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn
                      icon="mdi-dots-horizontal"
                      variant="text"
                      size="small"
                      v-bind="props"
                      class="text-grey-400"
                    />
                  </template>
                  <VList density="compact">
                    <VListItem>View Details</VListItem>
                    <VListItem>Export Data</VListItem>
                  </VList>
                </VMenu>
              </div>
              <div class="mb-2">
                <h2 class="text-h4 font-weight-bold text-grey-800 mb-1">
                  {{ wpTotal.toLocaleString('id-ID') }}
                </h2>
                <p class="text-body-2 text-grey-600 mb-0">
                  Total Wajib Pajak (Semua Waktu)
                </p>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <VCard class="metric-card h-100" elevation="0">
            <VCardText class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <div class="metric-icon-wrapper blue">
                  <VIcon color="white" size="24">mdi-account-plus</VIcon>
                </div>
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn
                      icon="mdi-dots-horizontal"
                      variant="text"
                      size="small"
                      v-bind="props"
                      class="text-grey-400"
                    />
                  </template>
                  <VList density="compact">
                    <VListItem>View Details</VListItem>
                    <VListItem>Export Data</VListItem>
                  </VList>
                </VMenu>
              </div>
              <div class="mb-2">
                <h2 class="text-h4 font-weight-bold text-grey-800 mb-1">
                  {{ chatTotal.toLocaleString('id-ID') }}
                </h2>
                <p class="text-body-2 text-grey-600 mb-0">
                  Total Pesan AI (Semua Waktu)
                </p>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <VCard class="metric-card h-100" elevation="0">
            <VCardText class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <div class="metric-icon-wrapper orange">
                  <VIcon color="white" size="24">mdi-robot</VIcon>
                </div>
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn
                      icon="mdi-dots-horizontal"
                      variant="text"
                      size="small"
                      v-bind="props"
                      class="text-grey-400"
                    />
                  </template>
                  <VList density="compact">
                    <VListItem>View Details</VListItem>
                    <VListItem>Export Data</VListItem>
                  </VList>
                </VMenu>
              </div>
              <div class="mb-2">
                <h2 class="text-h4 font-weight-bold text-grey-800 mb-1">
                  {{ analyticsTotal.toLocaleString('id-ID') }}
                </h2>
                <p class="text-body-2 text-grey-600 mb-0">
                  Total Laporan Analitik AI (Semua Waktu)
                </p>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <VCard class="metric-card h-100" elevation="0">
            <VCardText class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <div class="metric-icon-wrapper pink">
                  <VIcon color="white" size="24">mdi-heart</VIcon>
                </div>
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn
                      icon="mdi-dots-horizontal"
                      variant="text"
                      size="small"
                      v-bind="props"
                      class="text-grey-400"
                    />
                  </template>
                  <VList density="compact">
                    <VListItem>View Details</VListItem>
                    <VListItem>Export Data</VListItem>
                  </VList>
                </VMenu>
              </div>
              <div class="mb-2">
                <h2 class="text-h4 font-weight-bold text-grey-800 mb-1">
                  {{ kbTotal.toLocaleString('id-ID') }}
                </h2>
                <p class="text-body-2 text-grey-600 mb-0">
                  Total Knowledge Base (Semua Waktu)
                </p>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Quick Actions Section -->
      <VRow class="mb-8">
        <VCol cols="12">
          <div class="d-flex align-center justify-space-between mb-6">
            <div>
              <h2 class="text-h5 font-weight-bold text-grey-800 mb-1">
                Quick Actions
              </h2>
              <p class="text-body-2 text-grey-600 mb-0">
                Access frequently used features
              </p>
            </div>
            <VBtn
              variant="outlined"
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
            >
              Add Action
            </VBtn>
          </div>

          <VRow>
            <VCol
              v-for="action in quickActions"
              :key="action.title"
              cols="12"
              sm="6"
              md="3"
            >
              <Link :href="route(action.route)" class="text-decoration-none">
                <VCard class="action-card h-100" elevation="0" hover>
                  <VCardText class="pa-6 text-center">
                    <div class="mb-4">
                      <div :class="`action-icon-wrapper ${action.color}`">
                        <VIcon color="white" size="24">{{ action.icon }}</VIcon>
                      </div>
                    </div>
                    <h3 class="text-h6 font-weight-bold text-grey-800 mb-2">
                      {{ action.title }}
                    </h3>
                    <p class="text-body-2 text-grey-600 mb-0">
                      {{ action.description }}
                    </p>
                  </VCardText>
                </VCard>
              </Link>
            </VCol>
          </VRow>
        </VCol>
      </VRow>

      <!-- Recent Activity Section -->
      <VRow>
        <VCol cols="12" md="8">
          <VCard class="activity-card" elevation="0">
            <VCardTitle class="pa-6 pb-0">
              <div class="d-flex align-center justify-space-between w-100">
                <div>
                  <h3 class="text-h6 font-weight-bold text-grey-800">
                    Recent Activity
                  </h3>
                  <p class="text-body-2 text-grey-600 mb-0">
                    Latest system interactions
                  </p>
                </div>
                <VBtn variant="text" size="small" color="primary">
                  View All
                </VBtn>
              </div>
            </VCardTitle>
            <VCardText class="pa-6 pt-4">
              <VAlert
                :type="metrics.insights.available ? 'success' : 'info'"
                variant="tonal"
                border="start"
                class="mb-0"
              >
                <template #prepend>
                  <VIcon>{{
                    metrics.insights.available
                      ? 'mdi-check-decagram'
                      : 'mdi-lightbulb-on-outline'
                  }}</VIcon>
                </template>
                <div class="text-body-2">
                  <template v-if="metrics.insights.available">
                    Latest AI insight report is available
                    <span
                      v-if="metrics.insights.period"
                      class="text-medium-emphasis"
                    >
                      ({{ metrics.insights.period[0] }} →
                      {{ metrics.insights.period[1] }}) </span
                    >.
                    <Link
                      :href="route('admin.analytics')"
                      class="text-primary ml-1"
                      >View analytics</Link
                    >
                  </template>
                  <template v-else>
                    No completed AI insight reports yet. Generate from Analytics
                    page.
                  </template>
                </div>
              </VAlert>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="4">
          <VCard class="status-card" elevation="0">
            <VCardText class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <h3 class="text-h6 font-weight-bold text-grey-800">
                  System Status
                </h3>
                <VChip color="success" size="small" variant="tonal">
                  <VIcon start>mdi-check</VIcon>
                  Online
                </VChip>
              </div>

              <div class="status-items">
                <div class="status-item">
                  <div class="d-flex align-center justify-space-between mb-2">
                    <span class="text-body-2 text-grey-600">API Status</span>
                    <VIcon color="success" size="16">mdi-circle</VIcon>
                  </div>
                </div>
                <div class="status-item">
                  <div class="d-flex align-center justify-space-between mb-2">
                    <span class="text-body-2 text-grey-600">Database</span>
                    <VIcon color="success" size="16">mdi-circle</VIcon>
                  </div>
                </div>
                <div class="status-item">
                  <div class="d-flex align-center justify-space-between mb-2">
                    <span class="text-body-2 text-grey-600">AI Service</span>
                    <VIcon color="success" size="16">mdi-circle</VIcon>
                  </div>
                </div>
              </div>

              <VBtn
                block
                variant="outlined"
                color="primary"
                size="small"
                class="mt-4"
              >
                View Details
              </VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Modern Card Styles */
.metric-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  transition: all 0.2s ease;
}

.metric-card:hover {
  border-color: #e2e8f0;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.action-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.action-card:hover {
  border-color: #e2e8f0;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.activity-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
}

.status-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
}

/* Icon Wrappers */
.metric-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

/* Color Variants */
.purple {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
}

.blue {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
}

.orange {
  background: linear-gradient(135deg, #f59e0b, #f97316);
}

.pink {
  background: linear-gradient(135deg, #ec4899, #f43f5e);
}

.primary {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
}

.success {
  background: linear-gradient(135deg, #10b981, #059669);
}

/* Status Items */
.status-item {
  padding: 8px 0;
}

.status-item:not(:last-child) {
  border-bottom: 1px solid #f1f5f9;
}

/* Text Colors */
.text-grey-800 {
  color: #1f2937 !important;
}

.text-grey-600 {
  color: #6b7280 !important;
}

.text-grey-400 {
  color: #9ca3af !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .metric-card,
  .action-card,
  .activity-card,
  .status-card {
    margin-bottom: 16px;
  }
}
</style>
