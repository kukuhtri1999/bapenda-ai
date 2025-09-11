<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const stats = ref([
  {
    title: 'Total Taxpayers',
    value: '12,345',
    change: '+12%',
    changeColor: 'green',
    icon: 'mdi-account-group',
    color: 'primary',
  },
  {
    title: 'Active Cases',
    value: '89',
    change: '-5%',
    changeColor: 'red',
    icon: 'mdi-file-document-multiple',
    color: 'orange',
  },
  {
    title: 'AI Conversations',
    value: '1,567',
    change: '+23%',
    changeColor: 'green',
    icon: 'mdi-robot',
    color: 'blue',
  },
  {
    title: 'Revenue Collected',
    value: 'Rp 2.4B',
    change: '+8%',
    changeColor: 'green',
    icon: 'mdi-currency-usd',
    color: 'success',
  },
]);

const recentActivities = ref([
  {
    id: 1,
    type: 'chat',
    title: 'New AI chat session started',
    description: 'Taxpayer ID: WP123456 initiated a consultation',
    time: '5 minutes ago',
    icon: 'mdi-chat',
    color: 'blue',
  },
  {
    id: 2,
    type: 'user',
    title: 'New user registered',
    description: 'John Doe joined the system',
    time: '12 minutes ago',
    icon: 'mdi-account-plus',
    color: 'green',
  },
  {
    id: 3,
    type: 'document',
    title: 'Tax document processed',
    description: 'Document ABC-123 has been reviewed',
    time: '1 hour ago',
    icon: 'mdi-file-check',
    color: 'orange',
  },
  {
    id: 4,
    type: 'payment',
    title: 'Payment received',
    description: 'Rp 125,000,000 from taxpayer WP789012',
    time: '2 hours ago',
    icon: 'mdi-cash',
    color: 'success',
  },
]);

const quickActions = ref([
  {
    title: 'Manage Users',
    description: 'Add, edit, or remove system users',
    icon: 'mdi-account-cog',
    color: 'primary',
    route: 'users.index',
  },
  {
    title: 'View Chat Logs',
    description: 'Monitor AI customer service interactions',
    icon: 'mdi-chat-processing',
    color: 'blue',
    route: 'chat',
  },
  {
    title: 'Import Chat Messages',
    description: 'Unggah riwayat chat dari file XLSX',
    icon: 'mdi-file-excel',
    color: 'green',
    route: 'admin.chat-import',
  },
  {
    title: 'Generate Reports',
    description: 'Create analytical reports and insights',
    icon: 'mdi-chart-line',
    color: 'orange',
    route: 'reports.index',
  },
  {
    title: 'System Settings',
    description: 'Configure application preferences',
    icon: 'mdi-cog',
    color: 'grey',
    route: 'settings.index',
  },
]);

onMounted(() => {
  // Here you can fetch real data from API
  console.log('Dashboard mounted');
});
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="pa-0">
      <!-- Welcome Section -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard
            class="bg-gradient-to-r from-primary to-blue-600 text-white"
            elevation="8"
          >
            <VCardText class="pa-8">
              <VRow align="center">
                <VCol cols="12" md="8">
                  <h1 class="text-h4 font-weight-bold mb-2">
                    Welcome back,
                    {{ $page.props.auth.user.name }}!
                  </h1>
                  <p class="text-h6 font-weight-light mb-4">
                    Here's what's happening with your tax management system
                    today.
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
                  <VIcon size="120" class="opacity-50"
                    >mdi-city-variant</VIcon
                  >
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Statistics Cards -->
      <VRow class="mb-6">
        <VCol v-for="stat in stats" :key="stat.title" cols="12" sm="6" lg="3">
          <VCard elevation="4" class="h-100">
            <VCardText>
              <VRow align="center">
                <VCol cols="8">
                  <p class="text-body-2 text-medium-emphasis mb-1">
                    {{ stat.title }}
                  </p>
                  <h2 class="text-h4 font-weight-bold">
                    {{ stat.value }}
                  </h2>
                  <VChip
                    :color="stat.changeColor"
                    size="small"
                    variant="tonal"
                    class="mt-2"
                  >
                    {{ stat.change }} from last month
                  </VChip>
                </VCol>
                <VCol cols="4" class="text-center">
                  <VAvatar size="60" :color="stat.color" variant="tonal">
                    <VIcon size="30">{{ stat.icon }}</VIcon>
                  </VAvatar>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Content Row -->
      <VRow>
        <!-- Quick Actions -->
        <VCol cols="12" lg="8">
          <VCard elevation="4">
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
                >
                  <VCard
                    variant="outlined"
                    hover
                    class="h-100 cursor-pointer"
                    @click="$inertia.visit(route(action.route))"
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
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Recent Activities -->
        <VCol cols="12" lg="4">
          <VCard elevation="4" class="h-100">
            <VCardTitle class="d-flex align-center">
              <VIcon class="mr-2">mdi-history</VIcon>
              Recent Activities
            </VCardTitle>
            <VCardText class="pa-0">
              <VList>
                <VListItem
                  v-for="activity in recentActivities"
                  :key="activity.id"
                  class="px-4"
                >
                  <template #prepend>
                    <VAvatar size="40" :color="activity.color" variant="tonal">
                      <VIcon>{{ activity.icon }}</VIcon>
                    </VAvatar>
                  </template>
                  <VListItemTitle class="font-weight-medium">
                    {{ activity.title }}
                  </VListItemTitle>
                  <VListItemSubtitle class="mt-1">
                    {{ activity.description }}
                  </VListItemSubtitle>
                  <template #append>
                    <VListItemAction>
                      <small class="text-caption text-medium-emphasis">
                        {{ activity.time }}
                      </small>
                    </VListItemAction>
                  </template>
                </VListItem>
              </VList>
            </VCardText>
            <VCardActions>
              <VBtn variant="text" color="primary" block>
                View All Activities
              </VBtn>
            </VCardActions>
          </VCard>
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
