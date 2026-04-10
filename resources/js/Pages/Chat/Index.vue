<template>
  <VApp>
    <Head title="Mulai Chat"></Head>

    <!-- ── App Bar at VApp level so VMain auto-offsets content ── -->
    <VAppBar
      density="comfortable"
      id="tour-chat-header" class="chat-header"
      :style="{
        background:
          'linear-gradient(135deg, #A855F7 0%, #9333EA 50%, #7C3AED 100%)',
        boxShadow: '0 4px 16px rgba(147, 51, 234, 0.35)',
      }"
    >
      <VBtn
        icon="mdi-arrow-left"
        variant="text"
        color="white"
        @click="goToHome"
        class="me-2"
      ></VBtn>
      <VAppBarTitle class="text-white">
        <div class="d-flex align-center gap-2">
          <!-- Salma mini avatar in header -->
          <div class="salma-header-avatar">
            <img
              src="/images/salma2.gif"
              alt="SALMA"
              loading="lazy"
              class="salma-header-img"
            />
          </div>
          <div>
            <div class="font-weight-bold">
              SALMA AI — Asisten Samsat Lamongan
            </div>
            <div v-if="props.wajibPajakData" class="text-caption opacity-90">
              {{ props.wajibPajakData.nama }} ({{ props.wajibPajakData.nopol }})
            </div>
          </div>
        </div>
      </VAppBarTitle>
      <VSpacer></VSpacer>
      <VBtn
        icon="mdi-refresh"
        variant="text"
        color="white"
        @click="startNewChat"
        title="Chat Baru"
        class="me-2"
      ></VBtn>
    </VAppBar>

    <!-- ── VMain auto-applies top padding = AppBar height ── -->
    <VMain class="chat-main">
      <!-- Chat Container -->
      <VCard
        class="chat-container mx-auto"
        max-width="1000"
        :style="{
          borderRadius: '0',
          overflow: 'hidden',
          background: 'white',
          border: 'none',
          display: 'flex',
          flexDirection: 'column',
          flex: '1 1 auto',
          minHeight: '0',
        }"
        elevation="0"
      >
        <!-- Welcome Section -->
        <div
          v-if="!chatSession || messages.length === 0"
          id="tour-chat-welcome" class="welcome-section pa-6 text-center"
          :style="{
            background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)',
            height: '100%',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
          }"
        >
          <!-- SALMA Mascot GIF - welcome screen hero -->
          <div class="salma-mascot-wrapper mb-3">
            <img
              src="/images/salma2.gif"
              alt="SALMA AI Assistant"
              loading="eager"
              class="salma-mascot-img"
            />
          </div>

          <h2 class="text-h5 mb-3 gradient-text font-weight-bold">
            Selamat Datang di Layanan AI
          </h2>
          <h3 class="text-h6 mb-3 text-primary">Samsat Lamongan</h3>
          <p
            class="text-body-2 text-grey-700 mb-4 mx-auto"
            style="max-width: 450px; line-height: 1.6"
          >
            Saya siap membantu Anda dengan informasi seputar pajak kendaraan,
            STNK, dan layanan Samsat lainnya 24/7.
          </p>

          <!-- Quick Suggestions -->
          <div class="mb-4">
            <h4 class="text-subtitle-1 mb-3 text-grey-800">
              Pertanyaan Populer:
            </h4>
            <VRow justify="center" class="ma-0" dense>
              <VCol
                v-for="(suggestion, index) in quickSuggestions"
                :key="suggestion"
                cols="12"
                sm="6"
                lg="3"
                class="pa-1"
              >
                <VCard
                  @click="sendQuickMessage(suggestion)"
                  class="suggestion-card pa-3 text-center"
                  :style="{
                    cursor: 'pointer',
                    background:
                      'linear-gradient(135deg, ' +
                      getSuggestionColor(index) +
                      '15, ' +
                      getSuggestionColor(index) +
                      '08)',
                    border: '1.5px solid ' + getSuggestionColor(index) + '30',
                    borderRadius: '16px',
                    transition: 'all 0.3s ease',
                    minHeight: '80px',
                  }"
                  hover
                  elevation="1"
                >
                  <VIcon
                    :color="getSuggestionColor(index)"
                    size="24"
                    class="mb-1"
                  >
                    {{ getSuggestionIcon(index) }}
                  </VIcon>
                  <p
                    class="text-caption font-weight-medium mb-0"
                    :style="{
                      color: getSuggestionColor(index),
                      fontSize: '11px',
                      lineHeight: '1.3',
                    }"
                  >
                    {{ suggestion }}
                  </p>
                </VCard>
              </VCol>
            </VRow>
          </div>

          <VBtn
            @click="startNewChat"
            :style="{
              background: 'linear-gradient(135deg, #9B59B6, #7D3C98)',
              borderRadius: '20px',
              textTransform: 'none',
              padding: '10px 28px',
              boxShadow: '0 6px 16px rgba(125, 60, 152, 0.4)',
            }"
            color="white"
            class="start-chat-btn text-white font-weight-bold"
            size="large"
            elevation="0"
          >
            <VIcon left size="20">mdi-chat</VIcon>
            Mulai Chat
          </VBtn>
        </div>

        <!-- Messages Area -->
        <div v-else id="tour-chat-messages" class="chat-messages-area">
          <!-- Messages Container -->
          <div
            ref="messagesContainer"
            class="messages-scroll pa-3"
            :style="{
              flex: '1 1 auto',
              minHeight: '0',
              overflowY: 'auto',
              background: 'linear-gradient(to bottom, #fafafa, #ffffff)',
            }"
          >
            <!-- Message Items -->
            <div
              v-for="message in messages"
              :key="message.id || message.sent_at"
              class="mb-3"
            >
              <!-- User Message -->
              <VRow
                v-if="message.role === 'user'"
                justify="end"
                no-gutters
                class="mb-2"
              >
                <VCol cols="auto" class="max-width-75">
                  <VCard
                    class="user-message pa-3"
                    :style="{
                      background: 'linear-gradient(135deg, #9B59B6, #7D3C98)',
                      borderRadius: '18px 18px 4px 18px',
                      boxShadow: '0 3px 10px rgba(125, 60, 152, 0.35)',
                      maxWidth: '100%',
                    }"
                    elevation="0"
                  >
                    <div class="text-white font-weight-medium text-body-2">
                      {{ message.content }}
                    </div>
                    <div class="text-right mt-1">
                      <small
                        class="text-white"
                        style="opacity: 0.8; font-size: 10px"
                      >
                        {{ formatTime(message.sent_at) }}
                      </small>
                    </div>
                  </VCard>
                </VCol>
              </VRow>

              <!-- Assistant Message -->
              <VRow v-else justify="start" no-gutters class="mb-2">
                <VCol cols="auto" class="max-width-160">
                  <div class="d-flex align-start">
                    <!-- Salma avatar - lazy loaded for off-screen messages -->
                    <div class="salma-msg-avatar me-2 mt-1 flex-shrink-0">
                      <img
                        src="/images/salma2.gif"
                        alt="SALMA"
                        loading="lazy"
                        class="salma-msg-img"
                      />
                    </div>
                    <VCard
                      class="assistant-message pa-3 flex-grow-1"
                      :style="{
                        background: 'white',
                        borderRadius: '18px 18px 18px 4px',
                        border: '1px solid #e0e0e0',
                        boxShadow: '0 2px 8px rgba(0,0,0,0.06)',
                        maxWidth: '100%',
                      }"
                      elevation="0"
                    >
                      <!-- Feedback Form Component -->
                      <!-- (Feedback is now shown as a VDialog popup) -->

                      <!-- Final Thank You or Regular Assistant Message -->
                      <div
                        class="assistant-content text-grey-800 text-body-2"
                        style="line-height: 1.6"
                        v-html="getFormattedContent(message)"
                      ></div>

                      <div class="text-left mt-1">
                        <small class="text-grey-500" style="font-size: 10px">
                          {{ formatTime(message.sent_at) }}
                        </small>
                      </div>
                    </VCard>
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Typing Indicator -->
            <VRow v-if="isTyping" justify="start" no-gutters>
              <VCol cols="auto">
                <div class="d-flex align-start">
                  <div class="salma-msg-avatar me-2">
                    <img
                      src="/images/salma2.gif"
                      alt="SALMA"
                      loading="lazy"
                      class="salma-msg-img"
                    />
                  </div>
                  <VCard
                    class="pa-3"
                    :style="{
                      background: 'white',
                      borderRadius: '18px 18px 18px 4px',
                      border: '1px solid #e0e0e0',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.06)',
                    }"
                    elevation="0"
                  >
                    <div class="typing-indicator">
                      <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                      </div>
                      <span class="typing-text ms-2 text-grey-600 text-caption">
                        sedang mengetik...
                      </span>
                    </div>
                  </VCard>
                </div>
              </VCol>
            </VRow>
          </div>

          <!-- Input Area -->
          <VDivider style="border-color: rgba(0, 0, 0, 0.05)"></VDivider>
          <div
            class="pa-3"
            style="background: white; border-radius: 0 0 24px 24px"
          >
            <VRow no-gutters align="center" class="gap-2">
              <VCol>
                <VTextarea
                  v-model="currentMessage"
                  placeholder="Ketik pertanyaan Anda tentang layanan Samsat..."
                  rows="1"
                  auto-grow
                  max-rows="3"
                  variant="outlined"
                  id="tour-chat-input" class="message-input"
                  :disabled="isLoading"
                  @keydown.enter="handleEnterKey"
                  hide-details
                  density="compact"
                  :style="{
                    borderRadius: '20px',
                  }"
                ></VTextarea>
              </VCol>
              <VCol cols="auto">
                <VBtn
                  @click="() => sendMessage()"
                  :disabled="!currentMessage.trim() || isLoading"
                  :style="{
                    background: 'linear-gradient(135deg, #9B59B6, #7D3C98)',
                    borderRadius: '50%',
                    minWidth: '48px',
                    width: '48px',
                    height: '48px',
                    boxShadow: '0 4px 12px rgba(125, 60, 152, 0.35)',
                  }"
                  class="text-white"
                  elevation="0"
                  icon
                >
                  <VIcon v-if="!isLoading" size="20">mdi-send</VIcon>
                  <VProgressCircular
                    v-else
                    indeterminate
                    size="16"
                    color="white"
                  ></VProgressCircular>
                </VBtn>
              </VCol>
            </VRow>
          </div>
        </div>
      </VCard>
    </VMain>

    <!-- ─── Feedback Dialog Popup ──────────────────────────────────────── -->
    <VDialog
      v-model="showFeedbackDialog"
      max-width="460"
      persistent
      :scrim="'rgba(0,0,0,0.55)'"
    >
      <VCard class="feedback-popup rounded-xl pa-1" elevation="24">
        <!-- Header -->
        <div
          class="feedback-popup-header text-center pa-5 pb-3"
          :style="{
            background: 'linear-gradient(135deg, #A855F7 0%, #9333EA 100%)',
            borderRadius: '12px 12px 0 0',
          }"
        >
          <div class="feedback-emoji-large mb-2">
            {{ idleAutoTriggered ? '⏰' : '💬' }}
          </div>
          <h2 class="text-white font-weight-bold text-h6">
            {{
              idleAutoTriggered
                ? 'Sesi Berakhir Otomatis'
                : 'Bagaimana Layanan Kami?'
            }}
          </h2>
          <p class="text-white text-caption mt-1" style="opacity: 0.85">
            {{
              idleAutoTriggered
                ? 'Anda tidak aktif selama 5 menit. Sesi telah diakhiri.'
                : 'Terima kasih telah menggunakan SALMA AI'
            }}
          </p>
        </div>

        <VCardText class="pa-5">
          <!-- Rating section -->
          <div class="text-center mb-4">
            <p class="text-subtitle-2 font-weight-semibold text-grey-800 mb-3">
              Berikan Penilaian Anda
            </p>
            <VRating
              v-model="feedbackRating"
              :size="40"
              color="amber-darken-1"
              active-color="amber-darken-1"
              hover
              :density="'comfortable'"
              @update:model-value="onRatingChange"
            />
            <div
              class="rating-label mt-2 text-body-2 font-weight-medium"
              :style="{ color: feedbackRating > 0 ? '#9333EA' : '#9e9e9e' }"
            >
              {{ getRatingLabel(feedbackRating) }}
            </div>
          </div>

          <VDivider class="mb-4" />

          <!-- Feedback text -->
          <VTextarea
            v-model="feedbackText"
            label="Saran atau Komentar (opsional)"
            placeholder="Ceritakan pengalaman Anda menggunakan SALMA AI..."
            rows="3"
            variant="outlined"
            density="compact"
            no-resize
            :color="'deep-purple'"
            hide-details
          />
        </VCardText>

        <VCardActions class="px-5 pb-5 pt-0 d-flex gap-3">
          <VBtn
            variant="text"
            :disabled="isSubmittingFeedback"
            @click="
              showFeedbackDialog = false;
              feedbackRating = 0;
              feedbackText = '';
            "
            class="text-grey flex-grow-1"
          >
            Lewati
          </VBtn>
          <VBtn
            :disabled="feedbackRating === 0 || isSubmittingFeedback"
            :loading="isSubmittingFeedback"
            @click="submitFeedback"
            variant="flat"
            class="flex-grow-1 text-white"
            :style="{
              background:
                feedbackRating > 0
                  ? 'linear-gradient(135deg, #9B59B6, #7D3C98)'
                  : undefined,
              borderRadius: '10px',
            }"
            prepend-icon="mdi-send"
          >
            Kirim Feedback
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Lightbox -->
    <VueEasyLightbox
      :visible="lightboxVisible"
      :imgs="lightboxImages"
      :index="lightboxIndex"
      @hide="lightboxVisible = false"
    />

    <!-- Error Snackbar -->
    <VSnackbar v-model="showError" color="error" location="top" timeout="5000">
      {{ errorMessage }}
      <template v-slot:actions>
        <VBtn color="white" variant="text" @click="showError = false">
          Tutup
        </VBtn>
      </template>
    </VSnackbar>
    <TourButton @start="startChatTour" variant="public" />
  </VApp>
</template>

<script setup>
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';
import {
  ref, onMounted, onBeforeUnmount, nextTick, watch,
} from 'vue';
import { router, Head } from '@inertiajs/vue3';
import axios from 'axios';
import VueEasyLightbox from 'vue-easy-lightbox';

// Props for user data from session
const props = defineProps({
  wajibPajakData: {
    type: Object,
    default: null,
  },
});

// Reactive data
const chatSession = ref(null);
const messages = ref([]);
const currentMessage = ref('');
const isLoading = ref(false);
const isTyping = ref(false);
const showError = ref(false);
const errorMessage = ref('');
const messagesContainer = ref(null);
const lightboxVisible = ref(false);
const lightboxImages = ref([]);
const lightboxIndex = ref(0);
const formattedMessages = ref(new Map()); // Store formatted content by message ID

// Chat ending and feedback
const showFollowUp = ref(false);
const showFeedbackForm = ref(false);
const showFinalMessage = ref(false);
const feedbackRating = ref(0);
const feedbackText = ref('');
const isSubmittingFeedback = ref(false);
const feedbackSubmitted = ref(false);

// Feedback dialog (popup) + idle auto-end
const showFeedbackDialog = ref(false);
const idleAutoTriggered = ref(false);
let idleTimer = null;
const IDLE_TIMEOUT = 5 * 60 * 1000; // 5 minutes

// Quick suggestions
const quickSuggestions = ref([
  'Bagaimana cara bayar pajak kendaraan?',
  'Syarat perpanjangan STNK',
  'Lokasi dan jam operasional Samsat',
  'Cara cek pajak kendaraan online',
]);

// Session management
const generateSessionId = () => {
  const timestamp = Date.now();
  const random = Math.random().toString(36).substr(2, 9);
  return `sess_${timestamp}_${random}`;
};

const getSessionId = (forceNew = false) => {
  if (forceNew) {
    const newSessionId = generateSessionId();
    localStorage.setItem('chat_session_id', newSessionId);
    return newSessionId;
  }

  let sessionId = localStorage.getItem('chat_session_id');
  if (!sessionId) {
    sessionId = generateSessionId();
    localStorage.setItem('chat_session_id', sessionId);
  }
  return sessionId;
};

// ── Idle timer ────────────────────────────────────────────────────────
const resetIdleTimer = () => {
  clearTimeout(idleTimer);
  if (feedbackSubmitted.value || showFeedbackDialog.value) return;
  if (!chatSession.value && messages.value.length === 0) return;
  idleTimer = setTimeout(() => {
    if (!showFeedbackDialog.value && !feedbackSubmitted.value) {
      idleAutoTriggered.value = true;
      showFeedbackDialog.value = true;
    }
  }, IDLE_TIMEOUT);
};

// Initialize chat
const initializeChat = async () => {
  try {
    // Reset all feedback states
    showFollowUp.value = false;
    showFeedbackForm.value = false;
    showFinalMessage.value = false;
    feedbackRating.value = 0;
    feedbackText.value = '';
    isSubmittingFeedback.value = false;
    feedbackSubmitted.value = false;

    const response = await axios.post('/api/chat/start', {
      session_id: getSessionId(),
    });

    if (response.data.success) {
      chatSession.value = response.data.chat;
      messages.value = response.data.chat.messages || [];
      await scrollToBottom();
      resetIdleTimer();
    }
  } catch (error) {
    console.error('Error initializing chat:', error);
    showErrorMessage('Gagal memulai chat. Silakan refresh halaman.');
  }
};

// Start new chat
const startNewChat = async () => {
  try {
    messages.value = [];
    chatSession.value = null;

    // Reset all feedback states
    showFollowUp.value = false;
    showFeedbackForm.value = false;
    showFinalMessage.value = false;
    feedbackRating.value = 0;
    feedbackText.value = '';
    isSubmittingFeedback.value = false;
    feedbackSubmitted.value = false;

    const sessionId = getSessionId(true); // Force new session

    const response = await axios.post('/api/chat/start', {
      session_id: sessionId,
    });

    if (response.data.success) {
      chatSession.value = response.data.chat;
      messages.value = response.data.chat.messages || [];
      await scrollToBottom();
      resetIdleTimer();
    }
  } catch (error) {
    console.error('Error starting new chat:', error);
    showErrorMessage('Gagal memulai chat baru. Silakan coba lagi.');
  }
};

// Send message
const sendMessage = async (messageText = null, isContext = false) => {
  const text = messageText || currentMessage.value.trim();

  if (!text || isLoading.value) return;

  if (!messageText) {
    currentMessage.value = '';
  }

  // Initialize chat if needed
  if (!chatSession.value) {
    await initializeChat();
  }

  // Reset idle timer on activity
  resetIdleTimer();

  // Add user message to UI immediately (skip for context messages)
  if (!isContext) {
    const userMessage = {
      role: 'user',
      content: text,
      sent_at: new Date().toISOString(),
      id: `user_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
    };
    messages.value.push(userMessage);
    await scrollToBottom();
  }

  try {
    isLoading.value = true;
    isTyping.value = true;

    const response = await axios.post('/api/chat/message', {
      session_id: getSessionId(),
      message: text,
      is_context: isContext,
    });

    isTyping.value = false;

    if (response.data.success) {
      // Only show AI response if it's not a context message
      if (!isContext) {
        const assistantMessage = {
          ...response.data.assistant_message,
          id: `assistant_${Date.now()}_${Math.random()
            .toString(36)
            .substr(2, 9)}`,
        };
        messages.value.push(assistantMessage);
        await scrollToBottom();
        showFollowUpMessage();
      }
    } else {
      showErrorMessage(response.data.message || 'Gagal mengirim pesan');
    }
  } catch (error) {
    isTyping.value = false;
    console.error('Error sending message:', error);
    showErrorMessage('Gagal mengirim pesan. Silakan coba lagi.');
  } finally {
    isLoading.value = false;
  }
};

// Send quick message
const sendQuickMessage = async (message) => {
  currentMessage.value = message;
  await sendMessage();
};

// Handle enter key
const handleEnterKey = (event) => {
  if (!event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
};

// Go to home
const goToHome = () => {
  router.visit('/');
};

// Follow-up and feedback functions
const lastFollowUpAt = ref(0);
const showFollowUpMessage = async () => {
  try {
    const now = Date.now();
    const lastMsg = messages.value[messages.value.length - 1];
    if (!lastMsg || lastMsg.role !== 'assistant') return;
    // Avoid duplicating follow-ups within 2 seconds window
    if (now - lastFollowUpAt.value < 2000) return;

    const followUpMessage = {
      id: `followup_${now}`,
      role: 'assistant',
      content:
        'Ada lagi yang bisa SALMA bantu? <button data-action="end-chat" style="display:inline-flex;align-items:center;gap:5px;margin-left:10px;padding:6px 14px;border-radius:20px;border:1.5px solid #9333EA;background:transparent;color:#9333EA;cursor:pointer;font-size:12px;font-weight:600;transition:all 0.2s;">⛔ Akhiri Chat</button>',
      sent_at: new Date().toISOString(),
      type: 'follow_up',
    };
    messages.value.push(followUpMessage);
    lastFollowUpAt.value = now;
    await scrollToBottom();
  } catch (error) {
    console.error('Error in showFollowUpMessage:', error);
  }
};

// Make endChatFromMessage available globally
window.endChatFromMessage = () => {
  endChat();
};

const endChat = () => {
  clearTimeout(idleTimer);
  idleAutoTriggered.value = false;
  showFeedbackDialog.value = true;
};

const showFeedbackMessage = async () => {
  const feedbackMessage = {
    id: `feedback_${Date.now()}`,
    role: 'assistant',
    content: 'Feedback form will be displayed here',
    sent_at: new Date().toISOString(),
    type: 'feedback',
  };

  messages.value.push(feedbackMessage);
  await scrollToBottom();
};

// Rating helper function
const getRatingLabel = (rating) => {
  if (rating === 0) return 'Pilih rating (1-5 bintang)';
  const labels = {
    1: '1 dari 5 bintang - Sangat Buruk',
    2: '2 dari 5 bintang - Buruk',
    3: '3 dari 5 bintang - Cukup',
    4: '4 dari 5 bintang - Baik',
    5: '5 dari 5 bintang - Sangat Baik',
  };
  return labels[rating] || `${rating} dari 5 bintang`;
};

// Handle rating change
const onRatingChange = (rating) => {
  feedbackRating.value = rating;
};

const submitFeedback = async () => {
  if (feedbackRating.value === 0) {
    showErrorMessage('Mohon berikan rating untuk layanan kami');
    return;
  }

  try {
    isSubmittingFeedback.value = true;

    const response = await axios.post('/api/chat/feedback', {
      session_id: getSessionId(),
      rating: feedbackRating.value,
      feedback_text: feedbackText.value.trim() || null,
    });

    if (response.data.success) {
      feedbackSubmitted.value = true;
      showFeedbackDialog.value = false;
      clearTimeout(idleTimer);

      // Add final message to chat
      const finalMessage = {
        id: `final_${Date.now()}`,
        role: 'assistant',
        content:
          '✅ **Terima kasih atas feedback Anda!**\n\nMasukan Anda sangat berharga untuk meningkatkan kualitas layanan SALMA AI. Sampai jumpa! 👋\n\nAnda akan diarahkan ke halaman utama dalam 5 detik...',
        sent_at: new Date().toISOString(),
        type: 'final',
      };

      messages.value.push(finalMessage);
      await scrollToBottom();

      // End session
      await endChatSession();

      // Redirect to home after 5 seconds
      setTimeout(() => {
        router.visit('/');
      }, 5000);
    } else {
      showErrorMessage(response.data.message || 'Gagal menyimpan feedback');
    }
  } catch (error) {
    console.error('Error submitting feedback:', error);
    showErrorMessage('Gagal menyimpan feedback. Silakan coba lagi.');
  } finally {
    isSubmittingFeedback.value = false;
  }
};

window.hoverStar = (hoveredStar) => {
  // Only show hover effect if no rating is selected yet
  if (feedbackRating.value === 0) {
    document.querySelectorAll('[data-star]').forEach((star) => {
      const starNum = parseInt(star.getAttribute('data-star'));
      star.style.color = starNum <= hoveredStar ? '#ffc107' : '#ddd';
    });
  }
};

window.resetStarHover = () => {
  // Reset to current rating or gray if no rating
  if (feedbackRating.value > 0) {
    updateStarDisplay(feedbackRating.value);
  } else {
    document.querySelectorAll('[data-star]').forEach((star) => {
      star.style.color = '#ddd';
    });
  }
};

window.updateStarColors = (hoveredStar) => {
  if (feedbackRating.value > 0) {
    updateStarDisplay(feedbackRating.value);
  } else {
    // Reset to gray if no rating selected
    document.querySelectorAll('[data-star]').forEach((star) => {
      star.style.color = '#ddd';
    });
  }
};

const updateStarDisplay = (rating) => {
  document.querySelectorAll('[data-star]').forEach((star) => {
    const starNum = parseInt(star.getAttribute('data-star'));
    star.style.color = starNum <= rating ? '#ffc107' : '#ddd';
  });
};

const endChatSession = async () => {
  try {
    await axios.post('/api/chat/end-session', {
      session_id: getSessionId(),
    });

    // Clear local storage
    localStorage.removeItem('chat_session_id');
  } catch (error) {
    console.error('Error ending chat session:', error);
  }
};

// Utility functions
const formatTime = (timestamp) => {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatMessage = async (content) => {
  if (!content) return '';

  // If already looks like HTML, return as-is (for our follow-up/feedback messages)
  const looksHtml = /<\w+[\s\S]*>/m.test(content);
  if (looksHtml) return content;

  // Enhanced formatting with better link handling
  const formatted = content
    // Handle Markdown headings FIRST (before line break conversion)
    .replace(
      /^### (.+)$/gm,
      '<h4 style="font-size:13px;font-weight:700;margin:10px 0 4px;color:#1a1a2e">$1</h4>',
    )
    .replace(
      /^## (.+)$/gm,
      '<h3 style="font-size:14px;font-weight:700;margin:12px 0 5px;color:#1a1a2e">$1</h3>',
    )
    .replace(
      /^# (.+)$/gm,
      '<h2 style="font-size:15px;font-weight:700;margin:14px 0 6px;color:#1a1a2e">$1</h2>',
    )
    // Handle Markdown-style links [text](url) first
    .replace(
      /\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g,
      '<a href="$2" target="_blank" rel="noopener noreferrer" style="color: #7C3AED; text-decoration: underline; font-weight: 500;">$1</a>',
    )
    // Handle plain URLs (but not those already in HTML tags)
    .replace(
      /(?<!href="|">)(https?:\/\/[^\s<]+)(?![^<]*<\/a>)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #7C3AED; text-decoration: underline;">$1</a>',
    )
    // Convert bold text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // Convert italic text
    .replace(/(?<!\*)\*([^*\n]+)\*(?!\*)/g, '<em>$1</em>')
    // Convert line breaks
    .replace(/\n/g, '<br>')
    // Format numbered lists with proper line breaks
    .replace(/^(\d+\.\s)/gm, '<br>$1')
    // Format bullet points
    .replace(/^[-*]\s/gm, '<br>• ');

  return formatted;
};

const getFormattedContent = (message) => {
  const key = message.id || message.content;

  // Return cached formatted content if available
  if (formattedMessages.value.has(key)) {
    return formattedMessages.value.get(key);
  }

  // Format message asynchronously and cache result
  formatMessage(message.content).then((formatted) => {
    formattedMessages.value.set(key, formatted);
  });

  // Return simple formatted content as fallback while processing
  let content = message.content || '';

  // Enhanced formatting with better link handling
  content = content
    // Handle Markdown headings FIRST (before line break conversion)
    .replace(
      /^### (.+)$/gm,
      '<h4 style="font-size:13px;font-weight:700;margin:10px 0 4px;color:#1a1a2e">$1</h4>',
    )
    .replace(
      /^## (.+)$/gm,
      '<h3 style="font-size:14px;font-weight:700;margin:12px 0 5px;color:#1a1a2e">$1</h3>',
    )
    .replace(
      /^# (.+)$/gm,
      '<h2 style="font-size:15px;font-weight:700;margin:14px 0 6px;color:#1a1a2e">$1</h2>',
    )
    // Handle Markdown-style links [text](url)
    .replace(
      /\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g,
      '<a href="$2" target="_blank" rel="noopener noreferrer" style="color: #7C3AED; text-decoration: underline; font-weight: 500;">$1</a>',
    )
    // Handle plain URLs (but not those already in HTML tags)
    .replace(
      /(?<!href="|">)(https?:\/\/[^\s<]+)(?![^<]*<\/a>)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #7C3AED; text-decoration: underline;">$1</a>',
    )
    // Handle bold text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // Handle italic text
    .replace(/(?<!\*)\*([^*\n]+)\*(?!\*)/g, '<em>$1</em>')
    // Handle line breaks
    .replace(/\n/g, '<br>')
    // Handle numbered lists
    .replace(/^(\d+\.\s)/gm, '<br>$1')
    // Handle bullet points
    .replace(/^[-*]\s/gm, '<br>• ');

  return content;
};

const getSuggestionColor = (index) => {
  const colors = ['#E9A5F1', '#C68EFD', '#8F87F1'];
  return colors[index % colors.length];
};

const getSuggestionIcon = (index) => {
  const icons = [
    'mdi-credit-card',
    'mdi-card-account-details',
    'mdi-map-marker',
    'mdi-currency-usd',
  ];
  return icons[index % icons.length];
};

const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const showErrorMessage = (message) => {
  errorMessage.value = message;
  showError.value = true;
};

// Lightbox handlers
const handleContentClick = (event) => {
  const { target } = event;
  if (!target || typeof target.closest !== 'function') return;
  // End chat buttons
  const endBtn = target.closest('[data-action="end-chat"]');
  if (endBtn) {
    event.preventDefault();
    endChat();
    return;
  }
  // If clicking an anchor-wrapped image
  const anchor = target.closest('a.kb-lightbox');
  if (anchor) {
    event.preventDefault();
    const href = anchor.getAttribute('href');
    if (!href) return;
    const container = anchor.closest('.assistant-message');
    const links = container
      ? Array.from(container.querySelectorAll('a.kb-lightbox'))
      : [anchor];
    const imgs = links
      .map((a) => a.getAttribute('href'))
      .filter((u) => typeof u === 'string' && u.length > 0);
    lightboxImages.value = imgs;
    lightboxIndex.value = Math.max(0, imgs.indexOf(href));
    lightboxVisible.value = true;
    return;
  }
  // If clicking a plain image inside assistant content
  const imgEl = target.closest('.assistant-message img');
  if (imgEl) {
    event.preventDefault();
    const src = imgEl.getAttribute('src');
    if (!src) return;
    lightboxImages.value = [src];
    lightboxIndex.value = 0;
    lightboxVisible.value = true;
  }
};

// Track whether we've attached the delegated click listener
const listenerAttached = ref(false);

// Initialize on mount
onMounted(() => {
  initializeChat();

  if (messagesContainer.value) {
    messagesContainer.value.addEventListener('click', handleContentClick);
    listenerAttached.value = true;
  }

  // If the messages container is not present at mount (welcome screen), attach listener when it appears
  watch(messagesContainer, (newEl) => {
    if (newEl && !listenerAttached.value) {
      newEl.addEventListener('click', handleContentClick);
      listenerAttached.value = true;
    }
  });

  // Check for PKB context from PKB form
  const pkbContext = localStorage.getItem('pkb_context');
  if (pkbContext) {
    // Remove from localStorage
    localStorage.removeItem('pkb_context');

    // Send PKB context as first message
    setTimeout(() => {
      sendMessage(pkbContext, true); // true indicates this is context, not user message
    }, 1000);
  }

  // Check for initial question
  const initialQuestion = localStorage.getItem('initial_question');
  if (initialQuestion) {
    localStorage.removeItem('initial_question');
    setTimeout(() => {
      sendQuickMessage(initialQuestion);
    }, 500);
  }
});

onBeforeUnmount(() => {
  clearTimeout(idleTimer);
  if (messagesContainer.value && listenerAttached.value) {
    messagesContainer.value.removeEventListener('click', handleContentClick);
    listenerAttached.value = false;
  }
});

// Watch for new messages and scroll
watch(
  messages,
  () => {
    nextTick(() => {
      scrollToBottom();
    });
    // If last message is by assistant and not a follow-up/final/feedback, inject follow-up
    const last = messages.value[messages.value.length - 1];
    if (!last) return;
    const excludedTypes = new Set(['follow_up', 'feedback', 'final', 'test']);
    if (last.role === 'assistant' && !excludedTypes.has(last.type)) {
      showFollowUpMessage();
    }
  },
  { deep: true },
);

const chatTourSteps = [
  { title: 'Layanan Chat SALMA AI', intro: 'Selamat datang! Ini adalah halaman chat interaktif dengan AI SALMA — Asisten Digital Samsat Lamongan.' },
  { element: '#tour-chat-header', title: 'Header Chat', intro: 'Bagian atas menampilkan nama asisten (SALMA AI) beserta data kendaraan Anda. Gunakan tombol ↩ untuk kembali, atau ↺ untuk memulai sesi chat baru.' },
  { element: '#tour-chat-welcome', title: 'Area Sambutan & Saran', intro: 'Sebelum memulai, Anda akan melihat layar sambutan dengan contoh pertanyaan populer. Klik salah satu kartu saran untuk langsung bertanya.' },
  { element: '#tour-chat-input', title: 'Kolom Pesan', intro: 'Ketik pertanyaan Anda tentang pajak kendaraan, STNK, denda, jadwal Samsat Keliling, atau layanan lainnya di sini, lalu tekan Enter atau tombol kirim.' },
  { title: 'Mengakhiri Sesi & Memberikan Rating', intro: 'Setelah selesai, klik tombol "Akhiri Chat" (tombol merah di bawah). Dialog penilaian akan muncul — pilih bintang 1–5 dan tulis komentar opsional, lalu klik "Kirim Feedback".' },
];
const { startTour: startChatTour } = useTour(chatTourSteps);

</script>

<style scoped>
/* ── Full-height layout ─────────────────────────────────────────────── */
/* VMain is the Vuetify 3 correct way: it auto-applies top-padding equal
   to VAppBar height, so nothing overlaps the header. */
.chat-main {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
}

.chat-app {
  background: transparent;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-row {
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.chat-row > .v-col {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
}

.chat-container {
  flex: 1 1 auto;
  min-height: 0;
  display: flex !important;
  flex-direction: column;
  width: 100%;
  max-width: 1000px;
  margin: 0 auto;
  /* Fill remaining height inside VMain */
  height: 100%;
}

.chat-messages-area {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.messages-scroll {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
}

/* ── SALMA Mascot & Avatars ─────────────────────────────────────────── */
.salma-mascot-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  /* fixed dimensions so page doesn't jump on gif load */
  width: 140px;
  height: 140px;
  margin: 0 auto;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #f3e8ff, #ede9fe);
  box-shadow: 0 8px 32px rgba(147, 51, 234, 0.25);
}

.salma-mascot-img {
  width: 140px;
  height: 140px;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

/* Small avatar next to chat bubbles */
.salma-msg-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  /* background: linear-gradient(135deg, #8f87f1, #c68efd); */
  border: 2px solid #ede9fe;
}

.salma-msg-img {
  width: 80px;
  height: 100px;
  object-fit: fill;
  /* border-radius: 50%; */
  display: block;
}

@media screen and (max-width: 600px) {
  .salma-msg-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    /* background: linear-gradient(135deg, #8f87f1, #c68efd); */
    border: 2px solid #ede9fe;
  }

  .salma-msg-img {
    width: 40 px;
    height: 50px;
    object-fit: fill;
    /* border-radius: 50%; */
    display: block;
  }
}

/* Tiny header avatar next to SALMA AI title */
.salma-header-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.5);
  background: rgba(255, 255, 255, 0.15);
}

.salma-header-img {
  width: 34px;
  height: 34px;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

/* ── Header ─────────────────────────────────────────────────────────── */
.chat-header {
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(147, 51, 234, 0.35) !important;
}

/* ── Welcome screen ─────────────────────────────────────────────────── */
.gradient-text {
  background: linear-gradient(135deg, #9b59b6, #7d3c98);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.suggestion-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
}

.start-chat-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(125, 60, 152, 0.55) !important;
}

/* ── Message input ──────────────────────────────────────────────────── */
.message-input >>> .v-field {
  border-radius: 20px !important;
  border: 1px solid #e0e0e0 !important;
}

.message-input >>> .v-field:focus-within {
  border-color: #9333ea !important;
  box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.18) !important;
}

/* ── Message bubbles ────────────────────────────────────────────────── */
.assistant-content .kb-image-gallery {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}
.assistant-content .kb-image-gallery img {
  max-width: 120px;
  max-height: 120px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #eee;
}

.assistant-content .kb-reference {
  margin-top: 12px;
  padding-top: 8px;
  border-top: 1px dashed #e0e0e0;
}
.assistant-content .kb-ref-header {
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
}
.assistant-content .kb-ref-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  border: 1px solid #eee;
}
.assistant-content .kb-ref-content p {
  margin: 0.5em 0;
}

.assistant-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

.text-white-70 {
  color: rgba(255, 255, 255, 0.7) !important;
}

.max-width-75 {
  max-width: 75%;
}

.max-width-80 {
  max-width: 80%;
}

.gap-2 > * + * {
  margin-left: 8px;
}

.user-message,
.assistant-message {
  transition: all 0.2s ease;
}

/* ── Typing indicator ───────────────────────────────────────────────── */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
}

.typing-dots {
  display: flex;
  align-items: center;
  gap: 4px;
}

.typing-dots span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #9333ea;
  animation: typing-bounce 1.4s infinite ease-in-out both;
  display: inline-block;
}

.typing-dots span:nth-child(1) {
  animation-delay: -0.32s;
}
.typing-dots span:nth-child(2) {
  animation-delay: -0.16s;
}
.typing-dots span:nth-child(3) {
  animation-delay: 0s;
}

.typing-text {
  font-size: 12px;
  color: #666;
  margin-left: 8px;
}

@keyframes typing-bounce {
  0%,
  80%,
  100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}

/* ── Scrollbar ──────────────────────────────────────────────────────── */
.messages-scroll::-webkit-scrollbar {
  width: 5px;
}

.messages-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.messages-scroll::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #9b59b6, #7d3c98);
  border-radius: 10px;
}

.messages-scroll::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #7d3c98, #6c3483);
}

/* ── Feedback dialog ────────────────────────────────────────────────── */
.feedback-popup {
  border-radius: 16px !important;
  overflow: hidden;
}

.feedback-emoji-large {
  font-size: 44px;
  line-height: 1;
}

.rating-label {
  min-height: 20px;
  transition: color 0.2s;
}

/* ── Final thank you message ─────────────────────────────────────────── */
.final-message {
  animation: slideInUp 0.5s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ── Mobile responsive ──────────────────────────────────────────────── */
.max-width-90 {
  max-width: 90%;
}

@media (max-width: 768px) {
  .chat-app {
    height: 100%; /* VMain handles the full height */
  }

  .salma-mascot-wrapper {
    width: 110px;
    height: 110px;
  }

  .salma-mascot-img {
    width: 110px;
    height: 110px;
  }

  .max-width-75 {
    max-width: 88%;
  }

  .max-width-80 {
    max-width: 92%;
  }

  .max-width-90 {
    max-width: 96%;
  }

  .welcome-section {
    padding: 16px !important;
  }

  .gradient-text {
    font-size: 1.2rem !important;
  }

  .suggestion-card {
    min-height: 64px !important;
  }
}
</style>
