<script setup>
import { ref, computed } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';

const props = defineProps({
  existingData: {
    type: Object,
    default: null,
  },
  canProceedToChat: {
    type: Boolean,
    default: false,
  },
});

const logoUrl = import.meta.env.VITE_APP_LOGO;

// Helper function to get CSRF token
const getCsrfToken = () => {
  const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
  return csrfTokenElement ? csrfTokenElement.getAttribute('content') : '';
};

const form = ref({
  nama: props.existingData?.nama || '',
  nopol: props.existingData?.nopol || '',
  nomer_wa: props.existingData?.nomer_wa || '',
});

const formRef = ref(null);
const loading = ref(false);
const errors = ref({});
const isChangingData = ref(false);

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
  timeout: 4000,
  icon: 'mdi-check-circle',
});

const showSnackbar = (
  message,
  color = 'success',
  icon = 'mdi-check-circle',
) => {
  snackbar.value = {
    show: true,
    message,
    color,
    timeout: 4000,
    icon,
  };
};

const formatNopol = () => {
  let value = form.value.nopol.replace(/\s/g, '').toUpperCase();
  if (value.length > 0) {
    value = value
      .replace(/([A-Z]{1,2})([0-9]{1,4})([A-Z]{0,3})/, '$1 $2 $3')
      .trim();
  }
  form.value.nopol = value;
};

const startChatSession = async () => {
  errors.value = {};

  try {
    loading.value = true;
    const csrfToken = getCsrfToken();

    const response = await fetch('/api/wajib-pajak/start-chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(form.value),
    });

    const data = await response.json();

    if (data.success) {
      showSnackbar(
        'Data berhasil disimpan! Mengarahkan ke chat...',
        'success',
        'mdi-check-circle',
      );

      setTimeout(() => {
        window.location.href = data.redirect || '/customer-service';
      }, 800);
    } else if (data.errors) {
      errors.value = data.errors;
    } else {
      showSnackbar(
        data.message || 'Terjadi kesalahan saat menyimpan data',
        'error',
        'mdi-alert-circle',
      );
    }
  } catch (error) {
    console.error('Network error:', error);
    showSnackbar(
      'Terjadi kesalahan jaringan. Silakan coba lagi.',
      'error',
      'mdi-wifi-off',
    );
  } finally {
    loading.value = false;
  }
};

const proceedToChat = () => {
  window.location.href = '/customer-service';
};

const formSteps = [
  {
    title: 'Selamat Datang di SALMA AI!',
    intro:
      'Formulir pendaftaran singkat ini diperlukan sebelum memulai sesi tanya-jawab dengan asisten cerdas SALMA AI.',
  },
  {
    element: '#tour-wp-card',
    title: 'Formulir Data Wajib Pajak',
    intro:
      'Isi nama, plat nomor kendaraan, dan WhatsApp aktif Anda. Data aman dan hanya digunakan untuk personalisasi layanan.',
  },
  {
    element: '#tour-wp-nama',
    title: 'Nama Lengkap',
    intro: 'Masukkan nama lengkap Anda sesuai STNK atau KTP.',
  },
  {
    element: '#tour-wp-nopol',
    title: 'Nomor Polisi (Nopol)',
    intro:
      'Masukkan plat nomor kendaraan bermotor yang ingin Anda konsultasikan (contoh: S 1234 ZZ).',
  },
  {
    element: '#tour-wp-wa',
    title: 'Nomor WhatsApp',
    intro:
      'Masukkan nomor WhatsApp aktif Anda untuk konfirmasi dan ringkasan layanan.',
  },
  {
    title: 'Mulai Chat AI',
    intro:
      'Klik tombol "Mulai Chat AI" untuk langsung terhubung dengan asisten pintar 24/7.',
  },
];
const { startTour } = useTour(formSteps);
</script>

<template>
  <VApp>
    <Head title="Identitas Wajib Pajak — SALMA AI Samsat Lamongan" />

    <div class="wp-page-wrap">
      <!-- ═══ TOP GOVT BAR ═══ -->
      <header class="wp-topbar">
        <div class="wp-topbar__inner">
          <Link href="/" class="wp-brand no-underline">
            <VImg :src="logoUrl" alt="Bapenda" contain width="32" height="32" class="me-2" />
            <VImg src="/images/logo-jatim.png" alt="Pemprov Jatim" contain width="32" height="32" class="me-2 d-none d-sm-block" />
            <VImg src="/images/Lambang_Polda_Jatim.png" alt="Polri" contain width="32" height="32" class="me-2 d-none d-md-block" />
            <VImg src="/images/jasa-raharja.png" alt="Jasa Raharja" contain width="32" height="32" class="me-2 d-none d-md-block" />
            <div class="wp-brand__text">
              <span class="wp-brand__name">KB Samsat Lamongan</span>
              <span class="wp-brand__sub d-none d-sm-block">Pelayanan Publik Bebas Biaya</span>
            </div>
          </Link>

          <Link href="/" class="wp-back-btn no-underline">
            <VIcon size="16" class="me-1">mdi-arrow-left</VIcon>
            <span class="d-none d-sm-inline">Kembali ke Beranda</span>
            <span class="d-sm-none">Beranda</span>
          </Link>
        </div>
      </header>

      <!-- ═══ MAIN COMPACT FORM ═══ -->
      <main class="wp-content-wrap">
        <div class="wp-card-container">
          <div id="tour-wp-card" class="wp-card">
            <!-- Header Badge & Mascot -->
            <div class="wp-card__header">
              <div class="wp-card__avatar-wrap">
                <div class="wp-card__avatar">
                  <VIcon size="28" color="#C0392B">mdi-chat-processing</VIcon>
                </div>
              </div>

              <div class="wp-card__badge">
                <VIcon size="12" class="me-1" color="#C0392B">mdi-shield-check</VIcon>
                Data Diperlukan untuk Akses Chat
              </div>

              <h1 class="wp-card__title">Layanan Chat SALMA AI</h1>
              <p class="wp-card__subtitle">
                Samsat Lamongan Modern Assistant • Siap Melayani 24/7
              </p>
            </div>

            <!-- Existing Data Prompt (if already in session) -->
            <div v-if="canProceedToChat && !isChangingData" class="wp-existing-box mb-4">
              <div class="d-flex align-center gap-2 mb-2">
                <VIcon color="#27AE60" size="18">mdi-check-decagram</VIcon>
                <span class="font-weight-bold text-body-2 text-grey-900">Sesi Data Anda Masih Aktif</span>
              </div>
              <div class="wp-existing-details mb-3">
                <div class="wp-detail-row">
                  <span class="wp-detail-label">Nama:</span>
                  <span class="wp-detail-val">{{ existingData?.nama || '-' }}</span>
                </div>
                <div class="wp-detail-row">
                  <span class="wp-detail-label">Nopol:</span>
                  <span class="wp-detail-val">{{ existingData?.nopol || '-' }}</span>
                </div>
                <div class="wp-detail-row">
                  <span class="wp-detail-label">WhatsApp:</span>
                  <span class="wp-detail-val">{{ existingData?.nomer_wa || '-' }}</span>
                </div>
              </div>

              <div class="d-flex flex-column gap-2">
                <button @click="proceedToChat" class="wp-btn-primary">
                  <VIcon size="18" class="me-1">mdi-chat-processing</VIcon>
                  Lanjut ke Chat AI
                  <VIcon size="16" class="ms-1">mdi-arrow-right</VIcon>
                </button>
                <button @click="isChangingData = true" class="wp-btn-link">
                  <VIcon size="14" class="me-1">mdi-pencil-outline</VIcon>
                  Ubah / Perbarui Data
                </button>
              </div>
            </div>

            <!-- Input Form -->
            <form v-else @submit.prevent="startChatSession" class="wp-form">
              <div class="wp-input-group">
                <label for="tour-wp-nama" class="wp-label">
                  Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <div class="wp-input-box" :class="{ 'wp-input-box--error': errors.nama }">
                  <VIcon size="18" class="wp-input-icon">mdi-account-outline</VIcon>
                  <input
                    id="tour-wp-nama"
                    v-model="form.nama"
                    type="text"
                    placeholder="Masukkan nama lengkap Anda"
                    class="wp-input"
                    required
                  />
                </div>
                <span v-if="errors.nama" class="wp-error-text">{{ errors.nama[0] || errors.nama }}</span>
              </div>

              <div class="wp-input-group">
                <label for="tour-wp-nopol" class="wp-label">
                  Nomor Polisi (Nopol) <span class="text-red-500">*</span>
                </label>
                <div class="wp-input-box" :class="{ 'wp-input-box--error': errors.nopol }">
                  <VIcon size="18" class="wp-input-icon">mdi-car-outline</VIcon>
                  <input
                    id="tour-wp-nopol"
                    v-model="form.nopol"
                    type="text"
                    placeholder="Contoh: S 1234 ZZ"
                    class="wp-input uppercase font-mono tracking-wider font-semibold"
                    @input="formatNopol"
                    required
                  />
                </div>
                <span v-if="errors.nopol" class="wp-error-text">{{ errors.nopol[0] || errors.nopol }}</span>
              </div>

              <div class="wp-input-group">
                <label for="tour-wp-wa" class="wp-label">
                  Nomor WhatsApp <span class="text-red-500">*</span>
                </label>
                <div class="wp-input-box" :class="{ 'wp-input-box--error': errors.nomer_wa }">
                  <VIcon size="18" class="wp-input-icon">mdi-whatsapp</VIcon>
                  <input
                    id="tour-wp-wa"
                    v-model="form.nomer_wa"
                    type="tel"
                    placeholder="Contoh: 081234567890"
                    class="wp-input"
                    required
                  />
                </div>
                <span v-if="errors.nomer_wa" class="wp-error-text">{{ errors.nomer_wa[0] || errors.nomer_wa }}</span>
              </div>

              <!-- Submit Button -->
              <button
                type="submit"
                :disabled="loading"
                class="wp-btn-primary mt-4"
              >
                <VIcon size="18" class="me-1">
                  {{ loading ? 'mdi-loading mdi-spin' : 'mdi-chat-processing' }}
                </VIcon>
                {{ loading ? 'Menyimpan...' : 'Mulai Chat AI' }}
                <VIcon v-if="!loading" size="16" class="ms-1">mdi-arrow-right</VIcon>
              </button>

              <button
                v-if="canProceedToChat && isChangingData"
                type="button"
                @click="isChangingData = false"
                class="wp-btn-link mt-2"
              >
                Batal Ubah Data
              </button>
            </form>

            <!-- Security & Privacy footer -->
            <div class="wp-card__footer">
              <div class="wp-privacy-note">
                <VIcon size="14" color="#718096" class="me-1">mdi-lock-outline</VIcon>
                Data Anda aman, terenkripsi, dan hanya digunakan untuk layanan Samsat Lamongan.
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Success/Error Snackbar -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
      location="top"
    >
      <VIcon start>{{ snackbar.icon }}</VIcon>
      {{ snackbar.message }}
    </VSnackbar>

    <TourButton @start="startTour" variant="public" />
  </VApp>
</template>

<style scoped>
.wp-page-wrap {
  min-height: 100vh;
  background: linear-gradient(135deg, #1B2838 0%, #243348 50%, #1B2838 100%);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow-x: hidden;
}

.wp-page-wrap::before {
  content: '';
  position: absolute;
  top: -100px;
  right: -100px;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(192,57,43,.18) 0%, transparent 70%);
  pointer-events: none;
}

.wp-page-wrap::after {
  content: '';
  position: absolute;
  bottom: -100px;
  left: -100px;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(41,128,185,.12) 0%, transparent 70%);
  pointer-events: none;
}

/* ── TOPBAR ─────────────────────────────────────────────────────────────── */
.wp-topbar {
  padding: 14px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  background: rgba(27,40,56,.75);
  backdrop-filter: blur(12px);
  position: relative;
  z-index: 10;
}
.wp-topbar__inner {
  max-width: 1080px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.wp-brand {
  display: flex;
  align-items: center;
}
.wp-brand__text {
  display: flex;
  flex-direction: column;
}
.wp-brand__name {
  color: #fff;
  font-size: .95rem;
  font-weight: 700;
  line-height: 1.2;
}
.wp-brand__sub {
  color: rgba(255,255,255,.6);
  font-size: .72rem;
}
.wp-back-btn {
  display: inline-flex;
  align-items: center;
  color: rgba(255,255,255,.8);
  font-size: .82rem;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 20px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.15);
  transition: all .2s ease;
}
.wp-back-btn:hover {
  color: #fff;
  background: rgba(255,255,255,.18);
  transform: translateX(-2px);
}

/* ── CONTENT CONTAINER ──────────────────────────────────────────────────── */
.wp-content-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  position: relative;
  z-index: 2;
}
.wp-card-container {
  width: 100%;
  max-width: 480px;
}
.wp-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 32px 28px;
  box-shadow: 0 20px 60px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.1);
}

/* ── CARD HEADER ────────────────────────────────────────────────────────── */
.wp-card__header {
  text-align: center;
  margin-bottom: 24px;
}
.wp-card__avatar-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}
.wp-card__avatar {
  width: 58px;
  height: 58px;
  border-radius: 18px;
  background: #C0392B12;
  border: 1px solid #C0392B25;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(192,57,43,.12);
}
.wp-card__badge {
  display: inline-flex;
  align-items: center;
  background: #C0392B10;
  color: #C0392B;
  font-size: .72rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 16px;
  letter-spacing: .02em;
  margin-bottom: 8px;
}
.wp-card__title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #1B2838;
  line-height: 1.25;
  margin-bottom: 4px;
}
.wp-card__subtitle {
  font-size: .82rem;
  color: #718096;
  margin-bottom: 0;
}

/* ── EXISTING DATA BOX ──────────────────────────────────────────────────── */
.wp-existing-box {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 14px;
  padding: 16px;
}
.wp-existing-details {
  background: #ffffff;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.wp-detail-row {
  display: flex;
  justify-content: space-between;
  font-size: .82rem;
}
.wp-detail-label {
  color: #64748B;
  font-weight: 500;
}
.wp-detail-val {
  color: #1E293B;
  font-weight: 700;
}

/* ── FORM INPUTS ────────────────────────────────────────────────────────── */
.wp-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.wp-input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.wp-label {
  font-size: .78rem;
  font-weight: 700;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: .03em;
}
.wp-input-box {
  display: flex;
  align-items: center;
  background: #F8FAFC;
  border: 1.5px solid #E2E8F0;
  border-radius: 12px;
  padding: 10px 14px;
  transition: all .2s ease;
}
.wp-input-box:focus-within {
  background: #ffffff;
  border-color: #C0392B;
  box-shadow: 0 0 0 3px rgba(192,57,43,.12);
}
.wp-input-box--error {
  border-color: #EF4444 !important;
  background: #FEF2F2 !important;
}
.wp-input-icon {
  color: #94A3B8;
  margin-right: 10px;
  flex-shrink: 0;
}
.wp-input-box:focus-within .wp-input-icon {
  color: #C0392B;
}
.wp-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: .9rem;
  color: #1E293B;
  width: 100%;
}
.wp-input::placeholder {
  color: #94A3B8;
  font-size: .85rem;
}
.wp-error-text {
  font-size: .74rem;
  color: #EF4444;
  font-weight: 500;
  margin-top: 2px;
}

/* ── BUTTONS ────────────────────────────────────────────────────────────── */
.wp-btn-primary {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #C0392B 0%, #D32F2F 100%);
  color: #ffffff;
  font-size: .95rem;
  font-weight: 700;
  padding: 13px 20px;
  border-radius: 14px;
  border: none;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(192,57,43,.3);
  transition: all .25s ease;
}
.wp-btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #B03022 0%, #C0392B 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(192,57,43,.4);
}
.wp-btn-primary:disabled {
  opacity: .65;
  cursor: not-allowed;
}
.wp-btn-link {
  background: transparent;
  border: none;
  color: #64748B;
  font-size: .8rem;
  font-weight: 600;
  padding: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: color .2s ease;
}
.wp-btn-link:hover {
  color: #1E293B;
}

/* ── FOOTER ─────────────────────────────────────────────────────────────── */
.wp-card__footer {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #F1F5F9;
  text-align: center;
}
.wp-privacy-note {
  display: inline-flex;
  align-items: center;
  font-size: .74rem;
  color: #64748B;
  line-height: 1.4;
}

@media (max-width: 600px) {
  .wp-card {
    padding: 24px 20px;
    border-radius: 20px;
  }
  .wp-card__title {
    font-size: 1.25rem;
  }
}
</style>
