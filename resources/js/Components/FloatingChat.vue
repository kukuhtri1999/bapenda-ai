<template>
  <div class="floating-chat">
    <!-- Chat Button -->
    <VBtn
      v-if="!isChatOpen"
      @click="toggleChat"
      class="floating-chat-button"
      :style="{
        background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
        borderRadius: '50%',
        width: '64px',
        height: '64px',
        boxShadow: '0 8px 24px rgba(233, 165, 241, 0.4)',
        border: '3px solid white',
      }"
      elevation="0"
      size="x-large"
      icon
    >
      <VIcon size="32" color="white">mdi-robot</VIcon>
      <VTooltip activator="parent" location="left">
        <div class="pa-2">
          <div class="font-weight-bold">AI Customer Service</div>
          <div class="text-caption">Samsat Lamongan</div>
        </div>
      </VTooltip>
    </VBtn>

    <!-- Chat Widget -->
    <VCard
      v-show="isChatOpen"
      class="floating-chat-widget elevation-12"
      :style="{
        borderRadius: '24px',
        overflow: 'hidden',
        background: 'white',
        border: '1px solid rgba(0,0,0,0.05)',
      }"
    >
      <!-- Chat Header -->
      <div
        class="chat-widget-header pa-4"
        :style="{
          background:
            'linear-gradient(135deg, #E9A5F1 0%, #C68EFD 50%, #8F87F1 100%)',
          borderRadius: '24px 24px 0 0',
        }"
      >
        <div class="d-flex align-center">
          <VAvatar
            size="40"
            class="me-3"
            :style="{
              background: 'rgba(255,255,255,0.2)',
              backdropFilter: 'blur(10px)',
            }"
          >
            <VIcon size="22" color="white">mdi-robot</VIcon>
          </VAvatar>
          <div class="flex-grow-1">
            <div class="text-white font-weight-bold text-body-1">
              AI Assistant
            </div>
            <div class="text-white text-caption" style="opacity: 0.9">
              Samsat Lamongan • Online
            </div>
          </div>
          <div class="d-flex">
            <VBtn
              icon="mdi-minus"
              variant="text"
              color="white"
              @click="minimizeChat"
              size="small"
              class="me-1"
              :style="{ borderRadius: '12px' }"
            ></VBtn>
            <VBtn
              icon="mdi-close"
              variant="text"
              color="white"
              @click="closeChat"
              size="small"
              :style="{ borderRadius: '12px' }"
            ></VBtn>
          </div>
        </div>
      </div>

      <!-- Chat Messages -->
      <div ref="messagesContainer" class="messages-container">
        <!-- Welcome Message -->
        <div
          v-if="messages.length === 0"
          class="welcome-message pa-6 text-center"
          :style="{
            background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)',
            minHeight: '380px',
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
              boxShadow: '0 8px 20px rgba(233, 165, 241, 0.3)',
            }"
          >
            <VIcon size="40" color="white">mdi-robot</VIcon>
          </VAvatar>

          <h3 class="text-h6 mb-2 gradient-text font-weight-bold">Halo! 👋</h3>
          <p class="text-body-2 text-grey-700 mb-4" style="line-height: 1.6">
            Saya siap membantu Anda dengan informasi layanan Samsat Lamongan
          </p>

          <!-- Quick Suggestions -->
          <div class="mb-4">
            <p class="text-caption text-grey-600 mb-3">Pertanyaan populer:</p>
            <div class="d-flex flex-column gap-2">
              <VChip
                v-for="(suggestion, index) in quickSuggestions"
                :key="suggestion"
                @click="sendQuickMessage(suggestion)"
                class="suggestion-chip"
                :style="{
                  background:
                    'linear-gradient(135deg, ' +
                    getSuggestionColor(index) +
                    '20, ' +
                    getSuggestionColor(index) +
                    '10)',
                  border: '1px solid ' + getSuggestionColor(index) + '40',
                  borderRadius: '20px',
                  cursor: 'pointer',
                  fontSize: '12px',
                  height: '32px',
                }"
                variant="flat"
                size="small"
              >
                <VIcon :color="getSuggestionColor(index)" size="16" start>
                  {{ getSuggestionIcon(index) }}
                </VIcon>
                <span
                  :style="{
                    color: getSuggestionColor(index),
                  }"
                >
                  {{ suggestion }}
                </span>
              </VChip>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="mb-3">
            <VBtn
              @click="goToPkbCheck"
              :style="{
                background: 'linear-gradient(135deg, #4CAF50, #45a049)',
                borderRadius: '20px',
                textTransform: 'none',
                marginBottom: '8px',
                width: '100%',
                boxShadow: '0 4px 12px rgba(76, 175, 80, 0.3)',
              }"
              color="white"
              class="text-white font-weight-medium"
              size="small"
              elevation="0"
            >
              <VIcon left size="16">mdi-car</VIcon>
              Cek PKB Kendaraan
            </VBtn>
          </div>
        </div>

        <!-- Messages -->
        <div v-else class="pa-4" style="padding-bottom: 0">
          <div
            v-for="(message, index) in messages"
            :key="message.id || index"
            class="message-item mb-4"
          >
            <!-- User Message -->
            <div
              v-if="message.role === 'user'"
              class="user-message d-flex justify-end"
            >
              <VCard
                class="user-bubble"
                :style="{
                  background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
                  borderRadius: '20px 20px 6px 20px',
                  maxWidth: '280px',
                  boxShadow: '0 4px 12px rgba(233, 165, 241, 0.3)',
                }"
                elevation="0"
              >
                <VCardText class="pa-3">
                  <p
                    class="text-body-2 text-white mb-0"
                    style="line-height: 1.4"
                  >
                    {{ message.content }}
                  </p>
                  <div class="text-right mt-1">
                    <small
                      class="text-white"
                      style="opacity: 0.8; font-size: 10px"
                    >
                      {{ formatTime(message.sent_at) }}
                    </small>
                  </div>
                </VCardText>
              </VCard>
            </div>

            <!-- Assistant Message -->
            <div
              v-else-if="message.role === 'assistant'"
              class="assistant-message"
            >
              <div class="d-flex align-start">
                <VAvatar
                  size="32"
                  class="me-3 mt-1"
                  :style="{
                    background: 'linear-gradient(135deg, #8F87F1, #C68EFD)',
                    flexShrink: 0,
                  }"
                >
                  <VIcon size="16" color="white">mdi-robot</VIcon>
                </VAvatar>
                <VCard
                  class="assistant-bubble flex-grow-1"
                  :style="{
                    background: 'white',
                    borderRadius: '20px 20px 20px 6px',
                    maxWidth: '280px',
                    border: '1px solid #e0e0e0',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                  }"
                  elevation="0"
                >
                  <VCardText class="pa-3">
                    <div
                      class="text-body-2 text-grey-800 mb-0"
                      style="line-height: 1.5"
                      v-html="formatMessage(message.content)"
                    ></div>
                    <div class="text-left mt-1">
                      <small class="text-grey-800" style="font-size: 10px">
                        {{ formatTime(message.sent_at) }}
                      </small>
                    </div>
                  </VCardText>
                </VCard>
              </div>
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="isTyping" class="assistant-message">
            <div class="d-flex align-start">
              <VAvatar
                size="32"
                class="me-3"
                :style="{
                  background: 'linear-gradient(135deg, #8F87F1, #C68EFD)',
                }"
              >
                <VIcon size="16" color="white">mdi-robot</VIcon>
              </VAvatar>
              <VCard
                :style="{
                  background: 'white',
                  borderRadius: '20px 20px 20px 6px',
                  border: '1px solid #e0e0e0',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                }"
                elevation="0"
              >
                <VCardText class="pa-3">
                  <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="ms-2 text-grey-600 text-caption"
                      >sedang mengetik...</span
                    >
                  </div>
                </VCardText>
              </VCard>
            </div>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <VDivider style="border-color: rgba(0, 0, 0, 0.05)"></VDivider>
      <div class="pa-2" style="background: white; border-radius: 0 0 24px 24px">
        <VRow no-gutters align="center" class="gap-3">
          <VCol>
            <VTextarea
              v-model="currentMessage"
              placeholder="Ketik pertanyaan Anda..."
              variant="outlined"
              density="compact"
              rows="1"
              auto-grow
              max-rows="3"
              hide-details
              @keydown.enter.prevent="handleEnterKey"
              :disabled="isLoading"
              :style="{
                borderRadius: '20px',
              }"
              class="message-input"
            ></VTextarea>
          </VCol>
          <VCol cols="auto">
            <VBtn
              @click="() => sendMessage()"
              :disabled="!currentMessage.trim() || isLoading"
              :loading="isLoading"
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
    </VCard>

    <!-- Minimized State -->
    <VCard
      v-show="isMinimized"
      class="floating-chat-minimized"
      @click="maximizeChat"
      :style="{
        background: 'linear-gradient(135deg, #E9A5F1, #C68EFD)',
        borderRadius: '16px',
        cursor: 'pointer',
        transition: 'all 0.3s ease',
        border: '2px solid white',
        boxShadow: '0 8px 20px rgba(233, 165, 241, 0.4)',
      }"
      elevation="0"
    >
      <VCardText class="pa-3 text-center">
        <VIcon color="white" size="24" class="mb-1">mdi-robot</VIcon>
        <p class="text-caption text-white mb-0 font-weight-medium">Chat</p>
        <VBadge
          v-if="unreadCount > 0"
          :content="unreadCount"
          color="error"
          class="badge-position"
          :style="{
            position: 'absolute',
            top: '-8px',
            right: '-8px',
          }"
        >
        </VBadge>
      </VCardText>
    </VCard>

    <!-- Lightbox -->
    <VueEasyLightbox
      :visible="lightboxVisible"
      :imgs="lightboxImages"
      :index="lightboxIndex"
      @hide="lightboxVisible = false"
    />
  </div>
</template>

<script setup>
import {
  ref, onMounted, onBeforeUnmount, nextTick, watch,
} from 'vue';
import axios from 'axios';
import VueEasyLightbox from 'vue-easy-lightbox';

// Props
const props = defineProps({
  autoOpen: {
    type: Boolean,
    default: false,
  },
});

// Reactive data
const isChatOpen = ref(false);
const isMinimized = ref(false);
const currentMessage = ref('');
const messages = ref([]);
const chatSession = ref(null);
const isLoading = ref(false);
const isTyping = ref(false);
const unreadCount = ref(0);
const messagesContainer = ref(null);
const lightboxVisible = ref(false);
const lightboxImages = ref([]);
const lightboxIndex = ref(0);
let onKeydown;

// Quick suggestions
const quickSuggestions = ref([
  'Cara bayar pajak',
  'Lokasi Samsat',
  'Syarat STNK',
  'Cek denda pajak',
]);

// Utility functions
const getSuggestionColor = (index) => {
  const colors = ['#E9A5F1', '#C68EFD', '#8F87F1'];
  return colors[index % colors.length];
};

const getSuggestionIcon = (index) => {
  const icons = [
    'mdi-credit-card',
    'mdi-map-marker',
    'mdi-card-account-details',
    'mdi-alert-circle',
  ];
  return icons[index % icons.length];
};

const formatTime = (timestamp) => {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Generate or get session ID
const getSessionId = () => {
  let sessionId = localStorage.getItem('chat_widget_session_id');
  if (!sessionId) {
    sessionId = `widget_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    localStorage.setItem('chat_widget_session_id', sessionId);
  }
  return sessionId;
};

// Initialize chat
const initializeChat = async () => {
  try {
    const sessionId = getSessionId();

    const response = await axios.post('/api/chat/start', {
      session_id: sessionId,
    });

    if (response.data.success) {
      chatSession.value = response.data.chat;
      messages.value = response.data.chat.messages || [];
      await scrollToBottom();
    }
  } catch (error) {
    console.error('Error initializing chat:', error);
  }
};

// Toggle chat
const toggleChat = async () => {
  if (!isChatOpen.value) {
    // Check if user has filled wajib pajak data
    try {
      const response = await fetch('/api/check-wajib-pajak-session', {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN':
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content') || '',
        },
      });

      const data = await response.json();

      if (!data.hasSession) {
        // Show alert that user needs to fill data first
        alert(
          'Silakan isi data wajib pajak terlebih dahulu untuk menggunakan chat AI.',
        );

        // Redirect to wajib pajak form
        window.location.href = '/wajib-pajak';
        return;
      }
    } catch (error) {
      console.error('Error checking session:', error);
      // If error, redirect to form to be safe
      window.location.href = '/wajib-pajak';
      return;
    }

    isChatOpen.value = true;
    isMinimized.value = false;
    unreadCount.value = 0;

    if (!chatSession.value) {
      await initializeChat();
    }

    await scrollToBottom();
  } else {
    closeChat();
  }
};

// Close chat
const closeChat = () => {
  isChatOpen.value = false;
  isMinimized.value = false;
};

// Minimize chat
const minimizeChat = () => {
  isChatOpen.value = false;
  isMinimized.value = true;
};

// Maximize chat
const maximizeChat = () => {
  isChatOpen.value = true;
  isMinimized.value = false;
  unreadCount.value = 0;
  scrollToBottom();
};

// Send message
const sendMessage = async () => {
  if (!currentMessage.value.trim() || isLoading.value) return;

  const messageText = currentMessage.value.trim();
  currentMessage.value = '';

  // Add user message to UI immediately
  const userMessage = {
    role: 'user',
    content: messageText,
    sent_at: new Date().toISOString(),
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
      messages.value.push(response.data.assistant_message);

      // If chat is minimized, show notification
      if (isMinimized.value) {
        unreadCount.value++;
      }

      await scrollToBottom();
    }
  } catch (error) {
    isTyping.value = false;
    console.error('Error sending message:', error);

    const errorMsg = {
      role: 'assistant',
      content: 'Maaf, terjadi kesalahan. Silakan coba lagi.',
      sent_at: new Date().toISOString(),
    };
    messages.value.push(errorMsg);
    await scrollToBottom();
  } finally {
    isLoading.value = false;
  }
};

// Send quick message
const sendQuickMessage = (message) => {
  currentMessage.value = message;
  sendMessage();
};

// Navigate to PKB check page
const goToPkbCheck = () => {
  window.location.href = '/cek-pkb';
};

// Handle Enter key
const handleEnterKey = (event) => {
  if (!event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
};

// Scroll to bottom
const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

// Format message content
const formatMessage = (content) => {
  if (!content) return '';
  const looksHtml = /<\w+[\s\S]*>/m.test(content);
  if (looksHtml) return content;

  return (
    content
      // Handle Markdown-style links [text](url) first
      .replace(
        /\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g,
        '<a href="$2" target="_blank" rel="noopener noreferrer" style="color: #1976d2; text-decoration: underline; font-weight: 500;">$1</a>',
      )
      // Handle plain URLs (but not those already in HTML tags)
      .replace(
        /(?<!href="|">)(https?:\/\/[^\s<]+)(?![^<]*<\/a>)/g,
        '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #1976d2; text-decoration: underline;">$1</a>',
      )
      // Handle bold text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      // Handle line breaks
      .replace(/\n/g, '<br>')
      // Handle numbered lists
      .replace(/^(\d+\.\s)/gm, '<br>$1')
      // Handle bullet points
      .replace(/^[-*]\s/gm, '<br>• ')
  );
};

// Lightbox handlers
const handleContentClick = (event) => {
  const { target } = event;
  if (!target || typeof target.closest !== 'function') return;
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

// Watch for new messages to scroll
watch(
  messages,
  () => {
    scrollToBottom();
  },
  { deep: true },
);

// Auto-open if prop is set
onMounted(() => {
  if (props.autoOpen) {
    toggleChat();
  }
  if (messagesContainer.value) {
    messagesContainer.value.addEventListener('click', handleContentClick);
  }
  onKeydown = () => {};
});

onBeforeUnmount(() => {
  if (messagesContainer.value) {
    messagesContainer.value.removeEventListener('click', handleContentClick);
  }
  // no-op cleanup for onKeydown
});
</script>

<style scoped>
.floating-chat {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
}

.floating-chat-button {
  transition: all 0.3s ease;
  animation: pulse 3s infinite;
}

.floating-chat-button:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 12px 32px rgba(233, 165, 241, 0.6) !important;
}

@keyframes pulse {
  0%,
  100% {
    box-shadow: 0 8px 24px rgba(233, 165, 241, 0.4);
  }
  50% {
    box-shadow: 0 8px 24px rgba(233, 165, 241, 0.6);
  }
}

.floating-chat-widget {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 400px;
  height: 600px;
  backdrop-filter: blur(20px);
  transition: all 0.3s ease;
}

.floating-chat-minimized {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 80px;
  height: 70px;
  transition: all 0.3s ease;
}

.floating-chat-minimized:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 12px 28px rgba(233, 165, 241, 0.6) !important;
}

.gradient-text {
  background: linear-gradient(135deg, #e9a5f1, #c68efd);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.chat-widget-header {
  backdrop-filter: blur(10px);
}

.messages-container {
  height: 460px;
  overflow-y: auto;
  scroll-behavior: smooth;
  background: linear-gradient(to bottom, #fafafa, #ffffff);
}

/* Lightbox uses vue-easy-lightbox styles */

.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #e9a5f1, #c68efd);
  border-radius: 10px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #c68efd, #8f87f1);
}

.suggestion-chip {
  transition: all 0.2s ease;
  margin-bottom: 8px;
}

.suggestion-chip:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

.user-bubble,
.assistant-bubble {
  transition: all 0.2s ease;
}

.user-bubble:hover,
.assistant-bubble:hover {
  transform: translateY(-1px);
}

.message-input >>> .v-field {
  border-radius: 20px !important;
  border: 1px solid #e0e0e0 !important;
}

.assistant-bubble .kb-image-gallery {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 6px;
}
.assistant-bubble .kb-image-gallery img {
  max-width: 100px;
  max-height: 100px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #eee;
}

.assistant-bubble .kb-reference {
  margin-top: 8px;
  padding-top: 6px;
  border-top: 1px dashed #e0e0e0;
}
.assistant-bubble .kb-ref-header {
  font-size: 11px;
  color: #666;
  margin-bottom: 4px;
}
.assistant-bubble .kb-ref-content img {
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  border: 1px solid #eee;
}
.assistant-bubble .kb-ref-content p {
  margin: 0.5em 0;
}

.assistant-bubble img {
  max-width: 100%;
  height: auto;
  border-radius: 6px;
}

.message-input >>> .v-field:focus-within {
  border-color: #e9a5f1 !important;
  box-shadow: 0 0 0 2px rgba(233, 165, 241, 0.2) !important;
}

.gap-2 > * + * {
  margin-top: 8px;
}

.gap-3 > * + * {
  margin-left: 12px;
}

/* Typing indicator animation */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
}

.typing-indicator span {
  width: 6px;
  height: 6px;
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
    opacity: 0.6;
  }
  40% {
    transform: scale(1.2);
    opacity: 1;
  }
}

/* Mobile responsive */
@media (max-width: 480px) {
  .floating-chat {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }

  .floating-chat-widget {
    width: calc(100vw - 32px);
    height: calc(100vh - 32px);
    bottom: 16px;
    right: 16px;
    left: 16px;
  }

  .messages-container {
    height: calc(100vh - 200px);
  }

  .floating-chat-minimized {
    bottom: 16px;
    right: 16px;
  }

  .user-bubble,
  .assistant-bubble {
    max-width: 240px !important;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .messages-container {
    background: linear-gradient(to bottom, #1a1a1a, #2d2d2d);
  }

  .assistant-bubble {
    background: #2d2d2d !important;
    border-color: #404040 !important;
  }

  .assistant-bubble .text-grey-800 {
    color: #e0e0e0 !important;
  }
}
</style>
