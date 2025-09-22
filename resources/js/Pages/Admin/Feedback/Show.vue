<template>
  <Head title="Feedback Detail" />
  <AppLayout title="Feedback Detail">
    <!-- Header with Back Button -->
    <VRow class="mb-4">
      <VCol cols="12">
        <div class="d-flex align-center mb-3">
          <VBtn
            @click="goBack"
            icon
            variant="text"
            class="me-3"
          >
            <VIcon>mdi-arrow-left</VIcon>
          </VBtn>
          <div>
            <h1 class="text-h4 font-weight-bold text-grey-800 mb-1">
              📋 Feedback Detail
            </h1>
            <p class="text-body-2 text-grey-600 mb-0">
              Session ID: {{ feedback.session_id }}
            </p>
          </div>
        </div>
      </VCol>
    </VRow>

    <VRow>
      <!-- Feedback Information -->
      <VCol cols="12" md="8">
        <VCard class="mb-4">
          <VCardTitle class="pa-4 pb-2">
            <VIcon class="me-2">mdi-star</VIcon>
            Rating & Feedback
          </VCardTitle>
          <VCardText class="pa-4">
            <!-- Rating Display -->
            <div class="mb-4">
              <div class="text-subtitle-1 font-weight-medium mb-2">Rating</div>
              <div class="d-flex align-center">
                <div class="rating-stars me-3">
                  <span v-for="i in 5" :key="i" class="star" :class="{ active: i <= feedback.rating }">
                    ⭐
                  </span>
                </div>
                <VChip
                  :color="getRatingColor(feedback.rating)"
                  variant="flat"
                  size="large"
                >
                  {{ feedback.rating }}/5 - {{ getRatingLabel(feedback.rating) }}
                </VChip>
              </div>
            </div>

            <!-- Feedback Text -->
            <div class="mb-4">
              <div class="text-subtitle-1 font-weight-medium mb-2">Feedback Text</div>
              <VCard
                v-if="feedback.feedback_text"
                class="pa-3"
                color="grey-lighten-5"
                variant="flat"
              >
                <div class="text-body-1" style="line-height: 1.6; white-space: pre-wrap;">
                  {{ feedback.feedback_text }}
                </div>
              </VCard>
              <VAlert
                v-else
                type="info"
                variant="outlined"
                class="ma-0"
              >
                No feedback text provided by the user.
              </VAlert>
            </div>

            <!-- Timestamp -->
            <div>
              <div class="text-subtitle-1 font-weight-medium mb-2">Submitted At</div>
              <div class="d-flex align-center">
                <VIcon class="me-2" color="grey">mdi-clock-outline</VIcon>
                <span class="text-body-1">{{ formatDateTime(feedback.created_at) }}</span>
              </div>
            </div>
          </VCardText>
        </VCard>

        <!-- Chat Session Info (if available) -->
        <VCard v-if="feedback.chat_session">
          <VCardTitle class="pa-4 pb-2">
            <VIcon class="me-2">mdi-chat</VIcon>
            Chat Session Information
          </VCardTitle>
          <VCardText class="pa-4">
            <VRow>
              <VCol cols="12" sm="6">
                <div class="mb-3">
                  <div class="text-caption text-grey-600 mb-1">Session Started</div>
                  <div class="text-body-2">
                    {{ formatDateTime(feedback.chat_session.created_at) }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="mb-3">
                  <div class="text-caption text-grey-600 mb-1">Total Messages</div>
                  <div class="text-body-2">
                    {{ feedback.chat_session.messages ? feedback.chat_session.messages.length : 'N/A' }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="mb-3">
                  <div class="text-caption text-grey-600 mb-1">Session Duration</div>
                  <div class="text-body-2">
                    {{ calculateSessionDuration() }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="mb-3">
                  <div class="text-caption text-grey-600 mb-1">Session Status</div>
                  <VChip
                    :color="feedback.chat_session.ended_at ? 'red' : 'green'"
                    variant="flat"
                    size="small"
                  >
                    {{ feedback.chat_session.ended_at ? 'Ended' : 'Active' }}
                  </VChip>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Quick Stats Sidebar -->
      <VCol cols="12" md="4">
        <VCard class="mb-4">
          <VCardTitle class="pa-4 pb-2">
            <VIcon class="me-2">mdi-information</VIcon>
            Quick Info
          </VCardTitle>
          <VCardText class="pa-4">
            <div class="mb-3">
              <div class="text-caption text-grey-600 mb-1">Session ID</div>
              <VTextField
                :model-value="feedback.session_id"
                readonly
                variant="outlined"
                density="compact"
                append-inner-icon="mdi-content-copy"
                @click:append-inner="copyToClipboard(feedback.session_id)"
              />
            </div>
            <div class="mb-3">
              <div class="text-caption text-grey-600 mb-1">Feedback ID</div>
              <VTextField
                :model-value="feedback.id.toString()"
                readonly
                variant="outlined"
                density="compact"
              />
            </div>
            <div class="mb-3">
              <div class="text-caption text-grey-600 mb-1">Character Count</div>
              <div class="text-body-2">
                {{ feedback.feedback_text ? feedback.feedback_text.length : 0 }} characters
              </div>
            </div>
          </VCardText>
        </VCard>

        <!-- Actions Card -->
        <VCard>
          <VCardTitle class="pa-4 pb-2">
            <VIcon class="me-2">mdi-cog</VIcon>
            Actions
          </VCardTitle>
          <VCardText class="pa-4">
            <VBtn
              @click="exportSingleFeedback"
              block
              variant="outlined"
              color="primary"
              class="mb-3"
              prepend-icon="mdi-download"
            >
              Export This Feedback
            </VBtn>
            <VBtn
              @click="goBack"
              block
              variant="outlined"
              prepend-icon="mdi-arrow-left"
            >
              Back to List
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Success Snackbar -->
    <VSnackbar
      v-model="showCopySuccess"
      color="success"
      location="top"
      timeout="3000"
    >
      Session ID copied to clipboard!
      <template v-slot:actions>
        <VBtn color="white" variant="text" @click="showCopySuccess = false">
          Close
        </VBtn>
      </template>
    </VSnackbar>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  feedback: Object,
});

// Reactive data
const showCopySuccess = ref(false);

// Methods
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

const getRatingLabel = (rating) => {
  const labels = {
    1: 'Sangat Buruk',
    2: 'Buruk',
    3: 'Cukup',
    4: 'Baik',
    5: 'Sangat Baik',
  };
  return labels[rating] || 'Unknown';
};

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

const calculateSessionDuration = () => {
  if (!props.feedback.chat_session) return 'N/A';
  
  const start = new Date(props.feedback.chat_session.created_at);
  const end = props.feedback.chat_session.ended_at 
    ? new Date(props.feedback.chat_session.ended_at)
    : new Date(props.feedback.created_at);
  
  const diffMs = end - start;
  const diffMins = Math.floor(diffMs / 60000);
  const diffSecs = Math.floor((diffMs % 60000) / 1000);
  
  if (diffMins > 0) {
    return `${diffMins} min ${diffSecs} sec`;
  } else {
    return `${diffSecs} seconds`;
  }
};

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    showCopySuccess.value = true;
  } catch (err) {
    console.error('Failed to copy text: ', err);
  }
};

const exportSingleFeedback = () => {
  // Create CSV content for single feedback
  const csvContent = [
    ['Field', 'Value'],
    ['Session ID', props.feedback.session_id],
    ['Rating', props.feedback.rating],
    ['Feedback Text', props.feedback.feedback_text || 'No text provided'],
    ['Submitted At', formatDateTime(props.feedback.created_at)],
    ['Session Started', props.feedback.chat_session ? formatDateTime(props.feedback.chat_session.created_at) : 'N/A'],
    ['Session Duration', calculateSessionDuration()],
  ];

  const csvString = csvContent.map(row => 
    row.map(field => `"${field}"`).join(',')
  ).join('\n');

  const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `feedback_${props.feedback.id}_${Date.now()}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const goBack = () => {
  router.get('/admin/feedback');
};
</script>

<style scoped>
.rating-stars .star {
  font-size: 24px;
  color: #e0e0e0;
  margin-right: 2px;
}

.rating-stars .star.active {
  color: #ffc107;
}

.v-card {
  border-radius: 12px !important;
}

.v-alert {
  border-radius: 8px !important;
}
</style>