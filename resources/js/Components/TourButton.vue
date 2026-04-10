<script setup>
defineProps({
  /** Emit 'start' when clicked — parent calls startTour() */
  label: { type: String, default: 'Butuh Bantuan?' },
  /** 'admin' (bottom-right, higher z) or 'public' (bottom-right) */
  variant: { type: String, default: 'admin' },
});

defineEmits(['start']);
</script>

<template>
  <button
    :class="['tour-fab', `tour-fab--${variant}`]"
    @click="$emit('start')"
    aria-label="Mulai panduan interaktif"
    title="Klik untuk memulai panduan"
  >
    <span class="tour-fab-icon" aria-hidden="true">
      <!-- Question-mark circle icon (inline SVG, no external deps) -->
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <circle cx="12" cy="12" r="10" />
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
        <line x1="12" y1="17" x2="12.01" y2="17" />
      </svg>
    </span>
    <span class="tour-fab-label">{{ label }}</span>
  </button>
</template>

<style scoped>
/* ── Floating Action Button ─────────────────────────────────── */
.tour-fab {
  position: fixed;
  bottom: 28px;
  right: 24px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px 10px 14px;
  border: none;
  border-radius: 50px;
  background: linear-gradient(135deg, #6c33a0 0%, #9b59d0 100%);
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(108, 51, 160, 0.45);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
  z-index: 990;
  user-select: none;
  white-space: nowrap;
}

.tour-fab:hover {
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 8px 28px rgba(108, 51, 160, 0.55);
  background: linear-gradient(135deg, #5b228e 0%, #8847bc 100%);
}

.tour-fab:active {
  transform: translateY(0) scale(0.98);
}

/* Public variant — slightly different colour so it's always visible on light BG */
.tour-fab--public {
  background: linear-gradient(135deg, #0066cc 0%, #00aed6 100%);
  box-shadow: 0 4px 18px rgba(0, 102, 204, 0.4);
}
.tour-fab--public:hover {
  background: linear-gradient(135deg, #0052a3 0%, #009ab8 100%);
  box-shadow: 0 8px 28px rgba(0, 102, 204, 0.5);
}

.tour-fab-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ── Pulse animation on first render ───────────────────────── */
.tour-fab {
  animation:
    fab-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both,
    fab-pulse 3s 1.5s ease-in-out infinite;
}
@keyframes fab-pop {
  from {
    opacity: 0;
    transform: scale(0.5) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
@keyframes fab-pulse {
  0%,
  100% {
    box-shadow: 0 4px 18px rgba(108, 51, 160, 0.45);
  }
  50% {
    box-shadow: 0 4px 28px rgba(108, 51, 160, 0.75);
  }
}
.tour-fab--public {
  animation:
    fab-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both,
    fab-pulse-blue 3s 1.5s ease-in-out infinite;
}
@keyframes fab-pulse-blue {
  0%,
  100% {
    box-shadow: 0 4px 18px rgba(0, 102, 204, 0.4);
  }
  50% {
    box-shadow: 0 4px 28px rgba(0, 102, 204, 0.7);
  }
}

/* ── Mobile ─────────────────────────────────────────────────── */
@media (max-width: 480px) {
  .tour-fab {
    padding: 9px 14px 9px 12px;
    font-size: 0.75rem;
    bottom: 20px;
    right: 14px;
  }
  .tour-fab-label {
    display: none; /* icon only on very small screens */
  }
  .tour-fab {
    padding: 11px;
    border-radius: 50%;
  }
}
</style>
