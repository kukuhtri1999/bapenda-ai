<template>
  <VApp>
    <Head title="Mulai Chat"></Head>

    <!-- ── App Bar: Compact, Sleek Header with Crisp Responsive Typography ── -->
    <VAppBar
      density="compact"
      id="tour-chat-header"
      class="chat-header"
      :style="{
        background:
          'linear-gradient(135deg, #C0392B 0%, #D32F2F 50%, #B03022 100%)',
        boxShadow: '0 2px 12px rgba(192, 57, 43, 0.35)',
      }"
    >
      <VBtn
        icon="mdi-arrow-left"
        variant="text"
        color="white"
        size="small"
        @click="goToHome"
        title="Kembali ke Beranda"
        class="header-icon-btn me-1.5 flex-shrink-0"
      ></VBtn>

      <div class="chat-header-content d-flex align-center flex-grow-1 min-w-0 me-2">
        <!-- Salma mini avatar in header -->
        <div class="salma-header-avatar me-2 flex-shrink-0">
          <img
            src="/images/salma2.gif"
            alt="SALMA"
            loading="lazy"
            class="salma-header-img"
          />
        </div>

        <div class="chat-header-text min-w-0 flex-grow-1">
          <div class="chat-header-top d-flex align-center gap-1.5">
            <span class="chat-title-brand">SALMA AI</span>
            <span v-if="isBeta" class="chat-beta-badge">BETA</span>
            <span class="chat-title-sub d-none d-sm-inline opacity-90">— Asisten Samsat Lamongan</span>
          </div>
          <div class="chat-header-bottom text-truncate">
            <template v-if="props.wajibPajakData">
              <span class="sub-name">{{ props.wajibPajakData.nama }}</span>
              <span v-if="props.wajibPajakData.nopol" class="sub-nopol ms-1 font-weight-bold">({{ props.wajibPajakData.nopol }})</span>
            </template>
            <template v-else>
              <span class="sub-status-online">● Online</span>
              <span class="sub-status-desc ms-1 d-none d-xs-inline opacity-75">Samsat Lamongan</span>
            </template>
          </div>
        </div>
      </div>

      <div class="d-flex align-center gap-0.5 flex-shrink-0 ms-auto">
        <VBtn
          icon="mdi-help-circle-outline"
          variant="text"
          color="white"
          size="small"
          @click="startChatTour"
          title="Panduan Penggunaan"
          class="header-icon-btn"
        ></VBtn>
        <VBtn
          icon="mdi-refresh"
          variant="text"
          color="white"
          size="small"
          @click="startNewChat"
          title="Mulai Percakapan Baru"
          class="header-icon-btn"
        ></VBtn>
      </div>
    </VAppBar>

    <!-- ── VMain auto-applies top padding = AppBar height ── -->
    <VMain class="chat-main">
      <div class="chat-viewport">
        <!-- Chat Container -->
        <div class="chat-container">
          <!-- Scrollable Chat Body (Welcome or Messages) -->
          <div
            ref="messagesContainer"
            class="chat-scroll-area messages-scroll"
            @click="handleContentClick"
          >
            <!-- Welcome Section -->
            <div
              v-if="!chatSession || messages.length === 0"
              id="tour-chat-welcome"
              class="welcome-section"
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

              <h2 class="text-h5 mb-1.5 gradient-text font-weight-bold">
                Selamat Datang di Layanan AI
              </h2>
              <h3 class="text-subtitle-1 mb-2 font-weight-bold text-[#C0392B]">KB Samsat Lamongan</h3>
              <p class="welcome-desc text-grey-700 mb-4 mx-auto">
                Saya siap membantu Anda dengan informasi seputar pajak kendaraan,
                STNK, jadwal keliling, dan layanan Samsat lainnya 24/7.
              </p>

              <!-- Quick Suggestions -->
              <div class="quick-suggestions-wrap mb-4">
                <h4 class="text-caption font-weight-bold text-grey-700 mb-2 text-uppercase tracking-wider">
                  Pertanyaan Populer:
                </h4>
                <VRow justify="center" class="ma-0" dense>
                  <VCol
                    v-for="(suggestion, index) in quickSuggestions"
                    :key="suggestion"
                    cols="12"
                    sm="6"
                    class="pa-1"
                  >
                    <div
                      @click="sendQuickMessage(suggestion)"
                      class="suggestion-card"
                    >
                      <VIcon
                        :color="getSuggestionColor(index)"
                        size="20"
                        class="me-2 flex-shrink-0"
                      >
                        {{ getSuggestionIcon(index) }}
                      </VIcon>
                      <span class="suggestion-text">
                        {{ suggestion }}
                      </span>
                    </div>
                  </VCol>
                </VRow>
              </div>

              <VBtn
                @click="startNewChat"
                :style="{
                  background: 'linear-gradient(135deg, #C0392B, #D32F2F)',
                  borderRadius: '20px',
                  textTransform: 'none',
                  padding: '8px 24px',
                  boxShadow: '0 4px 14px rgba(192, 57, 43, 0.4)',
                }"
                color="white"
                class="start-chat-btn text-white font-weight-bold"
                size="default"
                elevation="0"
              >
                <VIcon start size="18">mdi-chat</VIcon>
                Mulai Chat AI
              </VBtn>
            </div>

            <!-- Messages Area -->
            <div v-else id="tour-chat-messages" class="chat-messages-area">
              <!-- Message Items -->
              <div
                v-for="message in messages"
                :key="message.id || message.sent_at"
                class="mb-3"
              >
                <!-- User Message -->
                <div
                  v-if="message.role === 'user'"
                  class="d-flex justify-end mb-2"
                >
                  <div class="user-message-bubble">
                    <div class="user-msg-content font-weight-medium">
                      {{ message.content }}
                    </div>
                    <div class="user-msg-time text-right mt-1">
                      <small>{{ formatTime(message.sent_at) }}</small>
                    </div>
                  </div>
                </div>

                <!-- Assistant Message -->
                <div v-else class="d-flex justify-start align-start mb-2">
                  <!-- Salma avatar -->
                  <div class="salma-msg-avatar me-2 mt-0.5 flex-shrink-0">
                    <img
                      src="/images/salma2.gif"
                      alt="SALMA"
                      loading="lazy"
                      class="salma-msg-img"
                    />
                  </div>
                  <div class="assistant-message-bubble flex-grow-1">
                    <div
                      class="assistant-content text-grey-800"
                      v-html="getFormattedContent(message)"
                    ></div>
                    <div class="assistant-msg-time text-left mt-1">
                      <small>{{ formatTime(message.sent_at) }}</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Typing Indicator -->
              <div v-if="isTyping" class="d-flex justify-start align-start mb-2">
                <div class="salma-msg-avatar me-2 mt-0.5 flex-shrink-0">
                  <img
                    src="/images/salma2.gif"
                    alt="SALMA"
                    loading="lazy"
                    class="salma-msg-img"
                  />
                </div>
                <div class="typing-indicator-bubble">
                  <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                  </div>
                  <span class="typing-text ms-2">sedang mengetik...</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ═══ ALWAYS FIXED & FLOATING BOTTOM INPUT BAR ═══ -->
          <div class="chat-floating-bar-wrap">
            <div class="chat-floating-bar-inner">
              <div
                class="chat-input-pill"
                :class="{ 'chat-input-pill--focused': isInputFocused, 'chat-input-pill--disabled': isLoading }"
              >
                <VTextarea
                  v-model="currentMessage"
                  placeholder="Ketik pertanyaan Anda disini..."
                  rows="1"
                  auto-grow
                  max-rows="4"
                  variant="plain"
                  id="tour-chat-input"
                  class="chat-plain-input"
                  :disabled="isLoading"
                  @keydown.enter="handleEnterKey"
                  @focus="isInputFocused = true"
                  @blur="isInputFocused = false"
                  hide-details
                  density="compact"
                ></VTextarea>

                <button
                  type="button"
                  @click="() => sendMessage()"
                  :disabled="!currentMessage.trim() || isLoading"
                  class="chat-send-btn"
                  :class="{ 'chat-send-btn--active': currentMessage.trim() && !isLoading }"
                  title="Kirim Pesan"
                  aria-label="Kirim Pesan"
                >
                  <VIcon v-if="!isLoading" size="20" color="white">mdi-send</VIcon>
                  <VProgressCircular
                    v-else
                    indeterminate
                    size="18"
                    width="2.5"
                    color="white"
                  ></VProgressCircular>
                </button>
              </div>

              <div class="chat-recaptcha-footer">
                Protected by reCAPTCHA • <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy</a> - <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Terms</a>
              </div>
            </div>
          </div>
        </div>
      </div>
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
            background: 'linear-gradient(135deg, #C0392B 0%, #D32F2F 100%)',
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
              :style="{ color: feedbackRating > 0 ? '#C0392B' : '#9e9e9e' }"
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
            :color="'primary'"
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
                  ? 'linear-gradient(135deg, #C0392B, #D32F2F)'
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

    <!-- Tour Button positioned cleanly above floating bar -->
    <div class="chat-tour-anchor">
      <TourButton @start="startChatTour" variant="public" />
    </div>
  </VApp>
</template>

<script setup>
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';
import {
  ref, computed, onMounted, onBeforeUnmount, nextTick, watch,
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
  cms: {
    type: Object,
    default: () => ({}),
  },
});

const isBeta = computed(() => {
  if (props.cms && props.cms.salma_is_beta !== undefined) {
    return Boolean(props.cms.salma_is_beta);
  }
  return true;
});

// Reactive data
const chatSession = ref(null);
const messages = ref([]);
const currentMessage = ref('');
const isLoading = ref(false);
const isTyping = ref(false);
const isInputFocused = ref(false);
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

// Google reCAPTCHA v3 helper with timeout safeguard
const getRecaptchaToken = async (action = 'chat_message') => {
  const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || '6Ld4LYQtAAAAACEQjznEQrI0x5v34bAZ49OvQleG';
  if (typeof window !== 'undefined' && window.grecaptcha && window.grecaptcha.execute) {
    try {
      return await Promise.race([
        window.grecaptcha.execute(siteKey, { action }),
        new Promise((resolve) => setTimeout(() => resolve(null), 1200)),
      ]);
    } catch (err) {
      console.warn('reCAPTCHA execution notice:', err);
    }
  }
  return null;
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

    const recaptchaToken = await getRecaptchaToken('start_chat');

    const response = await axios.post('/api/chat/start', {
      session_id: getSessionId(),
      recaptcha_token: recaptchaToken,
    });

    if (response.data.success) {
      chatSession.value = response.data.chat;
      messages.value = response.data.chat.messages || [];
      await scrollToBottom();
      resetIdleTimer();
    }
  } catch (error) {
    console.error('Error initializing chat:', error);
    if (!messages.value || messages.value.length === 0) {
      messages.value = [{
        id: 'assistant_greeting_' + Date.now(),
        role: 'assistant',
        content: 'Halo! Saya SALMA AI, asisten virtual resmi KB Samsat Lamongan. Ada yang bisa saya bantu terkait pajak kendaraan atau layanan Samsat hari ini?',
        sent_at: new Date().toISOString(),
      }];
    }
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
    const recaptchaToken = await getRecaptchaToken('start_chat');

    const response = await axios.post('/api/chat/start', {
      session_id: sessionId,
      recaptcha_token: recaptchaToken,
    });

    if (response.data.success) {
      chatSession.value = response.data.chat;
      messages.value = response.data.chat.messages || [];
      await scrollToBottom();
      resetIdleTimer();
    }
  } catch (error) {
    console.error('Error starting new chat:', error);
    messages.value = [{
      id: 'assistant_greeting_' + Date.now(),
      role: 'assistant',
      content: 'Halo! Saya SALMA AI, asisten virtual resmi KB Samsat Lamongan. Ada yang bisa saya bantu terkait pajak kendaraan atau layanan Samsat hari ini?',
      sent_at: new Date().toISOString(),
    }];
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

    const recaptchaToken = await getRecaptchaToken('chat_message');

    const response = await axios.post('/api/chat/message', {
      session_id: getSessionId(),
      message: text,
      is_context: isContext,
      recaptcha_token: recaptchaToken,
    });

    isTyping.value = false;

    if (response.data && response.data.success) {
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
      const errText = response.data?.message || 'Gagal mengirim pesan.';
      messages.value.push({
        role: 'assistant',
        content: `⚠️ ${errText}`,
        sent_at: new Date().toISOString(),
        id: `assistant_err_${Date.now()}`,
      });
      await scrollToBottom();
    }
  } catch (error) {
    isTyping.value = false;
    console.error('Error sending message:', error);
    const msg = error.response?.data?.message || 'Maaf, terjadi kendala saat memproses jawaban. Silakan coba kirim ulang pertanyaan Anda.';
    messages.value.push({
      role: 'assistant',
      content: `⚠️ ${msg}`,
      sent_at: new Date().toISOString(),
      id: `assistant_err_${Date.now()}`,
    });
    await scrollToBottom();
  } finally {
    isLoading.value = false;
    isTyping.value = false;
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
        'Ada lagi yang bisa SALMA bantu? <button data-action="end-chat" style="display:inline-flex;align-items:center;gap:5px;margin-left:10px;padding:6px 14px;border-radius:20px;border:1.5px solid #C0392B;background:#fef2f2;color:#C0392B;cursor:pointer;font-size:12px;font-weight:600;transition:all 0.2s;">⛔ Akhiri Chat</button>',
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
      '<a href="$2" target="_blank" rel="noopener noreferrer" style="color: #C0392B; text-decoration: underline; font-weight: 600;">$1</a>',
    )
    // Handle plain URLs (but not those already in HTML tags)
    .replace(
      /(?<!href="|">)(https?:\/\/[^\s<]+)(?![^<]*<\/a>)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #C0392B; text-decoration: underline;">$1</a>',
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
      '<a href="$2" target="_blank" rel="noopener noreferrer" style="color: #C0392B; text-decoration: underline; font-weight: 600;">$1</a>',
    )
    // Handle plain URLs (but not those already in HTML tags)
    .replace(
      /(?<!href="|">)(https?:\/\/[^\s<]+)(?![^<]*<\/a>)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #C0392B; text-decoration: underline;">$1</a>',
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
  const colors = ['#C0392B', '#2980B9', '#27AE60', '#D35400'];
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
/* ═══════════════════════════════════════════════════════════════════════════
   SALMA AI CHAT INTERFACE — Premium Government Red (#C0392B) Theme
   Always-Fixed Floating Input + Sleek Responsive Layout
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── Full-Height Viewport Layout ────────────────────────────────────────── */
.chat-main {
  height: calc(100vh - 48px);
  height: calc(100dvh - 48px);
  overflow: hidden;
  background: #F8FAFC;
  display: flex;
  flex-direction: column;
}

@media (max-width: 600px) {
  .chat-main {
    height: calc(100vh - 48px);
    height: calc(100dvh - 48px);
  }
}

.chat-viewport {
  flex: 1 1 auto;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
}

.chat-container {
  width: 100%;
  max-width: 920px;
  margin: 0 auto;
  height: 100%;
  display: flex;
  flex-direction: column;
  position: relative;
  background: #FFFFFF;
  border-left: 1px solid #EEF2F6;
  border-right: 1px solid #EEF2F6;
  box-shadow: 0 0 30px rgba(0, 0, 0, 0.03);
}

@media (max-width: 920px) {
  .chat-container {
    max-width: 100%;
    border-left: none;
    border-right: none;
  }
}

/* ── Scrollable Chat Content ────────────────────────────────────────────── */
.chat-scroll-area {
  flex: 1 1 auto;
  overflow-y: auto;
  padding: 20px 20px 115px 20px;
  background: linear-gradient(180deg, #FAFAFC 0%, #FFFFFF 100%);
  display: flex;
  flex-direction: column;
}

@media (max-width: 600px) {
  .chat-scroll-area {
    padding: 14px 12px 110px 12px;
  }
}

/* Custom Scrollbar */
.messages-scroll::-webkit-scrollbar {
  width: 5px;
}
.messages-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.messages-scroll::-webkit-scrollbar-thumb {
  background: rgba(192, 57, 43, 0.25);
  border-radius: 10px;
}
.messages-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(192, 57, 43, 0.45);
}

/* ── App Header ─────────────────────────────────────────────────────────── */
.chat-header {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.chat-header :deep(.v-toolbar__content) {
  padding: 0 8px !important;
}
.header-icon-btn {
  width: 32px !important;
  height: 32px !important;
  min-width: 32px !important;
  border-radius: 50% !important;
  transition: all 0.2s ease !important;
}
.header-icon-btn:hover {
  background: rgba(255, 255, 255, 0.18) !important;
  transform: scale(1.05);
}
.salma-header-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 1.5px solid rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.2);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
}
.salma-header-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.chat-header-content {
  flex: 1 1 auto;
  min-width: 0;
}
.chat-header-text {
  flex: 1 1 auto;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.chat-header-top {
  white-space: nowrap;
  line-height: 1.2;
}
.chat-title-brand {
  font-size: 0.92rem; /* 14.7px */
  font-weight: 700;
  letter-spacing: -0.01em;
  color: #FFFFFF;
  line-height: 1.2;
  white-space: nowrap;
  flex-shrink: 0;
}
.chat-title-sub {
  font-size: 0.75rem; /* 12px */
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
  white-space: nowrap;
}
.chat-beta-badge {
  display: inline-flex;
  align-items: center;
  font-size: 8px;
  font-weight: 800;
  background: #FFFFFF;
  color: #C0392B;
  padding: 0.5px 5px;
  border-radius: 8px;
  letter-spacing: 0.04em;
  line-height: 1.2;
  white-space: nowrap;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
.chat-header-bottom {
  font-size: 10.5px;
  line-height: 1.15;
  color: rgba(255, 255, 255, 0.88);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sub-status-online {
  color: #86EFAC;
  font-weight: 600;
  font-size: 10px;
}
.sub-name {
  font-weight: 500;
  color: #FFFFFF;
}
.sub-nopol {
  opacity: 0.92;
}

/* ── Welcome Section ────────────────────────────────────────────────────── */
.welcome-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-height: 100%;
  padding: 24px 16px;
  margin: auto 0;
}
.salma-mascot-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 110px;
  height: 110px;
  margin: 0 auto;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #FEE2E2, #FEF2F2);
  box-shadow: 0 8px 24px rgba(192, 57, 43, 0.2);
  border: 2px solid #FFFFFF;
}
.salma-mascot-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.gradient-text {
  background: linear-gradient(135deg, #C0392B, #962D22);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.welcome-desc {
  max-width: 480px;
  font-size: 0.88rem;
  line-height: 1.6;
}
.quick-suggestions-wrap {
  width: 100%;
  max-width: 580px;
}
.suggestion-card {
  display: flex;
  align-items: center;
  background: #FFFFFF;
  border: 1.5px solid #EEF2F6;
  border-radius: 14px;
  padding: 10px 14px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: left;
  height: 100%;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}
.suggestion-card:hover {
  transform: translateY(-2px);
  border-color: rgba(192, 57, 43, 0.35);
  background: #FFFBFB;
  box-shadow: 0 6px 16px rgba(192, 57, 43, 0.08);
}
.suggestion-text {
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
  line-height: 1.35;
}
.start-chat-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(192, 57, 43, 0.45) !important;
}

/* ── Message Bubbles ────────────────────────────────────────────────────── */
.user-message-bubble {
  background: linear-gradient(135deg, #C0392B 0%, #D32F2F 100%);
  color: #FFFFFF;
  border-radius: 18px 18px 4px 18px;
  padding: 11px 16px;
  max-width: 80%;
  box-shadow: 0 3px 12px rgba(192, 57, 43, 0.25);
  animation: fadeInMsg 0.25s ease-out;
}
@media (max-width: 600px) {
  .user-message-bubble {
    max-width: 88%;
    padding: 10px 14px;
  }
}
.user-msg-content {
  font-size: 0.88rem;
  line-height: 1.5;
  word-break: break-word;
}
.user-msg-time {
  opacity: 0.8;
  font-size: 10px;
}

.assistant-message-bubble {
  background: #FFFFFF;
  border: 1px solid #E2E8F0;
  border-radius: 18px 18px 18px 4px;
  padding: 13px 16px;
  max-width: 85%;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
  animation: fadeInMsg 0.25s ease-out;
}
@media (max-width: 600px) {
  .assistant-message-bubble {
    max-width: 90%;
    padding: 11px 14px;
  }
}
.assistant-content {
  font-size: 0.88rem;
  line-height: 1.65;
  word-break: break-word;
}
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
  border: 1px solid #E2E8F0;
  cursor: pointer;
  transition: transform 0.2s ease;
}
.assistant-content .kb-image-gallery img:hover {
  transform: scale(1.03);
}
.assistant-content .kb-reference {
  margin-top: 12px;
  padding-top: 8px;
  border-top: 1px dashed #E2E8F0;
}
.assistant-content .kb-ref-header {
  font-size: 11.5px;
  color: #64748B;
  margin-bottom: 6px;
}
.assistant-content .kb-ref-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  border: 1px solid #E2E8F0;
}
.assistant-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}
.assistant-msg-time {
  color: #94A3B8;
  font-size: 10px;
}

.salma-msg-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 1.5px solid rgba(192, 57, 43, 0.2);
  background: #FFF5F5;
  box-shadow: 0 2px 6px rgba(192, 57, 43, 0.1);
}
.salma-msg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
@media (max-width: 600px) {
  .salma-msg-avatar {
    width: 30px;
    height: 30px;
  }
}

@keyframes fadeInMsg {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ── Typing Indicator ───────────────────────────────────────────────────── */
.typing-indicator-bubble {
  background: #FFFFFF;
  border: 1px solid #E2E8F0;
  border-radius: 18px 18px 18px 4px;
  padding: 10px 16px;
  display: inline-flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
  background-color: #C0392B;
  animation: typing-bounce 1.4s infinite ease-in-out both;
  display: inline-block;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }
.typing-dots span:nth-child(3) { animation-delay: 0s; }
.typing-text {
  font-size: 11px;
  color: #64748B;
}
@keyframes typing-bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

/* ── ALWAYS FIXED & FLOATING BOTTOM INPUT BAR ───────────────────────────── */
.chat-floating-bar-wrap {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 35;
  pointer-events: none;
  display: flex;
  justify-content: center;
}
.chat-floating-bar-inner {
  pointer-events: auto;
  width: 100%;
  max-width: 920px;
  padding: 8px 18px 12px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0) 0%, rgba(255, 255, 255, 0.94) 28%, #FFFFFF 100%);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  display: flex;
  flex-direction: column;
  align-items: center;
}
@media (max-width: 600px) {
  .chat-floating-bar-inner {
    padding: 6px 12px 10px;
  }
}

.chat-input-pill {
  width: 100%;
  background: #FFFFFF;
  border: 1.5px solid #CBD5E1;
  border-radius: 26px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.03);
  padding: 4px 6px 4px 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.chat-input-pill--focused {
  border-color: #C0392B !important;
  box-shadow: 0 4px 22px rgba(192, 57, 43, 0.18), 0 0 0 3px rgba(192, 57, 43, 0.08) !important;
}
.chat-input-pill--disabled {
  opacity: 0.6;
  pointer-events: none;
}

/* Plain textarea without any outline artifacts */
.chat-plain-input {
  flex: 1;
}
.chat-plain-input :deep(.v-field) {
  padding: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}
.chat-plain-input :deep(.v-field__outline) {
  display: none !important;
}
.chat-plain-input :deep(.v-field__input) {
  padding: 8px 2px !important;
  min-height: 24px !important;
  font-size: 14px !important;
  line-height: 1.45 !important;
  color: #1E293B !important;
}
.chat-plain-input :deep(textarea) {
  scrollbar-width: none !important;
  -ms-overflow-style: none !important;
  resize: none !important;
}
.chat-plain-input :deep(textarea::-webkit-scrollbar) {
  display: none !important;
}

/* Send Button */
.chat-send-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: #E2E8F0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  cursor: not-allowed;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.chat-send-btn--active {
  background: linear-gradient(135deg, #C0392B 0%, #D32F2F 100%);
  cursor: pointer;
  box-shadow: 0 3px 12px rgba(192, 57, 43, 0.35);
}
.chat-send-btn--active:hover {
  transform: scale(1.06);
  box-shadow: 0 5px 16px rgba(192, 57, 43, 0.5);
}
.chat-send-btn--active:active {
  transform: scale(0.96);
}

.chat-recaptcha-footer {
  font-size: 10px;
  color: #94A3B8;
  text-align: center;
  padding-top: 4px;
  user-select: none;
}
.chat-recaptcha-footer a {
  color: #64748B;
  text-decoration: none;
}
.chat-recaptcha-footer a:hover {
  text-decoration: underline;
}

/* ── Tour Button Anchor (Safely Above Floating Bar) ─────────────────────── */
.chat-tour-anchor :deep(.tour-fab) {
  bottom: 84px !important;
  right: 20px !important;
  z-index: 45 !important;
}
@media (max-width: 600px) {
  .chat-tour-anchor :deep(.tour-fab) {
    bottom: 78px !important;
    right: 12px !important;
  }
}

/* ── Feedback Dialog ────────────────────────────────────────────────────── */
.feedback-popup {
  border-radius: 16px !important;
  overflow: hidden;
}
.feedback-emoji-large {
  font-size: 40px;
  line-height: 1;
}
.rating-label {
  min-height: 20px;
  transition: color 0.2s;
}
</style>
