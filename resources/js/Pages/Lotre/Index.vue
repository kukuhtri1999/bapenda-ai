<template>
  <Head title="Lotre"></Head>
  <div class="lotre-page">
    <div class="container mx-auto p-4">
      <div class="bg-white rounded-lg shadow p-4 max-w-md mx-auto">
        <h1 class="text-2xl font-semibold mb-2 text-center">Lotre Undian</h1>

        <div class="picker-area mb-4">
          <div class="picker-window">
            <div class="picker-list">
              <div
                class="picker-list-inner"
                :style="animationStyle"
                ref="innerRef"
              >
                <div
                  v-for="(p, idx) in displayPool"
                  :key="p?.id ? `p-${p.id}-${idx}` : `p-${idx}`"
                  class="picker-item"
                  :class="{ 'picker-winner': revealed.has(p?.id) }"
                >
                  <div class="text-sm font-medium">{{ p?.nama || 'Anon' }}</div>
                  <div class="text-xs text-gray-500">
                    {{ p?.nopol || ''
                    }}<span v-if="p?.alamat"> — {{ p.alamat }}</span>
                  </div>
                </div>
              </div>
              <!-- center overlay: visual guide for the center row -->
              <div class="picker-center-overlay pointer-events-none"></div>
            </div>
          </div>

          <div class="flex justify-center gap-3 mt-3">
            <button
              @click="spin"
              :disabled="spinning || resetting"
              class="btn-primary"
            >
              {{ spinning ? 'Berputar...' : 'Putar' }}
            </button>
            <button
              @click="confirmReset"
              :disabled="spinning || resetting"
              class="btn-secondary"
            >
              {{ resetting ? 'Resetting...' : 'Reset' }}
            </button>
          </div>
        </div>

        <div class="mt-4">
          <h3 class="text-lg font-medium mb-2">Pemenang (urutan)</h3>
          <ol class="list-decimal pl-5 space-y-2">
            <li
              v-for="w in winners"
              :key="w.id"
              class="p-2 rounded"
              :class="{
                'bg-green-100': revealed.has(w.id),
                'opacity-60': !revealed.has(w.id),
              }"
            >
              <div class="font-medium">
                <template v-if="revealed.has(w.id)">
                  {{ w.nama || 'Anon' }}
                  <span class="text-xs text-gray-500"
                    >{{ w.nopol
                    }}<span v-if="w.alamat"> — {{ w.alamat }}</span></span
                  >
                </template>
                <template v-else>
                  <span class="text-sm text-gray-600 italic">TBD</span>
                </template>
              </div>
              <div class="text-xs text-gray-500" v-if="revealed.has(w.id)">
                Urutan: {{ w.urutan_menang }}
              </div>
            </li>
          </ol>
        </div>

        <div
          id="confetti-root"
          class="pointer-events-none fixed inset-0 z-50"
        ></div>
      </div>
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

// Settings
const VISIBLE_COUNT = 7;
const ITEM_H = 50; // px
const CENTER_ROW_INDEX = Math.floor(VISIBLE_COUNT / 2);
const SPIN_MS = 10000; // 10s

// State
const participants = ref([]);
const winners = ref([]);
const displayPool = ref([]); // full pool we translate
const spinning = ref(false);
const resetting = ref(false);
const currentWinnerIndex = ref(0);
const revealed = ref(new Set());
const translateY = ref(0);

const innerRef = ref(null);

const animationStyle = computed(() => ({
  transform: `translateY(${translateY.value}px)`,
  transition: spinning.value
    ? `transform ${SPIN_MS}ms cubic-bezier(.25,.46,.45,.94)`
    : 'none',
}));

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
    // mark already-picked winners as revealed so they show after reload
    try {
      revealed.value = new Set((winners.value || []).map((x) => x.id));
    } catch (e) {
      revealed.value = new Set();
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

// Build pool and insert the target at a deterministic position
const buildPoolForWinner = (target) => {
  const source = participants.value.length
    ? participants.value.filter((p) => p.id !== target.id)
    : winners.value.filter((p) => p.id !== target.id);
  let pool = [];
  if (source.length === 0) return { pool: [target], pos: 0 };
  while (pool.length < 250) pool = pool.concat(shuffle(source));
  const pos = Math.max(5, Math.floor(pool.length * 0.7));
  pool.splice(pos, 0, target);
  return { pool, pos };
};

const spin = async () => {
  if (spinning.value) return;

  // request backend to pick a random non-winning participant
  let picked;
  try {
    spinning.value = true;
    const res = await fetch('/api/lotre/pick', { method: 'POST' });
    if (!res.ok) {
      // no eligible participant
      spinning.value = false;
      return;
    }
    picked = await res.json();
  } catch (e) {
    spinning.value = false;
    return;
  }

  // clear timers
  if (spinTimer) {
    clearTimeout(spinTimer);
    spinTimer = null;
  }
  if (finishTimer) {
    clearTimeout(finishTimer);
    finishTimer = null;
  }

  // build a pool that includes the picked participant
  const { pool, pos } = buildPoolForWinner(picked);
  displayPool.value = pool;

  // initial reset without transition
  spinning.value = false;
  translateY.value = 0;
  await nextTick();
  try {
    void innerRef.value?.offsetHeight;
  } catch (e) {}

  const centerOffset = CENTER_ROW_INDEX * ITEM_H;
  const final = -(pos * ITEM_H) + centerOffset;

  // start transition
  spinning.value = true;
  spinTimer = setTimeout(() => {
    translateY.value = final;
  }, 40);

  // reveal after SPIN_MS, refresh winners list
  finishTimer = setTimeout(async () => {
    // mark revealed locally (server already set apakah_menang)
    revealed.value.add(picked.id);
    spinning.value = false;
    runConfetti();
    spinTimer = null;
    finishTimer = null;
    // refresh winners list from server to get urutan_menang values
    await fetchWinners();
  }, SPIN_MS + 150);
};

const resetAll = async () => {
  // clear timers and reset
  if (spinTimer) {
    clearTimeout(spinTimer);
    spinTimer = null;
  }
  if (finishTimer) {
    clearTimeout(finishTimer);
    finishTimer = null;
  }
  // call backend to clear winner flags
  try {
    await fetch('/api/lotre/reset', { method: 'POST' });
  } catch (e) {
    // ignore
  }

  spinning.value = false;
  displayPool.value = [];
  translateY.value = 0;
  currentWinnerIndex.value = 0;
  revealed.value = new Set();
  await fetchParticipants();
  await fetchWinners();
};

// Confirmation wrapper around resetAll using SweetAlert2
const confirmReset = async () => {
  const result = await Swal.fire({
    title: 'Konfirmasi Reset',
    text: 'Reset akan menghapus semua status pemenang. Anda yakin ingin melanjutkan?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, reset',
    cancelButtonText: 'Batal',
    reverseButtons: true,
  });

  if (result.isConfirmed) {
    try {
      resetting.value = true;
      await resetAll();
      resetting.value = false;
      await Swal.fire('Direset', 'Semua pemenang telah direset.', 'success');
    } catch (e) {
      resetting.value = false;
      await Swal.fire(
        'Gagal',
        'Terjadi kesalahan saat mereset. Silakan coba lagi.',
        'error',
      );
    }
  }
};

const runConfetti = () => {
  const root = document.getElementById('confetti-root');
  if (!root) return;
  for (let i = 0; i < 30; i++) {
    const el = document.createElement('div');
    el.className = 'confetti';
    el.style.left = `${Math.random() * 100}vw`;
    el.style.background = ['#FFD700', '#FF6B6B', '#4ECDC4', '#45B7D1'][
      Math.floor(Math.random() * 4)
    ];
    root.appendChild(el);
    setTimeout(() => el.remove(), 4500);
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
.picker-area {
  text-align: center;
}
.picker-window {
  width: 320px;
  height: calc(50px * 7);
  margin: 0 auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  position: relative;
}
.picker-list {
  height: 100%;
  overflow: hidden;
  position: relative;
}
.picker-list-inner {
  position: relative;
  width: 100%;
}
.picker-item {
  height: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid #f3f4f6;
  background: #fff;
  box-sizing: border-box;
}
.picker-item.picker-winner {
  background: #fef3c7;
  border: 2px solid #f59e0b;
}
.picker-center-overlay {
  position: absolute;
  left: 0;
  right: 0;
  height: 50px;
  top: calc(50% - 25px);
  border-top: 2px dashed rgba(59, 130, 246, 0.25);
  border-bottom: 2px dashed rgba(59, 130, 246, 0.25);
  pointer-events: none;
}
.btn-primary {
  background: #2563eb;
  color: #fff;
  padding: 8px 14px;
  border-radius: 6px;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-secondary {
  background: #e5e7eb;
  padding: 8px 12px;
  border-radius: 6px;
}
.confetti {
  position: fixed;
  top: -10px;
  width: 10px;
  height: 14px;
  z-index: 9999;
  animation: cf 4s linear forwards;
}
@keyframes cf {
  to {
    transform: translateY(110vh) rotate(720deg);
    opacity: 0;
  }
}
</style>
