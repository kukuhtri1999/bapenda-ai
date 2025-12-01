<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import 'sweetalert2/dist/sweetalert2.min.css';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_participants: 0,
      total_winners: 0,
    }),
  },
});

// State
const currentStep = ref(1);
const file = ref(null);
const fileName = ref('');
const uploading = ref(false);
const previewing = ref(false);
const clearing = ref(false);
const previewData = ref(null);
const importResult = ref(null);
const errors = ref([]);
const currentStats = ref({ ...props.stats });

// Computed
const eligibleParticipants = computed(
  () => currentStats.value.total_participants - currentStats.value.total_winners,
);

const canProceedToPreview = computed(
  () => file.value !== null && !previewing.value,
);

const canProceedToImport = computed(
  () => previewData.value !== null && previewData.value.success && !uploading.value,
);

// Methods
const onFileChange = (e) => {
  const f = e.target.files?.[0];
  errors.value = [];
  previewData.value = null;
  importResult.value = null;

  if (!f) {
    file.value = null;
    fileName.value = '';
    return;
  }

  const allowed = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv',
  ];

  if (!allowed.includes(f.type)) {
    errors.value = ['Format file tidak didukung. Gunakan XLSX/XLS/CSV.'];
    file.value = null;
    fileName.value = '';
    return;
  }

  if (f.size > 20 * 1024 * 1024) {
    errors.value = ['Ukuran file melebihi 20MB.'];
    file.value = null;
    fileName.value = '';
    return;
  }

  file.value = f;
  fileName.value = f.name;
};

const triggerFileInput = () => {
  document.getElementById('file-input').click();
};

const previewFile = async () => {
  if (!file.value) {
    errors.value = ['Pilih file terlebih dahulu.'];
    return;
  }

  previewing.value = true;
  errors.value = [];
  previewData.value = null;

  try {
    const form = new FormData();
    form.append('file', file.value);

    const res = await window.axios.post(route('admin.lotre.preview'), form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    previewData.value = res.data;

    if (res.data?.success) {
      currentStep.value = 2;
    } else {
      errors.value = [res.data?.message || 'Preview gagal.'];
    }
  } catch (e) {
    errors.value = [
      e?.response?.data?.message
        || e.message
        || 'Terjadi kesalahan saat preview.',
    ];
  } finally {
    previewing.value = false;
  }
};

const confirmImport = async () => {
  if (!previewData.value?.success) return;

  const result = await Swal.fire({
    title: 'Konfirmasi Import',
    html: `
      <p>Anda akan mengimport <strong>${previewData.value.total_rows}</strong> data peserta.</p>
      <p class="text-sm text-gray-500 mt-2">Data duplikat (nopol sama) akan dilewati.</p>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Import Sekarang',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb',
  });

  if (result.isConfirmed) {
    await doImport();
  }
};

const doImport = async () => {
  uploading.value = true;
  errors.value = [];
  importResult.value = null;

  try {
    const form = new FormData();
    form.append('file', file.value);

    const res = await window.axios.post(route('admin.lotre.upload'), form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    importResult.value = res.data;

    if (res.data?.success) {
      currentStep.value = 3;
      if (res.data.stats) {
        currentStats.value = res.data.stats;
      }
    } else {
      errors.value = [res.data?.message || 'Import gagal.'];
    }
  } catch (e) {
    errors.value = [
      e?.response?.data?.message
        || e.message
        || 'Terjadi kesalahan saat import.',
    ];
  } finally {
    uploading.value = false;
  }
};

const resetWizard = () => {
  currentStep.value = 1;
  file.value = null;
  fileName.value = '';
  previewData.value = null;
  importResult.value = null;
  errors.value = [];

  const fileInput = document.getElementById('file-input');
  if (fileInput) fileInput.value = '';
};

const confirmClearAll = async () => {
  const result = await Swal.fire({
    title: 'Konfirmasi Hapus Semua Data',
    html: `
      <div class="text-left">
        <p class="text-red-600 font-semibold mb-2">⚠️ PERINGATAN!</p>
        <p>Anda akan menghapus <strong>${currentStats.value.total_participants}</strong> data peserta lotre.</p>
        <p class="text-sm text-gray-500 mt-2">Tindakan ini tidak dapat dibatalkan!</p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus Semua',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626',
    reverseButtons: true,
  });

  if (result.isConfirmed) {
    await clearAllData();
  }
};

const clearAllData = async () => {
  clearing.value = true;

  try {
    const res = await window.axios.post(route('admin.lotre.clear'));

    if (res.data?.success) {
      currentStats.value = { total_participants: 0, total_winners: 0 };
      await Swal.fire('Berhasil', res.data.message, 'success');
    } else {
      await Swal.fire(
        'Gagal',
        res.data.message || 'Gagal menghapus data.',
        'error',
      );
    }
  } catch (e) {
    await Swal.fire(
      'Gagal',
      e?.response?.data?.message || 'Terjadi kesalahan.',
      'error',
    );
  } finally {
    clearing.value = false;
  }
};

const downloadTemplate = () => {
  window.location.href = route('admin.lotre.template');
};

const goToLotreUndian = () => {
  window.location.href = route('lotre.index');
};
</script>

<template>
  <AppLayout title="Import Data Peserta Lotre">
    <Head title="Import Data Peserta Lotre" />

    <div class="space-y-6">
      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <VCard class="rounded-xl" variant="flat">
          <VCardText class="text-center">
            <div class="text-3xl font-bold text-blue-600">
              {{ currentStats.total_participants.toLocaleString() }}
            </div>
            <div class="text-gray-600">Total Peserta</div>
          </VCardText>
        </VCard>
        <VCard class="rounded-xl" variant="flat">
          <VCardText class="text-center">
            <div class="text-3xl font-bold text-green-600">
              {{ currentStats.total_winners }}
            </div>
            <div class="text-gray-600">Sudah Menang</div>
          </VCardText>
        </VCard>
        <VCard class="rounded-xl" variant="flat">
          <VCardText class="text-center">
            <div class="text-3xl font-bold text-purple-600">
              {{ eligibleParticipants.toLocaleString() }}
            </div>
            <div class="text-gray-600">Belum Menang</div>
          </VCardText>
        </VCard>
      </div>

      <!-- Stepper -->
      <div class="flex items-center justify-center">
        <div class="flex items-center">
          <!-- Step 1 -->
          <div class="flex items-center">
            <div
              :class="[
                'w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg transition-colors',
                currentStep >= 1
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-200 text-gray-500',
              ]"
            >
              1
            </div>
            <span
              class="ml-2 text-sm font-medium"
              :class="currentStep >= 1 ? 'text-blue-600' : 'text-gray-400'"
            >
              Upload
            </span>
          </div>

          <div
            :class="[
              'w-16 h-1 mx-2 rounded',
              currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200',
            ]"
          ></div>

          <!-- Step 2 -->
          <div class="flex items-center">
            <div
              :class="[
                'w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg transition-colors',
                currentStep >= 2
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-200 text-gray-500',
              ]"
            >
              2
            </div>
            <span
              class="ml-2 text-sm font-medium"
              :class="currentStep >= 2 ? 'text-blue-600' : 'text-gray-400'"
            >
              Preview
            </span>
          </div>

          <div
            :class="[
              'w-16 h-1 mx-2 rounded',
              currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-200',
            ]"
          ></div>

          <!-- Step 3 -->
          <div class="flex items-center">
            <div
              :class="[
                'w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg transition-colors',
                currentStep >= 3
                  ? 'bg-green-600 text-white'
                  : 'bg-gray-200 text-gray-500',
              ]"
            >
              ✓
            </div>
            <span
              class="ml-2 text-sm font-medium"
              :class="currentStep >= 3 ? 'text-green-600' : 'text-gray-400'"
            >
              Selesai
            </span>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <VCard class="rounded-xl" variant="flat">
        <!-- Step 1: Upload -->
        <VCardText v-if="currentStep === 1">
          <h2
            class="text-xl font-semibold text-gray-800 mb-4 flex items-center"
          >
            <VIcon class="mr-2">mdi-file-upload</VIcon>
            Pilih File Excel
          </h2>

          <!-- Format Info -->
          <VAlert type="info" variant="tonal" class="mb-6">
            <div class="text-sm">
              <p class="font-medium mb-2">Format File yang Diharapkan:</p>
              <ul class="list-disc list-inside ml-2 space-y-1">
                <li><strong>nama</strong> - Nama peserta</li>
                <li><strong>nopol</strong> - Nomor polisi kendaraan</li>
                <li><strong>alamat</strong> - Alamat peserta (opsional)</li>
              </ul>
            </div>
            <VBtn
              variant="text"
              color="primary"
              size="small"
              class="mt-3"
              @click="downloadTemplate"
            >
              <VIcon class="mr-1">mdi-download</VIcon>
              Download Template
            </VBtn>
          </VAlert>

          <!-- Error Alert -->
          <VAlert
            v-if="errors.length"
            type="error"
            variant="tonal"
            class="mb-6"
          >
            <div v-for="(err, i) in errors" :key="i">{{ err }}</div>
          </VAlert>

          <!-- File Upload Area -->
          <input
            type="file"
            id="file-input"
            accept=".xlsx,.xls,.csv"
            @change="onFileChange"
            class="hidden"
          />

          <div
            @click="triggerFileInput"
            @dragover.prevent
            @drop.prevent="
              (e) => onFileChange({ target: { files: e.dataTransfer.files } })
            "
            :class="[
              'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all',
              file
                ? 'border-green-400 bg-green-50'
                : 'border-gray-300 hover:border-blue-400 hover:bg-blue-50',
            ]"
          >
            <div v-if="!file" class="text-center">
              <VIcon size="64" color="grey">mdi-cloud-upload</VIcon>
              <p class="text-gray-600 font-medium mt-2">
                Klik untuk pilih file atau drag & drop
              </p>
              <p class="text-gray-400 text-sm mt-1">
                XLSX, XLS, atau CSV (Maks. 20MB)
              </p>
            </div>
            <div v-else class="text-center">
              <VIcon size="64" color="success">mdi-check-circle</VIcon>
              <p class="text-green-700 font-medium mt-2">{{ fileName }}</p>
              <p class="text-green-600 text-sm mt-1">File siap di-preview</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 mt-6">
            <VBtn
              color="primary"
              size="large"
              :disabled="!canProceedToPreview"
              :loading="previewing"
              @click="previewFile"
              class="flex-1"
            >
              <VIcon class="mr-2">mdi-eye</VIcon>
              Preview Data
            </VBtn>
          </div>
        </VCardText>

        <!-- Step 2: Preview -->
        <VCardText v-if="currentStep === 2 && previewData">
          <h2
            class="text-xl font-semibold text-gray-800 mb-4 flex items-center"
          >
            <VIcon class="mr-2">mdi-table-eye</VIcon>
            Preview Data
          </h2>

          <!-- Summary -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-blue-600">
                {{ previewData.total_rows }}
              </div>
              <div class="text-xs text-gray-500">Total Baris</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-green-600">
                {{ previewData.preview_count }}
              </div>
              <div class="text-xs text-gray-500">Preview</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <div class="text-sm font-semibold text-gray-700">
                {{ previewData.detected_columns.nama || '-' }}
              </div>
              <div class="text-xs text-gray-500">Kolom Nama</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <div class="text-sm font-semibold text-gray-700">
                {{ previewData.detected_columns.nopol || '-' }}
              </div>
              <div class="text-xs text-gray-500">Kolom Nopol</div>
            </div>
          </div>

          <!-- Preview Table -->
          <VTable class="rounded-lg border mb-6">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left">No</th>
                <th class="text-left">Nama</th>
                <th class="text-left">Nopol</th>
                <th class="text-left">Alamat</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in previewData.preview"
                :key="idx"
                :class="row.valid ? '' : 'bg-red-50'"
              >
                <td>{{ idx + 1 }}</td>
                <td class="font-medium">{{ row.nama || '-' }}</td>
                <td class="font-mono">{{ row.nopol || '-' }}</td>
                <td class="max-w-xs truncate">{{ row.alamat || '-' }}</td>
                <td class="text-center">
                  <VChip v-if="row.valid" color="success" size="small"
                    >Valid</VChip
                  >
                  <VChip v-else color="error" size="small">Invalid</VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <p
            v-if="previewData.total_rows > 10"
            class="text-center text-sm text-gray-500 mb-4"
          >
            ... dan {{ previewData.total_rows - 10 }} data lainnya
          </p>

          <!-- Actions -->
          <div class="flex gap-3">
            <VBtn
              variant="outlined"
              size="large"
              @click="resetWizard"
              class="flex-1"
            >
              <VIcon class="mr-2">mdi-arrow-left</VIcon>
              Kembali
            </VBtn>
            <VBtn
              color="success"
              size="large"
              :disabled="!canProceedToImport"
              :loading="uploading"
              @click="confirmImport"
              class="flex-1"
            >
              <VIcon class="mr-2">mdi-database-import</VIcon>
              Import Sekarang
            </VBtn>
          </div>
        </VCardText>

        <!-- Step 3: Complete -->
        <VCardText v-if="currentStep === 3 && importResult" class="text-center">
          <div class="py-6">
            <VIcon size="80" color="success" class="mb-4">
              mdi-check-circle-outline
            </VIcon>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
              Import Berhasil! 🎉
            </h2>
            <p class="text-gray-600">
              Data peserta lotre telah ditambahkan ke database
            </p>
          </div>

          <!-- Result Summary -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 rounded-xl p-4">
              <div class="text-3xl font-bold text-green-600">
                {{ importResult.imported }}
              </div>
              <div class="text-sm text-green-700">Berhasil</div>
            </div>
            <div class="bg-yellow-50 rounded-xl p-4">
              <div class="text-3xl font-bold text-yellow-600">
                {{ importResult.duplicates || 0 }}
              </div>
              <div class="text-sm text-yellow-700">Duplikat</div>
            </div>
            <div class="bg-red-50 rounded-xl p-4">
              <div class="text-3xl font-bold text-red-600">
                {{ importResult.skipped - (importResult.duplicates || 0) }}
              </div>
              <div class="text-sm text-red-700">Gagal</div>
            </div>
          </div>

          <!-- Error Details -->
          <VAlert
            v-if="importResult.errors && importResult.errors.length > 0"
            type="error"
            variant="tonal"
            class="mb-6 text-left"
          >
            <div class="font-medium mb-2">Detail Error:</div>
            <ul class="text-sm space-y-1">
              <li v-for="(err, i) in importResult.errors.slice(0, 20)" :key="i">
                {{ err }}
              </li>
            </ul>
            <p v-if="importResult.errors.length > 20" class="text-xs mt-2">
              ... dan {{ importResult.errors.length - 20 }} error lainnya
            </p>
          </VAlert>

          <!-- Actions -->
          <div class="flex gap-3">
            <VBtn
              variant="outlined"
              size="large"
              @click="resetWizard"
              class="flex-1"
            >
              <VIcon class="mr-2">mdi-refresh</VIcon>
              Import Lagi
            </VBtn>
            <VBtn
              color="primary"
              size="large"
              @click="goToLotreUndian"
              class="flex-1"
            >
              <VIcon class="mr-2">mdi-gift</VIcon>
              Ke Halaman Lotre
            </VBtn>
          </div>
        </VCardText>
      </VCard>

      <!-- Danger Zone -->
      <VCard class="rounded-xl border-red-200" variant="outlined">
        <VCardTitle class="text-red-800 bg-red-50">
          <VIcon class="mr-2" color="error">mdi-alert</VIcon>
          Zona Bahaya
        </VCardTitle>
        <VCardText>
          <p class="text-gray-600 text-sm mb-4">
            Hapus semua data peserta lotre. Tindakan ini tidak dapat dibatalkan.
          </p>
          <VBtn
            color="error"
            variant="outlined"
            :loading="clearing"
            :disabled="currentStats.total_participants === 0"
            @click="confirmClearAll"
          >
            <VIcon class="mr-2">mdi-delete-sweep</VIcon>
            Hapus Semua Data
          </VBtn>
        </VCardText>
      </VCard>
    </div>
  </AppLayout>
</template>
