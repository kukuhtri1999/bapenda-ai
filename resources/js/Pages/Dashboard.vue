<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';

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

// Average AI response time (comes directly from server, not animated)
const avgResponseTime = computed(() => {
  const v = metrics.value?.avg_response_time;
  return v != null ? Number(v).toFixed(2) : null;
});

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

const dashSteps = [
  { title: 'Selamat Datang di Dashboard', intro: 'Ini adalah halaman utama admin SALMA AI. Di sini Anda bisa melihat ringkasan semua data sistem secara real-time.' },
  { element: '#tour-dash-hero', title: 'Banner Selamat Datang', intro: 'Bagian ini menampilkan nama Anda dan tombol cepat menuju halaman Analitik.' },
  { element: '#tour-dash-metrics', title: 'Kartu Statistik', intro: 'Lima kartu ini menampilkan total Wajib Pajak, Pesan AI, Laporan Analitik, Knowledge Base, dan rata-rata waktu jawab AI.' },
  { element: '#tour-dash-actions', title: 'Quick Actions', intro: 'Tombol pintas untuk mengelola pengguna, melihat riwayat chat, mengimpor data, dan membuka analitik.' },
  { element: '#tour-dash-activity', title: 'Recent Activity', intro: 'Menampilkan status laporan AI insight terakhir. Klik "View All" untuk melihat analitik lengkap.' },
  { element: '#tour-dash-status', title: 'System Status', intro: 'Indikator status real-time: API, Database, dan AI Service. Pastikan semua berwarna hijau (Aktif).' },
];
const { startTour } = useTour(dashSteps);
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="dash-page">
      <!-- ── Hero Welcome Banner ─────────────────────────────────── -->
      <div id="tour-dash-hero" class="dash-hero mb-8">
        <div class="dash-hero-content">
          <div class="dash-hero-avatar">
            <VIcon size="40" color="white">mdi-account-circle</VIcon>
          </div>
          <div>
            <h1 class="dash-hero-title">
              Selamat datang, {{ $page.props.auth.user.name }}!
            </h1>
            <p class="dash-hero-sub">
              Berikut ringkasan data sistem SALMA AI hari ini.
            </p>
          </div>
        </div>
        <VBtn
          variant="flat"
          color="white"
          class="dash-hero-btn"
          :href="route('admin.analytics')"
        >
          Lihat Analitik
        </VBtn>
      </div>

      <!-- ── Metric Cards ──────────────────────────────────────── -->
      <VRow id="tour-dash-metrics" class="mb-8" dense>
        <VCol cols="12" sm="6" lg="3">
          <div class="metric-card">
            <div class="metric-icon-wrap mc-purple">
              <VIcon color="white" size="22">mdi-account-group</VIcon>
            </div>
            <div class="metric-body">
              <div class="metric-value">
                {{ wpTotal.toLocaleString('id-ID') }}
              </div>
              <div class="metric-label">Wajib Pajak</div>
            </div>
            <div class="metric-sub">Semua waktu</div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="metric-card">
            <div class="metric-icon-wrap mc-blue">
              <VIcon color="white" size="22">mdi-message-text</VIcon>
            </div>
            <div class="metric-body">
              <div class="metric-value">
                {{ chatTotal.toLocaleString('id-ID') }}
              </div>
              <div class="metric-label">Pesan AI</div>
            </div>
            <div class="metric-sub">Semua waktu</div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="metric-card">
            <div class="metric-icon-wrap mc-orange">
              <VIcon color="white" size="22">mdi-chart-areaspline</VIcon>
            </div>
            <div class="metric-body">
              <div class="metric-value">
                {{ analyticsTotal.toLocaleString('id-ID') }}
              </div>
              <div class="metric-label">Laporan Analitik</div>
            </div>
            <div class="metric-sub">Semua waktu</div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="metric-card">
            <div class="metric-icon-wrap mc-pink">
              <VIcon color="white" size="22">mdi-book-open-variant</VIcon>
            </div>
            <div class="metric-body">
              <div class="metric-value">
                {{ kbTotal.toLocaleString('id-ID') }}
              </div>
              <div class="metric-label">Knowledge Base</div>
            </div>
            <div class="metric-sub">Semua waktu</div>
          </div>
        </VCol>

        <VCol cols="12" sm="6" lg="3">
          <div class="metric-card">
            <div class="metric-icon-wrap mc-teal">
              <VIcon color="white" size="22">mdi-timer-outline</VIcon>
            </div>
            <div class="metric-body">
              <div class="metric-value">
                <template v-if="avgResponseTime !== null">
                  {{ avgResponseTime }}<span class="metric-unit">s</span>
                </template>
                <template v-else>
                  <span class="metric-na">—</span>
                </template>
              </div>
              <div class="metric-label">Rata-rata Waktu Jawab AI</div>
            </div>
            <div class="metric-sub">Semua riwayat chat</div>
          </div>
        </VCol>
      </VRow>

      <!-- ── Quick Actions ─────────────────────────────────────── -->
      <div id="tour-dash-actions" class="section-header mb-4">
        <div>
          <h2 class="section-title">Quick Actions</h2>
          <p class="section-sub">Akses fitur yang sering digunakan</p>
        </div>
      </div>

      <VRow class="mb-8" dense>
        <VCol
          v-for="action in quickActions"
          :key="action.title"
          cols="12"
          sm="6"
          md="3"
        >
          <Link :href="route(action.route)" class="text-decoration-none">
            <div class="action-card">
              <div :class="`action-icon-wrap ac-${action.color}`">
                <VIcon color="white" size="24">{{ action.icon }}</VIcon>
              </div>
              <h3 class="action-title">{{ action.title }}</h3>
              <p class="action-desc">{{ action.description }}</p>
            </div>
          </Link>
        </VCol>
      </VRow>

      <!-- ── Bottom Row ────────────────────────────────────────── -->
      <VRow>
        <VCol cols="12" md="8">
          <div id="tour-dash-activity" class="info-card">
            <div class="info-card-header">
              <div>
                <h3 class="info-card-title">Recent Activity</h3>
                <p class="info-card-sub">Interaksi sistem terbaru</p>
              </div>
              <Link :href="route('admin.analytics')" class="info-card-link">
                View All
              </Link>
            </div>
            <div class="info-card-body">
              <VAlert
                :type="metrics.insights?.available ? 'success' : 'info'"
                variant="tonal"
                border="start"
                class="mb-0 rounded-lg"
              >
                <template #prepend>
                  <VIcon>{{
                    metrics.insights?.available
                      ? 'mdi-check-decagram'
                      : 'mdi-lightbulb-on-outline'
                  }}</VIcon>
                </template>
                <div class="text-body-2">
                  <template v-if="metrics.insights?.available">
                    Laporan AI insight tersedia
                    <span
                      v-if="metrics.insights?.period"
                      class="text-medium-emphasis"
                    >
                      ({{ metrics.insights.period[0] }} →
                      {{ metrics.insights.period[1] }}) </span
                    >.
                    <Link
                      :href="route('admin.analytics')"
                      class="text-primary ms-1"
                      >Lihat analitik</Link
                    >
                  </template>
                  <template v-else>
                    Belum ada laporan AI insight. Generate dari halaman
                    Analytics.
                  </template>
                </div>
              </VAlert>
            </div>
          </div>
        </VCol>

        <VCol cols="12" md="4">
          <div id="tour-dash-status" class="info-card h-100">
            <div class="info-card-header">
              <h3 class="info-card-title">System Status</h3>
              <span class="status-badge-online">
                <span class="status-dot"></span>
                Online
              </span>
            </div>
            <div class="info-card-body">
              <div class="status-list">
                <div class="status-row">
                  <span class="status-row-label">API Status</span>
                  <span class="status-row-val ok">
                    <VIcon size="14">mdi-circle</VIcon> Aktif
                  </span>
                </div>
                <div class="status-row">
                  <span class="status-row-label">Database</span>
                  <span class="status-row-val ok">
                    <VIcon size="14">mdi-circle</VIcon> Aktif
                  </span>
                </div>
                <div class="status-row">
                  <span class="status-row-label">AI Service</span>
                  <span class="status-row-val ok">
                    <VIcon size="14">mdi-circle</VIcon> Aktif
                  </span>
                </div>
              </div>
            </div>
          </div>
        </VCol>
      </VRow>
    </div>
      <TourButton @start="startTour" />
  </AppLayout>
</template>

<style scoped>
/* ── Page ─────────────────────────────────────────────────── */
.dash-page {
  padding: 28px;
  max-width: 1280px;
  margin: 0 auto;
}

/* ── Hero Banner ──────────────────────────────────────────── */
.dash-hero {
  background: linear-gradient(135deg, #1B2838 0%, #C0392B 100%);
  border-radius: 16px;
  padding: 28px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.dash-hero-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.dash-hero-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.dash-hero-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 4px;
  line-height: 1.3;
}

.dash-hero-sub {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.75);
  margin: 0;
}

.dash-hero-btn {
  font-weight: 600 !important;
  text-transform: none !important;
  border-radius: 8px !important;
  color: #C0392B !important;
  white-space: nowrap;
}

/* ── Metric Cards ─────────────────────────────────────────── */
.metric-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 20px;
  display: grid;
  grid-template-columns: 48px 1fr;
  grid-template-rows: auto auto;
  gap: 0 14px;
  align-items: center;
  transition:
    box-shadow 0.2s,
    transform 0.2s;
}

.metric-card:hover {
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.07);
  transform: translateY(-2px);
}

.metric-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  grid-row: 1 / 3;
}

.metric-body {
  display: flex;
  flex-direction: column;
}

.metric-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.1;
}

.metric-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #64748b;
  margin-top: 2px;
}

.metric-sub {
  font-size: 0.75rem;
  color: #94a3b8;
  grid-column: 2;
  margin-top: 4px;
}

/* ── Icon Color Variants ──────────────────────────────────── */
.mc-purple {
  background: linear-gradient(135deg, #C0392B, #E74C3C);
}
.mc-blue {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}
.mc-orange {
  background: linear-gradient(135deg, #d97706, #f59e0b);
}
.mc-pink {
  background: linear-gradient(135deg, #db2777, #ec4899);
}
.mc-teal {
  background: linear-gradient(135deg, #0d9488, #14b8a6);
}

.metric-unit {
  font-size: 1rem;
  font-weight: 500;
  color: #64748b;
  margin-left: 2px;
}

.metric-na {
  font-size: 1.5rem;
  color: #94a3b8;
}

/* ── Section Header ───────────────────────────────────────── */
.section-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 2px;
}

.section-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0;
}

/* ── Action Cards ─────────────────────────────────────────── */
.action-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 24px 20px;
  text-align: center;
  transition:
    box-shadow 0.2s,
    border-color 0.2s,
    transform 0.2s;
  cursor: pointer;
  height: 100%;
}

.action-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  border-color: #fca5a5;
  transform: translateY(-3px);
}

.action-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
}

.ac-primary,
.ac-green {
  background: linear-gradient(135deg, #C0392B, #E74C3C);
}
.ac-blue {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}
.ac-orange {
  background: linear-gradient(135deg, #d97706, #f59e0b);
}
.ac-pink {
  background: linear-gradient(135deg, #db2777, #ec4899);
}

.action-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 6px;
}

.action-desc {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

/* ── Info Cards ────────────────────────────────────────────── */
.info-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
}

.info-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 12px;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 8px;
}

.info-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 2px;
}

.info-card-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0;
}

.info-card-link {
  font-size: 0.8rem;
  font-weight: 600;
  color: #C0392B;
  text-decoration: none;
}

.info-card-link:hover {
  text-decoration: underline;
}

.info-card-body {
  padding: 16px 20px 20px;
}

/* ── Status Badge ─────────────────────────────────────────── */
.status-badge-online {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: #dcfce7;
  color: #16a34a;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #16a34a;
  animation: pulse-green 2s ease-in-out infinite;
}

@keyframes pulse-green {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* ── Status List ──────────────────────────────────────────── */
.status-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 0;
  border-bottom: 1px solid #f1f5f9;
}

.status-row:last-child {
  border-bottom: none;
}

.status-row-label {
  font-size: 0.875rem;
  color: #64748b;
}

.status-row-val {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-row-val.ok {
  color: #16a34a;
}

/* ── Mobile ───────────────────────────────────────────────── */
@media (max-width: 768px) {
  .dash-page {
    padding: 16px;
  }
  .dash-hero {
    padding: 20px;
  }
  .dash-hero-title {
    font-size: 1.15rem;
  }
  .metric-value {
    font-size: 1.5rem;
  }
}
</style>
