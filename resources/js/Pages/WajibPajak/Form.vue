<script setup>
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';

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

// Computed property for form validation
// const isFormValid = computed(() => {
//     const namaValid = form.value.nama && form.value.nama.trim() !== "";
//     const nopolValid = form.value.nopol && form.value.nopol.trim() !== "";
//     const waValid =
//         form.value.nomer_wa &&
//         form.value.nomer_wa.trim() !== "" &&
//         /^[0-9+\-\s\(\)]+$/.test(form.value.nomer_wa.trim());

//     return namaValid && nopolValid && waValid;
// });
// Always enable the button - no validation checks
const isFormValid = ref(true);

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
  // Auto format nopol (AA 0000 ZZZ)
  let value = form.value.nopol.replace(/\s/g, '').toUpperCase();
  if (value.length > 0) {
    value = value
      .replace(/([A-Z]{1,2})([0-9]{1,4})([A-Z]{0,3})/, '$1 $2 $3')
      .trim();
  }
  form.value.nopol = value;
};

const startChatSession = async () => {
  // Clear previous errors
  errors.value = {};

  // No validation checks - just proceed directly
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

      // Redirect to chat after short delay
      setTimeout(() => {
        window.location.href = data.redirect;
      }, 1500);
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
</script>

<template>
  <VApp>
    <Head title="Form Data Wajib Pajak - Chat AI" />

    <VMain class="bg-gradient">
      <VContainer class="py-8">
        <VRow justify="center">
          <VCol cols="12" md="8" lg="6">
            <VCard class="pa-8 form-card" elevation="12">
              <div class="text-center mb-8">
                <VAvatar color="primary" size="80" class="mb-4">
                  <VIcon size="40" color="white">mdi-chat</VIcon>
                </VAvatar>
                <h1 class="text-h4 font-weight-bold text-primary mb-2">
                  Layanan Chat SALMA AI
                </h1>
                <p class="text-grey-600 mb-2">
                  Untuk menggunakan layanan chat AI, silakan isi data terlebih
                  dahulu
                </p>
                <VChip color="info" variant="outlined" size="small">
                  <VIcon left size="16">mdi-shield-check</VIcon>
                  Data Diperlukan untuk Akses Chat
                </VChip>
              </div>

              <!-- Show existing data if available -->
              <VAlert
                v-if="canProceedToChat"
                type="success"
                variant="tonal"
                class="mb-6"
                prominent
              >
                <template #title>Data Sudah Tersimpan</template>
                <p class="mb-4">Anda sudah mengisi data sebelumnya:</p>
                <ul class="mb-4">
                  <li>
                    <strong>Nama:</strong>
                    {{ existingData.nama }}
                  </li>
                  <li>
                    <strong>Nopol:</strong>
                    {{ existingData.nopol }}
                  </li>
                  <li>
                    <strong>No. WhatsApp:</strong>
                    {{ existingData.nomer_wa }}
                  </li>
                </ul>

                <VBtn
                  color="success"
                  size="large"
                  class="mr-3"
                  @click="proceedToChat"
                >
                  <VIcon left>mdi-chat</VIcon>
                  Lanjut ke Chat AI
                </VBtn>

                <VBtn
                  color="primary"
                  variant="outlined"
                  size="large"
                  @click="
                    form = {
                      nama: '',
                      nopol: '',
                      nomer_wa: '',
                    }
                  "
                >
                  <VIcon left>mdi-pencil</VIcon>
                  Ubah Data
                </VBtn>
              </VAlert>

              <!-- Form input -->
              <VForm
                ref="formRef"
                @submit.prevent="startChatSession"
                v-show="
                  !canProceedToChat ||
                  (form.nama === '' &&
                    form.nopol === '' &&
                    form.nomer_wa === '')
                "
              >
                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model="form.nama"
                      label="Nama Lengkap"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                      :error-messages="errors.nama"
                      placeholder="Masukkan nama lengkap"
                    ></VTextField>
                  </VCol>

                  <VCol cols="12">
                    <VTextField
                      v-model="form.nopol"
                      label="Nomor Polisi (contoh: AA 0000 ZZZ)"
                      prepend-inner-icon="mdi-car-info"
                      variant="outlined"
                      :error-messages="errors.nopol"
                      @input="formatNopol"
                      placeholder="AA 0000 ZZZ"
                    ></VTextField>
                  </VCol>

                  <VCol cols="12">
                    <VTextField
                      v-model="form.nomer_wa"
                      label="Nomor WhatsApp"
                      prepend-inner-icon="mdi-whatsapp"
                      variant="outlined"
                      :error-messages="errors.nomer_wa"
                      placeholder="08xxxxxxxxxx atau +62xxxxxxxxxx"
                      hint="Contoh: 08123456789 atau +6281234567890"
                      persistent-hint
                    ></VTextField>
                  </VCol>

                  <VCol cols="12" class="text-center">
                    <VBtn
                      type="submit"
                      color="primary"
                      size="x-large"
                      :loading="loading"
                      :disabled="!isFormValid"
                      class="px-8"
                    >
                      <v-icon left>mdi-wchat</v-icon>
                      Mulai Chat AI
                    </VBtn>
                  </VCol>
                </VRow>
              </VForm>

              <!-- Additional info -->
              <VDivider class="my-6"></VDivider>
              <div class="text-center">
                <VChip color="grey" variant="text" size="small" class="mb-2">
                  <VIcon left size="16">mdi-information</VIcon>
                  Informasi
                </VChip>
                <p class="text-sm text-grey-600">
                  Data yang Anda masukkan akan digunakan untuk memberikan
                  layanan yang lebih personal.<br />
                  <strong>Data akan disimpan secara permanen</strong>
                  dalam database kami untuk keperluan bank data dan peningkatan
                  layanan.<br />
                  Data juga disimpan dalam session untuk akses chat AI.
                </p>
              </div>
            </VCard>
          </VCol>
        </VRow>
      </VContainer>
    </VMain>

    <!-- Success/Error Snackbar -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
      location="top"
    >
      <VIcon left>{{ snackbar.icon }}</VIcon>
      {{ snackbar.message }}
    </VSnackbar>
  </VApp>
</template>

<style scoped>
.bg-gradient {
  background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 50%, #a5d6a7 100%);
  min-height: 100vh;
}

.form-card {
  border-radius: 24px !important;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95) !important;
}

.v-btn {
  border-radius: 12px !important;
  text-transform: none;
  font-weight: 600;
}

.v-text-field {
  margin-bottom: 8px;
}

.v-alert {
  border-radius: 16px !important;
}
</style>
