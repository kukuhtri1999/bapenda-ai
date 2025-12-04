<template>
  <div>
    <Head title="Lotre Undian - Bapenda" />

    <div
      class="lotre-page h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-800 relative overflow-hidden"
    >
      <!-- Background Particles -->
      <div class="particles-container absolute inset-0 pointer-events-none">
        <div
          v-for="i in 20"
          :key="i"
          class="particle"
          :style="getParticleStyle(i)"
        ></div>
      </div>

      <!-- Main Content - Compact Layout -->
      <div
        class="relative z-10 container mx-auto px-3 py-3 h-full flex flex-col"
      >
        <!-- Header - Compact -->
        <header class="text-center mb-3">
          <div
            class="flex items-center auto w-[150px] justify-center gap-3 mb-2 place-self-center"
          >
            <VImg :src="logoUrl" alt="Logo" contain :width="60" />
            <VImg
              src="/images/logo-jatim.png"
              alt="Logo Jatim"
              contain
              :height="60"
              :width="60"
            />
            <!--
            <VImg
              src="/images/logo-polri.png"
              alt="Logo Polri"
              contain
              :height="60"
              :width="60"
            />
            <VImg
              src="/images/jasa-raharja.png"
              alt="Jasa Raharja"
              contain
              :height="60"
              :width="60"
            /> -->
          </div>
          <h1
            class="text-2xl md:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 via-pink-200 to-cyan-200"
          >
            LOTRE UNDIAN BAPENDA JATIM
          </h1>
        </header>

        <!-- Main Grid - Compact -->
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-3 min-h-0">
          <!-- Slot Machine Card -->
          <div class="lg:col-span-2 flex flex-col min-h-0">
            <div
              class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 shadow-2xl overflow-hidden flex flex-col flex-1"
            >
              <!-- Machine Header -->
              <div
                class="bg-gradient-to-r from-yellow-500 to-orange-500 p-2 text-center flex-shrink-0"
              >
                <h2 class="text-lg font-bold text-white drop-shadow-md">
                  🎲 MESIN UNDIAN 🎲
                </h2>
              </div>

              <!-- Slot Display -->
              <div class="p-3 flex-1 flex flex-col min-h-0">
                <div
                  class="slot-machine-container relative mx-auto max-w-lg w-full"
                >
                  <!-- Slot Frame -->
                  <div
                    class="slot-frame bg-gradient-to-b from-gray-900 to-gray-800 rounded-xl p-3 shadow-inner border-4 border-yellow-500/50"
                  >
                    <!-- Slot Window -->
                    <div
                      class="slot-window bg-black/50 rounded-lg overflow-hidden relative"
                      :style="{ height: `${VISIBLE_COUNT * ITEM_H}px` }"
                    >
                      <!-- Gradient Overlays -->
                      <div
                        class="absolute inset-x-0 top-0 h-12 bg-gradient-to-b from-black/80 to-transparent z-10 pointer-events-none"
                      ></div>
                      <div
                        class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-black/80 to-transparent z-10 pointer-events-none"
                      ></div>

                      <!-- Center Highlight -->
                      <div
                        class="absolute inset-x-0 z-20 pointer-events-none"
                        :style="{
                          top: `${CENTER_ROW_INDEX * ITEM_H}px`,
                          height: `${ITEM_H}px`,
                        }"
                      >
                        <div
                          class="h-full border-t-2 border-b-2 border-yellow-400 bg-yellow-400/10"
                        ></div>
                      </div>

                      <!-- Rolling Items -->
                      <div
                        class="slot-items"
                        :style="animationStyle"
                        ref="innerRef"
                      >
                        <div
                          v-for="(p, idx) in displayPool"
                          :key="getItemKey(p, idx)"
                          class="slot-item flex items-center justify-center"
                          :style="{ height: `${ITEM_H}px` }"
                          :class="{ 'is-winner': isRevealed(p) }"
                        >
                          <div class="text-center px-3">
                            <div
                              class="text-base font-bold text-white truncate"
                            >
                              {{ getParticipantName(p) }}
                            </div>
                            <div class="text-xs text-gray-400 truncate">
                              {{ getParticipantNopol(p) }}
                              <span
                                v-if="p && p.kecamatan"
                                class="text-cyan-400"
                              >
                                • {{ p.kecamatan }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Decorative Lights -->
                  <div class="flex justify-around mt-2">
                    <div
                      v-for="i in 7"
                      :key="i"
                      class="light-bulb"
                      :class="{ 'is-active': spinning }"
                      :style="{ animationDelay: `${i * 0.05}s` }"
                    ></div>
                  </div>
                </div>

                <!-- Latest Winner Display -->
                <Transition name="winner-pop">
                  <div
                    v-if="latestWinner && !spinning"
                    class="mt-3 text-center"
                  >
                    <div
                      class="inline-block bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl px-6 py-2 shadow-2xl animate-bounce-subtle"
                    >
                      <div class="text-xs text-yellow-900 font-medium">
                        🎉 PEMENANG TERBARU 🎉
                      </div>
                      <div
                        class="text-2xl font-extrabold text-black drop-shadow"
                      >
                        {{ latestWinner.nama }}
                      </div>
                      <div class="text-base text-black">
                        {{ latestWinner.nopol }}
                        <span
                          v-if="latestWinner.kecamatan"
                          class="font-semibold"
                        >
                          • {{ latestWinner.kecamatan }}
                        </span>
                      </div>
                      <div
                        v-if="latestWinner.alamat"
                        class="text-sm text-black/80"
                      >
                        {{ latestWinner.alamat }}
                      </div>
                    </div>
                  </div>
                </Transition>

                <!-- Action Buttons -->
                <div class="flex justify-center gap-3 mt-3 flex-shrink-0">
                  <button
                    @click="spin"
                    :disabled="spinning || resetting"
                    class="spin-button group relative px-8 py-3 rounded-full font-bold text-lg text-white overflow-hidden transition-all duration-300"
                    :class="
                      spinning
                        ? 'bg-gray-600'
                        : 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 hover:scale-105 hover:shadow-xl hover:shadow-green-500/30'
                    "
                  >
                    <span class="relative z-10 flex items-center gap-2">
                      <svg
                        v-if="spinning"
                        class="animate-spin h-5 w-5"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          class="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          stroke-width="4"
                          fill="none"
                        />
                        <path
                          class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                      </svg>
                      <span v-else>🎰</span>
                      {{ spinning ? 'BERPUTAR...' : 'PUTAR!' }}
                    </span>
                  </button>

                  <button
                    @click="confirmReset"
                    :disabled="spinning || resetting || winners.length === 0"
                    class="px-5 py-3 rounded-full font-bold text-white bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-400 hover:to-pink-500 transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                  >
                    <span class="flex items-center gap-2">
                      <span>🔄</span>
                      {{ resetting ? 'Resetting...' : 'Reset' }}
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Winners Panel - Compact -->
          <div class="lg:col-span-1 flex flex-col min-h-0">
            <div
              class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 shadow-2xl overflow-hidden flex flex-col flex-1"
            >
              <div
                class="bg-gradient-to-r from-purple-500 to-pink-500 p-2 flex-shrink-0"
              >
                <h3 class="text-base font-bold text-white text-center">
                  🏆 DAFTAR PEMENANG ({{ winners.length }})
                </h3>
              </div>

              <div class="p-2 flex-1 overflow-y-auto custom-scrollbar min-h-0">
                <TransitionGroup name="winner-list" tag="ol" class="space-y-2">
                  <li
                    v-for="(w, idx) in winners"
                    :key="w.id"
                    class="winner-card rounded-lg p-2 transition-all duration-300"
                    :class="getWinnerCardClass(idx)"
                  >
                    <div class="flex items-center gap-2">
                      <!-- Rank Badge -->
                      <div
                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                        :class="getRankBadgeClass(idx)"
                      >
                        <span v-if="idx === 0">🥇</span>
                        <span v-else-if="idx === 1">🥈</span>
                        <span v-else-if="idx === 2">🥉</span>
                        <span v-else>{{ idx + 1 }}</span>
                      </div>

                      <!-- Winner Info -->
                      <div class="flex-1 min-w-0">
                        <div class="font-semibold text-white text-sm truncate">
                          {{ revealed.has(w.id) ? w.nama || 'Anon' : 'TBD' }}
                        </div>
                        <div
                          v-if="revealed.has(w.id)"
                          class="text-xs text-white/60"
                        >
                          {{ w.nopol }}
                          <span v-if="w.kecamatan" class="text-cyan-400">
                            • {{ w.kecamatan }} - {{ w.alamat }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </li>
                </TransitionGroup>

                <div v-if="winners.length === 0" class="text-center py-6">
                  <div class="text-3xl mb-2">🎲</div>
                  <p class="text-white/60 text-sm">Belum ada pemenang</p>
                  <p class="text-xs text-white/40">
                    Klik tombol PUTAR untuk memulai!
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confetti Container -->
      <div
        id="confetti-root"
        class="pointer-events-none fixed inset-0 z-50"
      ></div>
    </div>
  </div>
</template>

<script setup>
import {
  ref, computed, onMounted, nextTick,
} from 'vue';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const logoUrl = import.meta.env.VITE_APP_LOGO;

// Props from server
const props = defineProps({
  spinDuration: {
    type: Number,
    default: 2000, // Faster default: 2 seconds
  },
});

// Settings - Compact and Fast
const VISIBLE_COUNT = 5; // Fewer visible items for compact view
const ITEM_H = 48; // Smaller items for compact view
const CENTER_ROW_INDEX = Math.floor(VISIBLE_COUNT / 2);
const SPIN_MS = Math.min(props.spinDuration, 3000); // Cap at 3 seconds for fast feel
const CYCLE_INTERVAL = 30; // Fast cycling animation interval (ms)

// State
const participants = ref([]);
const winners = ref([]);
const displayPool = ref([]);
const spinning = ref(false);
const resetting = ref(false);
const revealed = ref(new Set());
const translateY = ref(0);
const latestWinner = ref(null);

const innerRef = ref(null);

// Computed
const eligibleCount = computed(
  () => participants.value.length - winners.value.length,
);

const animationStyle = computed(() => ({
  transform: `translateY(${translateY.value}px)`,
  transition: spinning.value
    ? `transform ${SPIN_MS}ms cubic-bezier(.2,.9,.3,1)` // Faster easing with quick start
    : 'none',
}));

// Helper functions for template
const getItemKey = (p, idx) => {
  if (p && p.id) return `p-${p.id}-${idx}`;
  return `p-${idx}`;
};

const isRevealed = (p) => {
  if (!p || !p.id) return false;
  return revealed.value.has(p.id);
};

const getParticipantName = (p) => {
  if (!p) return 'Loading...';
  return p.nama || 'Loading...';
};

const getParticipantNopol = (p) => {
  if (!p) return '';
  return p.nopol || '';
};

const getWinnerCardClass = (idx) => {
  if (idx === 0) return 'bg-gradient-to-r from-yellow-500/30 to-orange-500/30 border-2 border-yellow-400';
  if (idx === 1) return 'bg-gradient-to-r from-gray-400/30 to-gray-500/30 border border-gray-400';
  if (idx === 2) return 'bg-gradient-to-r from-orange-600/30 to-amber-600/30 border border-orange-500';
  return 'bg-white/5 border border-white/10';
};

const getRankBadgeClass = (idx) => {
  if (idx === 0) return 'bg-yellow-500 text-yellow-900';
  if (idx === 1) return 'bg-gray-400 text-gray-900';
  if (idx === 2) return 'bg-orange-600 text-white';
  return 'bg-white/20 text-white';
};

// Particle styles for background animation
const getParticleStyle = (i) => ({
  left: `${Math.random() * 100}%`,
  top: `${Math.random() * 100}%`,
  animationDelay: `${Math.random() * 5}s`,
  animationDuration: `${10 + Math.random() * 10}s`,
});

let spinTimer = null;
let finishTimer = null;

const fetchParticipants = async () => {
  try {
    const res = await fetch('/api/lotre/participants');
    const json = await res.json();
    participants.value = json.data || json || [];
  } catch (e) {
    participants.value = [];
  }
};

const fetchWinners = async () => {
  try {
    const res = await fetch('/api/lotre/winners');
    const data = await res.json();
    winners.value = data || [];
    revealed.value = new Set((winners.value || []).map((x) => x.id));
    if (winners.value.length > 0) {
      latestWinner.value = winners.value[winners.value.length - 1];
    }
  } catch (e) {
    winners.value = [];
  }
};

const shuffle = (arr) => {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
};

const buildPoolForWinner = (target) => {
  const source = participants.value.length
    ? participants.value.filter((p) => p.id !== target.id)
    : winners.value.filter((p) => p.id !== target.id);
  let pool = [];
  if (source.length === 0) return { pool: [target], pos: 0 };
  // Build a larger pool for faster, more dramatic spin effect
  while (pool.length < 200) pool = pool.concat(shuffle(source));
  const pos = Math.max(10, Math.floor(pool.length * 0.7)); // More distance for faster feel
  pool.splice(pos, 0, target);
  return { pool, pos };
};

const spin = async () => {
  if (spinning.value) return;

  let picked;
  try {
    spinning.value = true;
    const res = await fetch('/api/lotre/pick', { method: 'POST' });
    if (!res.ok) {
      spinning.value = false;
      await Swal.fire({
        icon: 'info',
        title: 'Tidak Ada Peserta',
        text: 'Semua kecamatan sudah ada pemenangnya!',
        background: '#1e1b4b',
        color: '#fff',
      });
      return;
    }
    picked = await res.json();
  } catch (e) {
    spinning.value = false;
    return;
  }

  // Clear timers
  if (spinTimer) clearTimeout(spinTimer);
  if (finishTimer) clearTimeout(finishTimer);

  // Build pool with picked participant
  const { pool, pos } = buildPoolForWinner(picked);
  displayPool.value = pool;

  // Reset position
  spinning.value = false;
  translateY.value = 0;
  await nextTick();
  try {
    void innerRef.value?.offsetHeight;
  } catch (e) {}

  const centerOffset = CENTER_ROW_INDEX * ITEM_H;
  const final = -(pos * ITEM_H) + centerOffset;

  // Start spin animation
  spinning.value = true;
  spinTimer = setTimeout(() => {
    translateY.value = final;
  }, 20); // Faster start

  // Reveal after spin
  finishTimer = setTimeout(async () => {
    revealed.value.add(picked.id);
    latestWinner.value = picked;
    spinning.value = false;
    runConfetti();
    spinTimer = null;
    finishTimer = null;

    // Play winner sound effect (optional)
    playWinnerSound();

    await fetchWinners();
  }, SPIN_MS + 100); // Shorter delay after spin
};

const resetAll = async () => {
  if (spinTimer) clearTimeout(spinTimer);
  if (finishTimer) clearTimeout(finishTimer);

  try {
    await fetch('/api/lotre/reset', { method: 'POST' });
  } catch (e) {}

  spinning.value = false;
  displayPool.value = [];
  translateY.value = 0;
  revealed.value = new Set();
  latestWinner.value = null;
  await fetchParticipants();
  await fetchWinners();
};

const confirmReset = async () => {
  const result = await Swal.fire({
    title: 'Konfirmasi Reset',
    text: 'Reset akan menghapus semua status pemenang. Anda yakin?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Reset',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    background: '#1e1b4b',
    color: '#fff',
  });

  if (result.isConfirmed) {
    resetting.value = true;
    await resetAll();
    resetting.value = false;
    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Semua pemenang telah direset.',
      background: '#1e1b4b',
      color: '#fff',
      timer: 2000,
      showConfirmButton: false,
    });
  }
};

const runConfetti = () => {
  const root = document.getElementById('confetti-root');
  if (!root) return;

  const colors = [
    '#FFD700',
    '#FF6B6B',
    '#4ECDC4',
    '#45B7D1',
    '#96E6A1',
    '#DDA0DD',
    '#F0E68C',
  ];

  for (let i = 0; i < 60; i++) {
    const el = document.createElement('div');
    el.className = 'confetti-piece';
    el.style.left = `${Math.random() * 100}vw`;
    el.style.background = colors[Math.floor(Math.random() * colors.length)];
    el.style.animationDuration = `${2 + Math.random() * 2}s`;
    el.style.animationDelay = `${Math.random() * 0.5}s`;
    root.appendChild(el);
    setTimeout(() => el.remove(), 5000);
  }
};

const playWinnerSound = () => {
  // Create a simple beep sound using Web Audio API
  try {
    const audioContext = new (window.AudioContext
      || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.frequency.value = 800;
    oscillator.type = 'sine';
    gainNode.gain.value = 0.3;

    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.2);
  } catch (e) {
    // Ignore audio errors
  }
};

onMounted(async () => {
  await fetchParticipants();
  await fetchWinners();

  if (participants.value.length) {
    displayPool.value = [...participants.value].concat([...participants.value]);
  } else if (winners.value.length) {
    displayPool.value = [...winners.value];
  } else {
    displayPool.value = Array(VISIBLE_COUNT).fill({ nama: 'Loading...' });
  }
});
</script>

<style scoped>
/* Background Particles */
.particles-container .particle {
  position: absolute;
  width: 6px;
  height: 6px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  animation: float 15s ease-in-out infinite;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0) translateX(0);
    opacity: 0.3;
  }
  50% {
    transform: translateY(-30px) translateX(20px);
    opacity: 0.8;
  }
}

/* Slot Machine */
.slot-items {
  position: relative;
  width: 100%;
}

.slot-item {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.slot-item.is-winner {
  background: linear-gradient(
    135deg,
    rgba(251, 191, 36, 0.3),
    rgba(245, 158, 11, 0.3)
  );
}

/* Light Bulbs */
.light-bulb {
  width: 16px;
  height: 16px;
  background: #374151;
  border-radius: 50%;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
  transition: all 0.2s ease;
}

.light-bulb.is-active {
  animation: bulb-blink 0.15s ease-in-out infinite alternate;
}

@keyframes bulb-blink {
  from {
    background: #fbbf24;
    box-shadow:
      0 0 10px #fbbf24,
      0 0 20px #fbbf24;
  }
  to {
    background: #f59e0b;
    box-shadow:
      0 0 5px #f59e0b,
      0 0 10px #f59e0b;
  }
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Winner Animations */
.winner-pop-enter-active {
  animation: pop-in 0.5s ease-out;
}

.winner-pop-leave-active {
  animation: pop-out 0.3s ease-in;
}

@keyframes pop-in {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes pop-out {
  to {
    transform: scale(0);
    opacity: 0;
  }
}

/* Winner List Animation */
.winner-list-enter-active {
  animation: slide-in 0.4s ease-out;
}

.winner-list-leave-active {
  animation: slide-out 0.3s ease-in;
}

.winner-list-move {
  transition: transform 0.3s ease;
}

@keyframes slide-in {
  from {
    transform: translateX(-20px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slide-out {
  to {
    transform: translateX(20px);
    opacity: 0;
  }
}

/* Confetti */
:global(.confetti-piece) {
  position: fixed;
  top: -20px;
  width: 12px;
  height: 12px;
  z-index: 9999;
  animation: confetti-fall 3s linear forwards;
  transform-origin: center;
}

@keyframes confetti-fall {
  0% {
    transform: translateY(0) rotateZ(0) rotateY(0);
    opacity: 1;
  }
  100% {
    transform: translateY(110vh) rotateZ(720deg) rotateY(720deg);
    opacity: 0;
  }
}

/* Pulse Animation */
.animate-pulse-slow {
  animation: pulse-slow 2s ease-in-out infinite;
}

@keyframes pulse-slow {
  0%,
  100% {
    transform: scale(1);
    box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 40px rgba(251, 191, 36, 0.8);
  }
}

/* Bounce Animation */
.animate-bounce-subtle {
  animation: bounce-subtle 1s ease-in-out infinite;
}

@keyframes bounce-subtle {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

/* Spin Button Glow Effect */
.spin-button::before {
  content: '';
  position: absolute;
  inset: -2px;
  background: linear-gradient(45deg, #22c55e, #10b981, #22c55e, #10b981);
  border-radius: 9999px;
  z-index: -1;
  animation: glow-spin 3s linear infinite;
  background-size: 400% 400%;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.spin-button:not(:disabled):hover::before {
  opacity: 1;
}

@keyframes glow-spin {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}
</style>
