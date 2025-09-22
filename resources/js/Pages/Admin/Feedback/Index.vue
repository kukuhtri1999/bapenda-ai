<template>
  <Head title="Feedback Management" />
  <AppLayout title="Feedback Management">
    <!-- Header Section -->
    <VRow class="mb-4">
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold text-grey-800 mb-1">
              📝 Feedback Management
            </h1>
            <p class="text-body-2 text-grey-600">
              Monitor and analyze user feedback from SALMA AI chat sessions
            </p>
          </div>
          <VBtn
            @click="exportFeedback"
            :loading="isExporting"
            color="primary"
            variant="outlined"
            prepend-icon="mdi-download"
          >
            Export CSV
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol v-for="(stat, index) in statisticsCards" :key="index" cols="12" sm="6" lg="3">
        <VCard class="pa-4 h-100" :color="stat.color" variant="flat">
          <div class="d-flex align-center">
            <VIcon :color="stat.iconColor" size="40" class="me-3">
              {{ stat.icon }}
            </VIcon>
            <div>
              <div class="text-h5 font-weight-bold" :class="stat.textColor">
                {{ stat.value }}
              </div>
              <div class="text-caption" :class="stat.subtitleColor">
                {{ stat.title }}
              </div>
            </div>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Rating Distribution Chart -->
    <VRow class="mb-6">
      <VCol cols="12" md="6">
        <VCard class="pa-4">
          <VCardTitle class="pb-2">
            <VIcon class="me-2">mdi-chart-bar</VIcon>
            Rating Distribution
          </VCardTitle>
          <div class="rating-chart">
            <div
              v-for="rating in [5, 4, 3, 2, 1]"
              :key="rating"
              class="rating-bar mb-2"
            >
              <div class="d-flex align-center">
                <span class="rating-label me-2">{{ rating }}⭐</span>
                <VProgressLinear
                  :model-value="getRatingPercentage(rating)"
                  :color="getRatingColor(rating)"
                  height="20"
                  class="flex-grow-1 me-2"
                  rounded
                >
                  <template v-slot:default="{ value }">
                    <small class="text-white font-weight-bold">
                      {{ Math.ceil(value) }}%
                    </small>
                  </template>
                </VProgressLinear>
                <span class="text-caption text-grey-600">
                  ({{ stats.rating_distribution[rating] || 0 }})
                </span>
              </div>
            </div>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard class="pa-4 h-100">
          <VCardTitle class="pb-2">
            <VIcon class="me-2">mdi-information</VIcon>
            Recent Activity
          </VCardTitle>
          <VCardText>
            <div class="text-center py-4">
              <VIcon size="48" color="primary" class="mb-2">mdi-chart-timeline-variant</VIcon>
              <div class="text-h6 mb-1">{{ stats.recent_feedbacks }}</div>
              <div class="text-caption text-grey-600">Feedback in last 7 days</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filters -->
    <VRow class="mb-4">
      <VCol cols="12" md="3">
        <VTextField
          v-model="filters.search"
          placeholder="Search feedback, session ID..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="debouncedSearch"
        />
      </VCol>
      <VCol cols="12" md="2">
        <VSelect
          v-model="filters.rating"
          :items="ratingOptions"
          placeholder="All Ratings"
          variant="outlined"
          density="compact"
          clearable
        />
      </VCol>
      <VCol cols="12" md="2">
        <VTextField
          v-model="filters.date_from"
          type="date"
          label="From Date"
          variant="outlined"
          density="compact"
        />
      </VCol>
      <VCol cols="12" md="2">
        <VTextField
          v-model="filters.date_to"
          type="date"
          label="To Date"
          variant="outlined"
          density="compact"
        />
      </VCol>
      <VCol cols="12" md="3">
        <div class="d-flex gap-2">
          <VBtn @click="applyFilters" color="primary" variant="flat">
            <VIcon left>mdi-filter</VIcon>
            Apply Filters
          </VBtn>
          <VBtn @click="clearFilters" variant="outlined">
            <VIcon left>mdi-filter-off</VIcon>
            Clear
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Feedback Table -->
    <VCard>
      <VCardTitle class="pa-4 pb-2">
        <div class="d-flex justify-space-between align-center w-100">
          <div class="d-flex align-center">
            <VIcon class="me-2">mdi-format-list-bulleted</VIcon>
            Feedback List
          </div>
          <VChip color="primary" variant="flat" size="small">
            {{ feedbacks.total }} Total
          </VChip>
        </div>
      </VCardTitle>

      <VDataTable
        :headers="headers"
        :items="feedbacks.data"
        :loading="loading"
        item-key="id"
        class="elevation-0"
        :items-per-page="-1"
        hide-default-footer
      >
        <!-- Session ID Column -->
        <template v-slot:item.session_id="{ item }">
          <VChip color="blue-grey" variant="outlined" size="small">
            {{ item.session_id.substring(0, 12) }}...
          </VChip>
        </template>

        <!-- Rating Column -->
        <template v-slot:item.rating="{ item }">
          <div class="d-flex align-center">
            <div class="rating-display">
              <span v-for="i in 5" :key="i" class="star">
                {{ i <= item.rating ? '⭐' : '⭐' }}
              </span>
            </div>
            <VChip
              :color="getRatingColor(item.rating)"
              variant="flat"
              size="small"
              class="ms-2"
            >
              {{ item.rating }}/5
            </VChip>
          </div>
        </template>

        <!-- Feedback Text Column -->
        <template v-slot:item.feedback_text="{ item }">
          <div v-if="item.feedback_text" class="feedback-text">
            {{ item.feedback_text.length > 100 
                ? item.feedback_text.substring(0, 100) + '...' 
                : item.feedback_text 
            }}
          </div>
          <VChip v-else color="grey" variant="outlined" size="small">
            No text provided
          </VChip>
        </template>

        <!-- Created At Column -->
        <template v-slot:item.created_at="{ item }">
          <div class="text-caption">
            {{ formatDate(item.created_at) }}
          </div>
        </template>

        <!-- Actions Column -->
        <template v-slot:item.actions="{ item }">
          <VBtn
            @click="viewDetail(item.id)"
            color="primary"
            variant="text"
            size="small"
            icon
          >
            <VIcon>mdi-eye</VIcon>
          </VBtn>
        </template>
      </VDataTable>

      <!-- Pagination -->
      <VDivider />
      <div class="pa-4">
        <div class="d-flex justify-space-between align-center">
          <div class="text-caption text-grey-600">
            Showing {{ feedbacks.from || 0 }} to {{ feedbacks.to || 0 }} of {{ feedbacks.total }} entries
          </div>
          <VPagination
            v-if="feedbacks.last_page > 1"
            v-model="currentPage"
            :length="feedbacks.last_page"
            @update:model-value="changePage"
            total-visible="7"
            size="small"
          />
        </div>
      </div>
    </VCard>

    <!-- Loading Overlay -->
    <VOverlay v-model="loading" class="align-center justify-center">
      <VProgressCircular color="primary" indeterminate size="64" />
    </VOverlay>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

// Props
const props = defineProps({
  feedbacks: Object,
  stats: Object,
  filters: Object,
});

// Reactive data
const loading = ref(false);
const isExporting = ref(false);
const currentPage = ref(props.feedbacks.current_page || 1);

const filters = ref({
  search: props.filters.search || '',
  rating: props.filters.rating || null,
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  sort_by: props.filters.sort_by || 'created_at',
  sort_order: props.filters.sort_order || 'desc',
});

// Table headers
const headers = ref([
  { title: 'Session ID', key: 'session_id', sortable: true },
  { title: 'Rating', key: 'rating', sortable: true },
  { title: 'Feedback Text', key: 'feedback_text', sortable: false },
  { title: 'Date', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]);

// Rating options for filter
const ratingOptions = ref([
  { title: '⭐ (1 Star)', value: 1 },
  { title: '⭐⭐ (2 Stars)', value: 2 },
  { title: '⭐⭐⭐ (3 Stars)', value: 3 },
  { title: '⭐⭐⭐⭐ (4 Stars)', value: 4 },
  { title: '⭐⭐⭐⭐⭐ (5 Stars)', value: 5 },
]);

// Computed statistics cards
const statisticsCards = computed(() => [
  {
    title: 'Total Feedback',
    value: props.stats.total_feedbacks,
    icon: 'mdi-message-text',
    color: 'blue-lighten-5',
    iconColor: 'blue',
    textColor: 'text-blue',
    subtitleColor: 'text-blue-darken-2',
  },
  {
    title: 'Average Rating',
    value: props.stats.average_rating + '/5',
    icon: 'mdi-star',
    color: 'amber-lighten-5',
    iconColor: 'amber',
    textColor: 'text-amber-darken-2',
    subtitleColor: 'text-amber-darken-3',
  },
  {
    title: 'Recent Feedback',
    value: props.stats.recent_feedbacks,
    icon: 'mdi-clock-outline',
    color: 'green-lighten-5',
    iconColor: 'green',
    textColor: 'text-green',
    subtitleColor: 'text-green-darken-2',
  },
  {
    title: 'Satisfaction Rate',
    value: getSatisfactionRate() + '%',
    icon: 'mdi-emoticon-happy',
    color: 'purple-lighten-5',
    iconColor: 'purple',
    textColor: 'text-purple',
    subtitleColor: 'text-purple-darken-2',
  },
]);

// Methods
const getRatingPercentage = (rating) => {
  const total = props.stats.total_feedbacks;
  if (total === 0) return 0;
  const count = props.stats.rating_distribution[rating] || 0;
  return (count / total) * 100;
};

const getRatingColor = (rating) => {
  const colors = {
    1: 'red',
    2: 'orange',
    3: 'yellow',
    4: 'light-green',
    5: 'green',
  };
  return colors[rating] || 'grey';
};

const getSatisfactionRate = () => {
  const total = props.stats.total_feedbacks;
  if (total === 0) return 0;
  const satisfied = (props.stats.rating_distribution[4] || 0) + (props.stats.rating_distribution[5] || 0);
  return Math.round((satisfied / total) * 100);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const applyFilters = () => {
  loading.value = true;
  router.get('/admin/feedback', filters.value, {
    preserveState: true,
    onFinish: () => {
      loading.value = false;
    },
  });
};

const clearFilters = () => {
  filters.value = {
    search: '',
    rating: null,
    date_from: '',
    date_to: '',
    sort_by: 'created_at',
    sort_order: 'desc',
  };
  applyFilters();
};

const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

const changePage = (page) => {
  currentPage.value = page;
  router.get('/admin/feedback', { ...filters.value, page }, {
    preserveState: true,
  });
};

const viewDetail = (feedbackId) => {
  router.get(`/admin/feedback/${feedbackId}`);
};

const exportFeedback = async () => {
  isExporting.value = true;
  try {
    window.open(`/admin/feedback/export/csv?${new URLSearchParams(filters.value).toString()}`);
  } catch (error) {
    console.error('Export failed:', error);
  } finally {
    isExporting.value = false;
  }
};

onMounted(() => {
  // Any initialization logic
});
</script>

<style scoped>
.rating-chart {
  max-width: 100%;
}

.rating-label {
  min-width: 40px;
  font-size: 12px;
}

.rating-display .star {
  font-size: 16px;
  color: #ffc107;
}

.rating-display .star:nth-child(n+6) {
  color: #e0e0e0;
}

.feedback-text {
  max-width: 300px;
  word-wrap: break-word;
  line-height: 1.4;
}

.v-data-table >>> .v-data-table__wrapper {
  border-radius: 0;
}

.v-card {
  border-radius: 12px !important;
}

.v-chip {
  font-size: 11px;
}
</style>