<template>
  <Head title="Mulai Chat"></Head>
  <VApp>
    <VContainer fluid class="pa-0 pt-16 chat-app">
      <VRow no-gutters class="fill-height">
        <VCol cols="12">
          <!-- Chat Header -->
          <VAppBar
            density="comfortable"
            class="chat-header"
            :style="{
              background:
                'linear-gradient(135deg, #E9A5F1 0%, #C68EFD 50%, #8F87F1 100%)',
              boxShadow: '0 4px 12px rgba(233, 165, 241, 0.3)',
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
              <div class="d-flex align-center">
                <VIcon left color="white" size="28">mdi-robot</VIcon>
                <div class="ml-2">
                  <div class="font-weight-bold">
                    SALMA AI — Asisten Samsat Lamongan
                  </div>
                  <div
                    v-if="props.wajibPajakData"
                    class="text-caption opacity-90"
                  >
                    {{ props.wajibPajakData.nama }} ({{
                      props.wajibPajakData.nopol
                    }})
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

          <!-- Chat Container -->
          <VCard
            class="chat-container mx-auto elevation-12"
            max-width="1000"
            height="calc(100vh - 160px)"
            :style="{
              borderRadius: '24px',
              overflow: 'hidden',
              background: 'white',
              border: '1px solid rgba(0,0,0,0.05)',
            }"
          >
            <!-- Welcome Section -->
            <div
              v-if="!chatSession || messages.length === 0"
              class="welcome-section pa-6 text-center"
              :style="{
                background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)',
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center',
              }"
            >
              <VAvatar
                size="80"
                class="mb-4 mx-auto"
                :style="{
                  background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                  boxShadow: '0 8px 24px rgba(233, 165, 241, 0.4)',
                }"
              >
                <VIcon size="40" color="white">mdi-robot</VIcon>
              </VAvatar>

              <h2 class="text-h5 mb-3 gradient-text font-weight-bold">
                Selamat Datang di Layanan AI
              </h2>
              <h3 class="text-h6 mb-3 text-primary">Samsat Lamongan</h3>
              <p
                class="text-body-2 text-grey-700 mb-4 mx-auto"
                style="max-width: 450px; line-height: 1.6"
              >
                Saya siap membantu Anda dengan informasi seputar pajak
                kendaraan, STNK, dan layanan Samsat lainnya 24/7.
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
                        border:
                          '1.5px solid ' + getSuggestionColor(index) + '30',
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
                  background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                  borderRadius: '20px',
                  textTransform: 'none',
                  padding: '10px 28px',
                  boxShadow: '0 6px 16px rgba(233, 165, 241, 0.4)',
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
            <div v-else class="chat-messages-area">
              <!-- Messages Container -->
              <div
                ref="messagesContainer"
                class="messages-scroll pa-3"
                :style="{
                  height: 'calc(100vh - 260px)',
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
                          background:
                            'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                          borderRadius: '18px 18px 4px 18px',
                          boxShadow: '0 3px 10px rgba(233, 165, 241, 0.3)',
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
                    <VCol cols="auto" class="max-width-80">
                      <div class="d-flex align-start">
                        <VAvatar
                          size="32"
                          class="me-2 mt-1 flex-shrink-0"
                          :style="{
                            background:
                              'linear-gradient(135deg, #8F87F1, #C68EFD)',
                          }"
                        >
                          <VIcon color="white" size="16">mdi-robot</VIcon>
                        </VAvatar>
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
                          <div
                            v-if="message.type === 'feedback'"
                            class="feedback-form"
                          >
                            <div class="text-center mb-4">
                              <div style="font-size: 32px; margin-bottom: 8px">
                                ❤️
                              </div>
                              <h3 class="text-primary mb-2">Terima Kasih!</h3>
                              <p class="text-grey-600 text-body-2">
                                Mohon berikan penilaian Anda terhadap layanan
                                SALMA AI untuk membantu kami memberikan
                                pelayanan yang lebih baik.
                              </p>
                            </div>

                            <div class="text-center mb-4">
                              <p
                                class="text-subtitle-2 font-weight-medium mb-3"
                              >
                                Berikan Rating Layanan:
                              </p>
                              <VRating
                                v-model="feedbackRating"
                                :size="32"
                                color="amber"
                                active-color="amber"
                                hover
                                half-increments
                                clearable
                                @update:model-value="onRatingChange"
                              />
                              <p class="text-caption text-grey-600 mt-2">
                                {{ getRatingLabel(feedbackRating) }}
                              </p>
                            </div>

                            <div class="mb-4">
                              <VTextarea
                                v-model="feedbackText"
                                placeholder="Bagikan pengalaman Anda menggunakan layanan SALMA AI..."
                                rows="3"
                                variant="outlined"
                                density="compact"
                                no-resize
                              />
                            </div>

                            <div class="text-center">
                              <VBtn
                                @click="submitFeedback"
                                :disabled="
                                  feedbackRating === 0 || isSubmittingFeedback
                                "
                                :loading="isSubmittingFeedback"
                                color="primary"
                                variant="flat"
                                size="large"
                                prepend-icon="mdi-send"
                                class="px-6"
                              >
                                Kirim Feedback
                              </VBtn>
                            </div>
                          </div>

                          <!-- Final Thank You Message -->
                          <div
                            v-else-if="message.type === 'final'"
                            class="final-message text-center"
                          >
                            <div
                              style="
                                font-size: 40px;
                                color: #4caf50;
                                margin-bottom: 12px;
                              "
                            >
                              ✅
                            </div>
                            <h3 class="text-success mb-2">Terima Kasih!</h3>
                            <p class="text-grey-600 text-body-2 mb-3">
                              Feedback Anda telah tersimpan. Masukan Anda sangat
                              berharga untuk meningkatkan kualitas layanan kami.
                            </p>
                            <p class="text-grey-600 text-body-2">
                              Anda akan dialihkan ke halaman utama dalam 5
                              detik...
                            </p>
                          </div>

                          <!-- Regular Assistant Message -->
                          <div
                            v-else
                            class="assistant-content text-grey-800 text-body-2"
                            style="line-height: 1.5"
                            v-html="getFormattedContent(message)"
                          ></div>

                          <div class="text-left mt-1">
                            <small
                              class="text-grey-500"
                              style="font-size: 10px"
                            >
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
                      <VAvatar
                        size="32"
                        class="me-2"
                        :style="{
                          background:
                            'linear-gradient(135deg, #8F87F1, #C68EFD)',
                        }"
                      >
                        <VIcon color="white" size="16">mdi-robot</VIcon>
                      </VAvatar>
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
                          <span
                            class="typing-text ms-2 text-grey-600 text-caption"
                          >
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
                      class="message-input"
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
                        background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                        borderRadius: '50%',
                        minWidth: '48px',
                        width: '48px',
                        height: '48px',
                        boxShadow: '0 4px 12px rgba(233, 165, 241, 0.3)',
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
        </VCol>
      </VRow>
    </VContainer>

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
  </VApp>
</template>

<script setup>
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
    }
  } catch (error) {
    console.error('Error starting new chat:', error);
    showErrorMessage('Gagal memulai chat baru. Silakan coba lagi.');
  }
};

// Send message
const sendMessage = async (messageText = null, isContext = false) => {
  console.log('=== sendMessage CALLED ===');
  console.log('messageText:', messageText);
  console.log('isContext:', isContext);

  const text = messageText || currentMessage.value.trim();
  console.log('text to send:', text);

  if (!text || isLoading.value) {
    console.log('=== EARLY RETURN - no text or loading ===');
    return;
  }

  if (!messageText) {
    currentMessage.value = '';
  }

  // Initialize chat if needed
  if (!chatSession.value) {
    await initializeChat();
  }

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
        console.log('=== AI RESPONSE SUCCESS - NOT CONTEXT ===');
        const assistantMessage = {
          ...response.data.assistant_message,
          id: `assistant_${Date.now()}_${Math.random()
            .toString(36)
            .substr(2, 9)}`,
        };
        messages.value.push(assistantMessage);
        await scrollToBottom();

        // Show follow-up message after AI response
        console.log('=== ABOUT TO CALL showFollowUpMessage ===');
        console.log('isContext:', isContext);

        // Try immediate call first
        console.log('=== CALLING showFollowUpMessage IMMEDIATELY ===');
        showFollowUpMessage();

        // Also try with timeout
        setTimeout(() => {
          console.log(
            '=== TIMEOUT EXECUTING - CALLING showFollowUpMessage AGAIN ===',
          );
          showFollowUpMessage();
        }, 1000);
      } else {
        console.log('=== SKIPPING FOLLOW-UP - IS CONTEXT MESSAGE ===');
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
        'Ada lagi yang bisa SALMA bantu? <button data-action="end-chat" style="margin-left:8px;padding:6px 10px;border-radius:10px;border:1px solid #e0e0e0;background:#f7f7f7;cursor:pointer;">Akhiri Chat</button>',
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
  // Add feedback form as a message
  showFeedbackMessage();
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

      // Add final thank you message
      const finalMessage = {
        id: `final_${Date.now()}`,
        role: 'assistant',
        content: 'Thank you message will be displayed here',
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

  // Simple formatting that works reliably
  const formatted = content
    // Convert URLs to clickable links
    .replace(
      /(https?:\/\/[^\s]+)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #1976d2; text-decoration: underline;">$1</a>',
    )
    // Convert bold text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
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
  const content = message.content || '';

  // Same simple formatting as formatMessage for consistency
  return content
    .replace(
      /(https?:\/\/[^\s]+)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #1976d2; text-decoration: underline;">$1</a>',
    )
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')
    .replace(/^(\d+\.\s)/gm, '<br>$1')
    .replace(/^[-*]\s/gm, '<br>• ');
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
  console.log(
    '[Chat Index] mounted - build active at',
    new Date().toISOString(),
  );
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
</script>

<style scoped>
.chat-app {
  background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
  min-height: 100vh;
}

.chat-header {
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(233, 165, 241, 0.3) !important;
}

.chat-container {
  backdrop-filter: blur(20px);
  transition: all 0.3s ease;
}

/* Lightbox uses vue-easy-lightbox styles */

.gradient-text {
  background: linear-gradient(135deg, #e9a5f1, #c68efd);
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
  box-shadow: 0 8px 24px rgba(233, 165, 241, 0.6) !important;
}

.message-input >>> .v-field {
  border-radius: 20px !important;
  border: 1px solid #e0e0e0 !important;
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

/* Generic images inside assistant content */
.assistant-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

.message-input >>> .v-field:focus-within {
  border-color: #e9a5f1 !important;
  box-shadow: 0 0 0 2px rgba(233, 165, 241, 0.2) !important;
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

.user-message:hover,
.assistant-message:hover {
  transform: translateY(-1px);
}

/* Typing indicator animation */
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
  background-color: #c68efd;
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
    transform: scale(0.8);
    opacity: 0.6;
  }
  40% {
    transform: scale(1.2);
    opacity: 1;
  }
}

/* Scrollbar styling */
.messages-scroll::-webkit-scrollbar {
  width: 6px;
}

.messages-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.messages-scroll::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #e9a5f1, #c68efd);
  border-radius: 10px;
}

.messages-scroll::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #c68efd, #8f87f1);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .chat-container {
    margin: 0 !important;
    border-radius: 0 !important;
    height: calc(100vh - 100px) !important;
    max-width: 100% !important;
  }

  .welcome-section {
    padding: 20px 16px !important;
  }

  .gradient-text {
    font-size: 1.5rem !important;
  }

  .max-width-75 {
    max-width: 85%;
  }

  .max-width-80 {
    max-width: 90%;
  }

  .suggestion-card {
    min-height: 70px !important;
  }

  .max-width-90 {
    max-width: 95%;
  }
}

/* Feedback and follow-up components */
.feedback-card {
  transition: all 0.3s ease;
}

.feedback-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
}

.star-btn {
  transition: all 0.2s ease;
}

.star-btn:hover {
  transform: scale(1.1);
}

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

.max-width-90 {
  max-width: 90%;
}
</style>
