<template>
  <VApp>
    <VContainer fluid class="pa-0 chat-app">
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
              <VIcon left color="white" size="28">mdi-robot</VIcon>
              <span class="font-weight-bold"
                >SALMA AI — Asisten Samsat Lamongan</span
              >
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
            class="chat-container mx-auto elevation-8"
            max-width="900"
            height="calc(100vh - 200px)"
            style="border-radius: 20px; overflow: hidden"
          >
            <!-- Welcome Section -->
            <div
              v-if="!chatSession || messages.length === 0"
              class="welcome-section pa-8 text-center"
              :style="{
                background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)',
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center',
              }"
            >
              <VAvatar
                size="100"
                class="mb-6 mx-auto"
                :style="{
                  background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                  boxShadow: '0 8px 24px rgba(233, 165, 241, 0.4)',
                }"
              >
                <VIcon size="50" color="white">mdi-robot</VIcon>
              </VAvatar>

              <h2 class="text-h4 mb-4 gradient-text font-weight-bold">
                Selamat Datang di Layanan AI
              </h2>
              <h3 class="text-h5 mb-4 text-primary">Samsat Lamongan</h3>
              <p
                class="text-body-1 text-grey-700 mb-6 mx-auto"
                style="max-width: 500px"
              >
                Saya siap membantu Anda dengan informasi seputar pajak
                kendaraan, STNK, dan layanan Samsat lainnya 24/7.
              </p>

              <!-- Quick Suggestions -->
              <div class="mb-6">
                <h4 class="text-h6 mb-4 text-grey-800">Pertanyaan Populer:</h4>
                <VRow justify="center" class="ma-0">
                  <VCol
                    v-for="(suggestion, index) in quickSuggestions"
                    :key="suggestion"
                    cols="12"
                    sm="6"
                    md="6"
                    class="pa-2"
                  >
                    <VCard
                      @click="sendQuickMessage(suggestion)"
                      class="suggestion-card pa-4 text-center"
                      :style="{
                        cursor: 'pointer',
                        background:
                          'linear-gradient(135deg, ' +
                          getSuggestionColor(index) +
                          '20, ' +
                          getSuggestionColor(index) +
                          '10)',
                        border: '2px solid ' + getSuggestionColor(index) + '40',
                        borderRadius: '15px',
                        transition: 'all 0.3s ease',
                      }"
                      hover
                      elevation="2"
                    >
                      <VIcon
                        :color="getSuggestionColor(index)"
                        size="32"
                        class="mb-2"
                      >
                        {{ getSuggestionIcon(index) }}
                      </VIcon>
                      <p
                        class="text-body-2 font-weight-medium mb-0"
                        :style="{
                          color: getSuggestionColor(index),
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
                  borderRadius: '25px',
                  textTransform: 'none',
                  padding: '12px 32px',
                  boxShadow: '0 8px 20px rgba(233, 165, 241, 0.4)',
                }"
                color="white"
                class="start-chat-btn text-white font-weight-bold"
                size="large"
                elevation="0"
              >
                <VIcon left>mdi-chat</VIcon>
                Mulai Chat
              </VBtn>
            </div>

            <!-- Messages Area -->
            <div v-else>
              <!-- Messages Container -->
              <div
                ref="messagesContainer"
                class="messages-scroll pa-4"
                :style="{
                  height: 'calc(100vh - 340px)',
                  overflowY: 'auto',
                  background: 'linear-gradient(to bottom, #f8f9fa, #ffffff)',
                }"
              >
                <!-- Message Items -->
                <div
                  v-for="message in messages"
                  :key="message.id || message.sent_at"
                  class="mb-4"
                >
                  <!-- User Message -->
                  <VRow
                    v-if="message.role === 'user'"
                    justify="end"
                    no-gutters
                  >
                    <VCol cols="auto" class="max-width-70">
                      <VCard
                        class="user-message pa-3"
                        :style="{
                          background:
                            'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                          borderRadius: '20px 20px 5px 20px',
                          boxShadow: '0 4px 12px rgba(233, 165, 241, 0.3)',
                        }"
                        elevation="2"
                      >
                        <div class="text-white font-weight-medium">
                          {{ message.content }}
                        </div>
                        <div class="text-right mt-1">
                          <small class="text-white-70">
                            {{ formatTime(message.sent_at) }}
                          </small>
                        </div>
                      </VCard>
                    </VCol>
                  </VRow>

                  <!-- Assistant Message -->
                  <VRow v-else justify="start" no-gutters>
                    <VCol cols="auto" class="max-width-70">
                      <div class="d-flex align-start">
                        <VAvatar
                          size="35"
                          class="me-3 mt-1"
                          :style="{
                            background:
                              'linear-gradient(135deg, #8F87F1, #C68EFD)',
                          }"
                        >
                          <VIcon color="white" size="20">mdi-robot</VIcon>
                        </VAvatar>
                        <div style="flex: 1">
                          <VCard
                            class="assistant-message pa-3"
                            style="
                              background: white;
                              border-radius: 20px 20px 20px 5px;
                              box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                              border: 1px solid #e0e0e0;
                            "
                            elevation="1"
                          >
                            <div
                              class="assistant-content text-grey-800"
                              v-html="formatMessage(message.content)"
                            ></div>
                            <div class="text-left mt-2">
                              <small class="text-grey-500">
                                {{ formatTime(message.sent_at) }}
                              </small>
                            </div>
                          </VCard>
                        </div>
                      </div>
                    </VCol>
                  </VRow>
                </div>

                <!-- Typing Indicator -->
                <VRow v-if="isTyping" justify="start" no-gutters>
                  <VCol cols="auto">
                    <div class="d-flex align-center">
                      <VAvatar
                        size="35"
                        class="me-3"
                        :style="{
                          background:
                            'linear-gradient(135deg, #8F87F1, #C68EFD)',
                        }"
                      >
                        <VIcon color="white" size="20">mdi-robot</VIcon>
                      </VAvatar>
                      <VCard
                        class="pa-3"
                        style="border-radius: 20px; background: white"
                      >
                        <div class="typing-indicator">
                          <span></span>
                          <span></span>
                          <span></span>
                          <span class="ms-2 text-grey-600"
                            >AI sedang mengetik...</span
                          >
                        </div>
                      </VCard>
                    </div>
                  </VCol>
                </VRow>
              </div>

              <!-- Input Area -->
              <VDivider></VDivider>
              <div class="pa-4" style="background: white">
                <VRow no-gutters align="center">
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
                      :style="{
                        borderRadius: '25px',
                      }"
                    ></VTextarea>
                  </VCol>
                  <VCol cols="auto" class="ml-3">
                    <VBtn
                      @click="sendMessage"
                      :disabled="!currentMessage.trim() || isLoading"
                      :style="{
                        background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                        borderRadius: '50%',
                        minWidth: '56px',
                        width: '56px',
                        height: '56px',
                      }"
                      class="text-white"
                      elevation="2"
                    >
                      <VIcon v-if="!isLoading">mdi-send</VIcon>
                      <VProgressCircular
                        v-else
                        indeterminate
                        size="20"
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
import { router } from '@inertiajs/vue3';
import axios from 'axios';

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
const sendMessage = async () => {
  const messageText = currentMessage.value.trim();
  if (!messageText || isLoading.value) return;

  currentMessage.value = '';

  // Initialize chat if needed
  if (!chatSession.value) {
    await initializeChat();
  }

  // Add user message to UI immediately
  const userMessage = {
    role: 'user',
    content: messageText,
    sent_at: new Date().toISOString(),
    id: `user_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
  };
  messages.value.push(userMessage);
  await scrollToBottom();

  try {
    isLoading.value = true;
    isTyping.value = true;

    const response = await axios.post('/api/chat/message', {
      session_id: getSessionId(),
      message: messageText,
    });

    isTyping.value = false;

    if (response.data.success) {
      const assistantMessage = {
        ...response.data.assistant_message,
        id:
          `assistant_${
            Date.now()
          }_${
            Math.random().toString(36).substr(2, 9)}`,
      };
      messages.value.push(assistantMessage);
      await scrollToBottom();
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
}

.gradient-text {
  background: linear-gradient(135deg, #e9a5f1, #c68efd);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.suggestion-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.start-chat-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(233, 165, 241, 0.6) !important;
}

.message-input >>> .v-field {
  border-radius: 25px !important;
}

.text-white-70 {
  color: rgba(255, 255, 255, 0.7) !important;
}

.max-width-70 {
  max-width: 70%;
}

/* Typing indicator animation */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #c68efd;
  animation: typing-bounce 1.4s infinite ease-in-out both;
}

.typing-indicator span:nth-child(1) {
  animation-delay: -0.32s;
}

.typing-indicator span:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes typing-bounce {
  0%,
  80%,
  100% {
    transform: scale(0.8);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Scrollbar styling */
.messages-scroll::-webkit-scrollbar {
  width: 6px;
}

.messages-scroll::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.messages-scroll::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #e9a5f1, #c68efd);
  border-radius: 10px;
}

.messages-scroll::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #c68efd, #8f87f1);
}

/* Mobile responsive */
@media (max-width: 600px) {
  .chat-container {
    margin: 0 !important;
    border-radius: 0 !important;
    height: calc(100vh - 120px) !important;
  }

  .welcome-section {
    padding: 24px 16px !important;
  }

  .gradient-text {
    font-size: 1.8rem !important;
  }

  .max-width-70 {
    max-width: 85%;
  }
}
</style>
