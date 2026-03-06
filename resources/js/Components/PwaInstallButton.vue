<template>
  <!-- Only render when install is possible AND not already installed -->
  <Transition name="pwa-slide">
    <div v-if="canInstall && !dismissed" class="pwa-install-wrapper">
      <!-- ── Main floating button ───────────────────────────────────────── -->
      <div class="pwa-install-card" :class="{ expanded: cardExpanded }">
        <!-- Collapsed pill -->
        <button
          v-if="!cardExpanded"
          class="pwa-pill"
          @click="cardExpanded = true"
          aria-label="Install aplikasi SALMA AI"
        >
          <span class="pwa-pill-icon">
            <VIcon size="18" color="white">mdi-download-circle</VIcon>
          </span>
          <span class="pwa-pill-text">Pasang Aplikasi</span>
          <span class="pwa-pill-badge">Gratis</span>
        </button>

        <!-- Expanded card -->
        <div v-else class="pwa-expanded-card">
          <!-- Close -->
          <button
            class="pwa-close-btn"
            @click="handleDismiss"
            aria-label="Tutup"
          >
            <VIcon size="16">mdi-close</VIcon>
          </button>

          <!-- App identity -->
          <div class="pwa-app-row">
            <img
              src="/icons/icon-96x96.png"
              alt="SALMA AI"
              class="pwa-app-icon"
            />
            <div class="pwa-app-info">
              <span class="pwa-app-name">SALMA AI</span>
              <span class="pwa-app-publisher">Bapenda Samsat Lamongan</span>
              <div class="pwa-stars">
                <VIcon v-for="i in 5" :key="i" size="11" color="#F59E0B"
                  >mdi-star</VIcon
                >
                <span class="pwa-rating-text">4.9 · Gratis</span>
              </div>
            </div>
          </div>

          <!-- Benefits -->
          <ul class="pwa-benefits">
            <li>
              <VIcon size="13" color="#6C33A0">mdi-check-circle</VIcon> Akses
              cepat dari layar utama
            </li>
            <li>
              <VIcon size="13" color="#6C33A0">mdi-check-circle</VIcon>
              Tampilan fullscreen tanpa browser
            </li>
            <li>
              <VIcon size="13" color="#6C33A0">mdi-check-circle</VIcon>
              Bekerja saat koneksi lambat
            </li>
          </ul>

          <!-- CTA -->
          <button
            class="pwa-install-cta"
            :disabled="installing"
            @click="handleInstall"
          >
            <VIcon v-if="!installing" size="16" color="white" class="mr-1"
              >mdi-download</VIcon
            >
            <VProgressCircular
              v-else
              size="14"
              width="2"
              indeterminate
              color="white"
              class="mr-1"
            />
            {{
              installing
                ? 'Memasang...'
                : isIOS
                  ? 'Cara Pasang di iPhone/iPad'
                  : 'Pasang Sekarang'
            }}
          </button>
        </div>
      </div>

      <!-- ── iOS manual guide ───────────────────────────────────────────── -->
      <Transition name="pwa-fade">
        <div v-if="showIOSGuide" class="pwa-ios-guide">
          <div class="pwa-ios-guide-header">
            <VIcon size="18" color="#6C33A0">mdi-apple</VIcon>
            <span>Pasang di iPhone / iPad</span>
            <button @click="dismissIOSGuide" class="pwa-ios-close">
              <VIcon size="14">mdi-close</VIcon>
            </button>
          </div>
          <ol class="pwa-ios-steps">
            <li>
              <span class="pwa-ios-step-icon">
                <VIcon size="18" color="#007AFF">mdi-export-variant</VIcon>
              </span>
              <span
                >Ketuk tombol <strong>Bagikan</strong>
                <VIcon size="14" color="#007AFF">mdi-export-variant</VIcon>
                di toolbar Safari</span
              >
            </li>
            <li>
              <span class="pwa-ios-step-icon">
                <VIcon size="18" color="#007AFF">mdi-plus-box</VIcon>
              </span>
              <span
                >Guilir dan ketuk <strong>"Tambah ke Layar Utama"</strong></span
              >
            </li>
            <li>
              <span class="pwa-ios-step-icon">
                <VIcon size="18" color="#007AFF">mdi-check-circle</VIcon>
              </span>
              <span>Ketuk <strong>Tambah</strong> — selesai! 🎉</span>
            </li>
          </ol>
          <!-- Arrow pointing down toward Safari toolbar -->
          <div class="pwa-ios-arrow-down"></div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
import { ref } from 'vue';
import { usePWA } from '@/composables/usePWA.js';

const {
  canInstall, isIOS, showIOSGuide, promptInstall, dismissIOSGuide,
} = usePWA();

const cardExpanded = ref(false);
const installing = ref(false);
const dismissed = ref(false);

async function handleInstall() {
  installing.value = true;
  const result = await promptInstall();
  installing.value = false;
  if (result === 'accepted') {
    dismissed.value = true;
  } else if (result === 'ios-guide') {
    // showIOSGuide toggled inside composable
  } else {
    // dismissed or unavailable — collapse card
    cardExpanded.value = false;
  }
}

function handleDismiss() {
  cardExpanded.value = false;
  // After a short delay, hide the pill too for this session
  setTimeout(() => {
    dismissed.value = true;
  }, 8000);
}
</script>

<style scoped>
/* ── Wrapper ──────────────────────────────────────────────────────────────── */
.pwa-install-wrapper {
  position: fixed;
  bottom: 88px; /* sit above floating chat button */
  right: 20px;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
  max-width: 320px;
}

/* ── Collapsed pill ──────────────────────────────────────────────────────── */
.pwa-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, #6c33a0 0%, #9b59b6 100%);
  color: #fff;
  border: none;
  border-radius: 50px;
  padding: 8px 14px 8px 8px;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(108, 51, 160, 0.45);
  font-family: 'Poppins', sans-serif;
  font-size: 12.5px;
  font-weight: 600;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
  user-select: none;
  animation: pwa-pulse 3s ease-in-out infinite;
}
.pwa-pill:hover {
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 6px 24px rgba(108, 51, 160, 0.55);
}
.pwa-pill:active {
  transform: scale(0.97);
}
.pwa-pill-icon {
  width: 28px;
  height: 28px;
  background: rgba(255, 255, 255, 0.18);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pwa-pill-text {
  letter-spacing: 0.01em;
}
.pwa-pill-badge {
  background: rgba(255, 255, 255, 0.22);
  border-radius: 20px;
  padding: 1px 7px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.03em;
}

/* ── Expanded card ───────────────────────────────────────────────────────── */
.pwa-expanded-card {
  width: 300px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 8px 40px rgba(0, 0, 0, 0.18);
  padding: 18px 16px 16px;
  position: relative;
  border: 1px solid rgba(108, 51, 160, 0.12);
}
.pwa-close-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: background 0.15s;
}
.pwa-close-btn:hover {
  background: #e5e7eb;
}

.pwa-app-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.pwa-app-icon {
  width: 52px;
  height: 52px;
  border-radius: 13px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  flex-shrink: 0;
}
.pwa-app-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.pwa-app-name {
  font-family: 'Poppins', sans-serif;
  font-weight: 700;
  font-size: 15px;
  color: #111;
}
.pwa-app-publisher {
  font-size: 11px;
  color: #6b7280;
}
.pwa-stars {
  display: flex;
  align-items: center;
  gap: 1px;
  margin-top: 2px;
}
.pwa-rating-text {
  font-size: 10.5px;
  color: #9ca3af;
  margin-left: 4px;
}

.pwa-benefits {
  list-style: none;
  padding: 0;
  margin: 0 0 14px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.pwa-benefits li {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #374151;
}

.pwa-install-cta {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
  background: linear-gradient(135deg, #6c33a0 0%, #9b59b6 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 11px 0;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition:
    opacity 0.2s,
    transform 0.15s;
  box-shadow: 0 3px 12px rgba(108, 51, 160, 0.35);
}
.pwa-install-cta:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}
.pwa-install-cta:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* ── iOS guide ───────────────────────────────────────────────────────────── */
.pwa-ios-guide {
  width: 300px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 8px 40px rgba(0, 0, 0, 0.15);
  padding: 14px 16px 18px;
  border: 1.5px solid #e5e7eb;
  position: relative;
}
.pwa-ios-guide-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-family: 'Poppins', sans-serif;
  font-weight: 600;
  font-size: 13.5px;
  color: #111;
}
.pwa-ios-close {
  margin-left: auto;
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
}
.pwa-ios-steps {
  padding: 0;
  margin: 0;
  list-style: none;
  counter-reset: ios-step;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.pwa-ios-steps li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 12.5px;
  color: #374151;
  line-height: 1.45;
}
.pwa-ios-step-icon {
  flex-shrink: 0;
  width: 30px;
  height: 30px;
  background: #f0f4ff;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pwa-ios-arrow-down {
  position: absolute;
  bottom: -10px;
  right: 24px;
  width: 0;
  height: 0;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  border-top: 10px solid #fff;
  filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.08));
}

/* ── Transitions ─────────────────────────────────────────────────────────── */
.pwa-slide-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.pwa-slide-leave-active {
  transition: all 0.3s ease;
}
.pwa-slide-enter-from,
.pwa-slide-leave-to {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}

.pwa-fade-enter-active {
  transition: all 0.25s ease;
}
.pwa-fade-leave-active {
  transition: all 0.2s ease;
}
.pwa-fade-enter-from,
.pwa-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

/* ── Pulse animation on pill ─────────────────────────────────────────────── */
@keyframes pwa-pulse {
  0%,
  100% {
    box-shadow: 0 4px 18px rgba(108, 51, 160, 0.45);
  }
  50% {
    box-shadow: 0 4px 28px rgba(108, 51, 160, 0.7);
  }
}

/* ── Mobile adjustments ───────────────────────────────────────────────────── */
@media (max-width: 400px) {
  .pwa-install-wrapper {
    right: 12px;
    bottom: 80px;
  }
  .pwa-expanded-card,
  .pwa-ios-guide {
    width: calc(100vw - 40px);
    max-width: 300px;
  }
}
</style>
