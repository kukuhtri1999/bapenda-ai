<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const metrics = computed(
  () => page.props.metrics || {
    wajib_pajak: { total: 0, this_month: 0 },
    chat_messages: { total: 0, this_week: 0 },
    insights: { available: false, period: null },
  },
);

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

const wpTotal = animatedValue(metrics.value.wajib_pajak.total || 0);
const wpMonth = animatedValue(metrics.value.wajib_pajak.this_month || 0, 900);
const chatTotal = animatedValue(metrics.value.chat_messages.total || 0, 1000);
const chatWeek = animatedValue(metrics.value.chat_messages.this_week || 0, 900);

const quickActions = [
  {
    title: 'Manage Users',
    description: 'Add, edit, or remove system users',
    icon: 'mdi-account-cog',
    color: 'primary',
    route: 'users.index',
  },
  {
    title: 'View Chat History',
    description: 'Browse and filter AI messages',
    icon: 'mdi-history',
    color: 'blue',
    route: 'admin.chat-history.index',
  },
  {
    title: 'Import Chat Messages',
    description: 'Unggah riwayat chat dari file XLSX',
    icon: 'mdi-file-excel',
    color: 'green',
    route: 'admin.chat-import',
  },
  {
    title: 'AI Analytics',
    description: 'View insights and recommendations',
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
    <div class="pa-0">
      <!-- <VRow class="mb-6">
        <VCol cols="12">
          <VCard
            class="bg-gradient-to-r from-primary to-blue-600 rounded-xl"
            elevation="6"
          >
            <VCardText class="pa-8">
              <VRow align="center">
                <VCol cols="12" md="8">
                  <h1 class="text-h4 font-weight-bold mb-2">
                    Welcome back, {{ $page.props.auth.user.name }}!
                  </h1>
                  <p class="text-h6 font-weight-light mb-4">
                    Quick overview of taxpayers, chats, and AI insights.
                  </p>
                  <VChip
                    class="ma-1"
                    color="rgba(255,255,255,0.2)"
                    text-color="white"
                  >
                    <VIcon left>mdi-clock</VIcon>
                    {{
                      new Date().toLocaleDateString('id-ID', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })
                    }}
                  </VChip>
                </VCol>
                <VCol cols="12" md="4" class="text-center">
                  <VIcon size="110" class="opacity-60">mdi-city-variant</VIcon>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow> -->

      <!-- Domain Metrics (Flat) -->
      <VRow class="mb-6">
        <VCol cols="12" sm="6" lg="3">
          <VCard
            variant="flat"
            elevation="0"
            class="h-100 rounded-xl border border-gray-200"
          >
            <VCardText>
              <div class="flex items-center justify-between mb-2">
                <p class="text-body-2 text-medium-emphasis">
                  Total Wajib Pajak
                </p>
                <VAvatar size="36" color="primary" variant="tonal"
                  ><VIcon>mdi-account-group</VIcon></VAvatar
                >
              </div>
              <h2 class="text-h4 font-weight-bold">
                {{ wpTotal.toLocaleString('id-ID') }}
              </h2>
              <div class="text-caption text-medium-emphasis mt-1">
                Cumulative registered taxpayers
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" sm="6" lg="3">
          <VCard
            variant="flat"
            elevation="0"
            class="h-100 rounded-xl border border-gray-200"
          >
            <VCardText>
              <div class="flex items-center justify-between mb-2">
                <p class="text-body-2 text-medium-emphasis">
                  Wajib Pajak (Bulan Ini)
                </p>
                <VAvatar size="36" color="success" variant="tonal"
                  ><VIcon>mdi-calendar-month</VIcon></VAvatar
                >
              </div>
              <h2 class="text-h4 font-weight-bold">
                {{ wpMonth.toLocaleString('id-ID') }}
              </h2>
              <div class="text-caption text-medium-emphasis mt-1">
                New entries this month
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" sm="6" lg="3">
          <VCard
            variant="flat"
            elevation="0"
            class="h-100 rounded-xl border border-gray-200"
          >
            <VCardText>
              <div class="flex items-center justify-between mb-2">
                <p class="text-body-2 text-medium-emphasis">AI Messages</p>
                <VAvatar size="36" color="blue" variant="tonal"
                  ><VIcon>mdi-robot</VIcon></VAvatar
                >
              </div>
              <h2 class="text-h4 font-weight-bold">
                {{ chatTotal.toLocaleString('id-ID') }}
              </h2>
              <div class="text-caption text-medium-emphasis mt-1">
                Total messages processed
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" sm="6" lg="3">
          <VCard
            variant="flat"
            elevation="0"
            class="h-100 rounded-xl border border-gray-200"
          >
            <VCardText>
              <div class="flex items-center justify-between mb-2">
                <p class="text-body-2 text-medium-emphasis">
                  AI Messages (Minggu Ini)
                </p>
                <VAvatar size="36" color="indigo" variant="tonal"
                  ><VIcon>mdi-calendar-week</VIcon></VAvatar
                >
              </div>
              <h2 class="text-h4 font-weight-bold">
                {{ chatWeek.toLocaleString('id-ID') }}
              </h2>
              <div class="text-caption text-medium-emphasis mt-1">
                Volume this week
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Quick Actions (SPA Links) -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard
            variant="flat"
            elevation="0"
            class="rounded-xl border border-gray-200"
          >
            <VCardTitle class="d-flex align-center">
              <VIcon class="mr-2">mdi-lightning-bolt</VIcon>
              Quick Actions
            </VCardTitle>
            <VCardText>
              <VRow>
                <VCol
                  v-for="action in quickActions"
                  :key="action.title"
                  cols="12"
                  sm="6"
                  md="3"
                >
                  <Link :href="route(action.route)" class="no-underline">
                    <VCard
                      variant="flat"
                      elevation="0"
                      class="h-100 cursor-pointer transition-transform hover:-translate-y-0.5 rounded-lg border border-gray-300"
                    >
                      <VCardText class="text-center pa-6">
                        <VAvatar
                          size="60"
                          :color="action.color"
                          variant="tonal"
                          class="mb-4"
                        >
                          <VIcon size="30">{{ action.icon }}</VIcon>
                        </VAvatar>
                        <h3 class="text-h6 font-weight-medium mb-2">
                          {{ action.title }}
                        </h3>
                        <p class="text-body-2 text-medium-emphasis">
                          {{ action.description }}
                        </p>
                      </VCardText>
                    </VCard>
                  </Link>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Insights banner -->
      <VRow>
        <VCol cols="12">
          <VAlert
            :type="metrics.insights.available ? 'success' : 'info'"
            variant="flat"
            border="start"
            prominent
          >
            <template #prepend>
              <VIcon>{{
                metrics.insights.available
                  ? 'mdi-check-decagram'
                  : 'mdi-lightbulb-on-outline'
              }}</VIcon>
            </template>
            <div class="text-body-1">
              <template v-if="metrics.insights.available">
                Latest AI insight report is available
                <span
                  v-if="metrics.insights.period"
                  class="text-medium-emphasis"
                >
                  ({{ metrics.insights.period[0] }} →
                  {{ metrics.insights.period[1] }}) </span
                >.
                <Link :href="route('admin.analytics')" class="text-primary ml-1"
                  >View analytics</Link
                >
              </template>
              <template v-else>
                No completed AI insight reports yet. Generate from Analytics
                page.
              </template>
            </div>
          </VAlert>
        </VCol>
      </VRow>
    </div>
  </AppLayout>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.bg-gradient-to-r {
  background: linear-gradient(
    to right,
    var(--v-theme-primary),
    var(--v-theme-blue-600)
  );
}
</style>
