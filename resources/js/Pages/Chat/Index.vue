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
                          <div
                            class="assistant-content text-grey-800 text-body-2"
                            style="line-height: 1.5"
                            v-html="formatMessage(message.content)"
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
  ref, onMounted, nextTick, watch,
} from 'vue';
import { router, Head } from '@inertiajs/vue3';
import axios from 'axios';

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
  const text = messageText || currentMessage.value.trim();
  if (!text || isLoading.value) return;

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
        const assistantMessage = {
          ...response.data.assistant_message,
          id: `assistant_${Date.now()}_${Math.random()
            .toString(36)
            .substr(2, 9)}`,
        };
        messages.value.push(assistantMessage);
        await scrollToBottom();
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

// Utility functions
const formatTime = (timestamp) => {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatMessage = (content) => {
  if (!content) return '';
  return content
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')
    .replace(/^(\d+\.\s)/gm, '<br>$1');
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

// Initialize on mount
onMounted(() => {
  initializeChat();

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

// Watch for new messages and scroll
watch(
  messages,
  () => {
    nextTick(() => {
      scrollToBottom();
    });
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
}
</style>
