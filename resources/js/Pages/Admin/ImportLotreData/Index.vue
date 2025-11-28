<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
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
const eligibleParticipants = computed(() => (
  currentStats.value.total_participants - currentStats.value.total_winners
));

const canProceedToPreview = computed(() => file.value !== null && !previewing.value);

const canProceedToImport = computed(() => (
  previewData.value !== null && previewData.value.success && !uploading.value
));

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

    const res = await window.axios.post(
      route('admin.lotre-import.preview'),
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );

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

    const res = await window.axios.post(
      route('admin.lotre-import.upload'),
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );

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

  // Reset file input
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
    const res = await window.axios.post(route('admin.lotre-import.clear'));

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
  window.location.href = route('admin.lotre-import.template');
};

const goToLotreUndian = () => {
  window.location.href = route('lotre.index');
};
</script>

<template>
  <div>
    <Head title="Import Data Peserta Lotre" />

    <div
      class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-8 px-4"
    >
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-800 mb-2">
            🎰 Import Data Peserta Lotre
          </h1>
          <p class="text-gray-600">
            Upload file Excel untuk menambahkan peserta undian
          </p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <div
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center transform hover:scale-105 transition-transform"
          >
            <div class="text-4xl font-bold text-blue-600 mb-1">
              {{ currentStats.total_participants }}
            </div>
            <div class="text-sm text-gray-500 font-medium">Total Peserta</div>
          </div>
          <div
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center transform hover:scale-105 transition-transform"
          >
            <div class="text-4xl font-bold text-green-600 mb-1">
              {{ currentStats.total_winners }}
            </div>
            <div class="text-sm text-gray-500 font-medium">Sudah Menang</div>
          </div>
          <div
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center transform hover:scale-105 transition-transform"
          >
            <div class="text-4xl font-bold text-purple-600 mb-1">
              {{ eligibleParticipants }}
            </div>
            <div class="text-sm text-gray-500 font-medium">Belum Menang</div>
          </div>
        </div>

        <!-- Stepper -->
        <div class="flex items-center justify-center mb-8">
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
                >Upload</span
              >
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
                >Preview</span
              >
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
                >Selesai</span
              >
            </div>
          </div>
        </div>

        <!-- Main Card -->
        <div
          class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden"
        >
          <!-- Step 1: Upload -->
          <div v-if="currentStep === 1" class="p-6 md:p-8">
            <h2
              class="text-xl font-semibold text-gray-800 mb-4 flex items-center"
            >
              <span class="text-2xl mr-2">📁</span> Pilih File Excel
            </h2>

            <!-- Format Info -->
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
              <h3 class="font-medium text-blue-800 mb-2">
                Format File yang Diharapkan:
              </h3>
              <div class="text-sm text-blue-700 space-y-1">
                <p>File Excel/CSV dengan kolom header:</p>
                <ul class="list-disc list-inside ml-2 space-y-1">
                  <li><strong>nama</strong> - Nama peserta</li>
                  <li><strong>nopol</strong> - Nomor polisi kendaraan</li>
                  <li><strong>alamat</strong> - Alamat peserta (opsional)</li>
                </ul>
              </div>
              <button
                @click="downloadTemplate"
                class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                <svg
                  class="w-4 h-4 mr-1"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  ></path>
                </svg>
                Download Template
              </button>
            </div>

            <!-- Error Alert -->
            <div
              v-if="errors.length"
              class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6"
            >
              <div class="flex items-start">
                <svg
                  class="w-5 h-5 text-red-500 mt-0.5 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"
                  ></path>
                </svg>
                <div>
                  <div
                    v-for="(err, i) in errors"
                    :key="i"
                    class="text-red-700 text-sm"
                  >
                    {{ err }}
                  </div>
                </div>
              </div>
            </div>

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
              <div v-if="!file">
                <svg
                  class="w-16 h-16 mx-auto text-gray-400 mb-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                  ></path>
                </svg>
                <p class="text-gray-600 font-medium">
                  Klik untuk pilih file atau drag & drop
                </p>
                <p class="text-gray-400 text-sm mt-1">
                  XLSX, XLS, atau CSV (Maks. 20MB)
                </p>
              </div>
              <div v-else>
                <svg
                  class="w-16 h-16 mx-auto text-green-500 mb-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  ></path>
                </svg>
                <p class="text-green-700 font-medium">{{ fileName }}</p>
                <p class="text-green-600 text-sm mt-1">File siap di-preview</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 mt-6">
              <button
                @click="previewFile"
                :disabled="!canProceedToPreview"
                :class="[
                  'flex-1 py-3 px-6 rounded-xl font-medium text-white transition-all flex items-center justify-center',
                  canProceedToPreview
                    ? 'bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl'
                    : 'bg-gray-300 cursor-not-allowed',
                ]"
              >
                <svg
                  v-if="previewing"
                  class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                {{ previewing ? 'Memproses...' : 'Preview Data →' }}
              </button>
            </div>
          </div>

          <!-- Step 2: Preview -->
          <div v-if="currentStep === 2 && previewData" class="p-6 md:p-8">
            <h2
              class="text-xl font-semibold text-gray-800 mb-4 flex items-center"
            >
              <span class="text-2xl mr-2">👀</span> Preview Data
            </h2>

            <!-- Summary -->
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                  <div class="text-2xl font-bold text-blue-600">
                    {{ previewData.total_rows }}
                  </div>
                  <div class="text-xs text-gray-500">Total Baris</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-green-600">
                    {{ previewData.preview_count }}
                  </div>
                  <div class="text-xs text-gray-500">Preview</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-gray-700">
                    {{ previewData.detected_columns.nama || '-' }}
                  </div>
                  <div class="text-xs text-gray-500">Kolom Nama</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-gray-700">
                    {{ previewData.detected_columns.nopol || '-' }}
                  </div>
                  <div class="text-xs text-gray-500">Kolom Nopol</div>
                </div>
              </div>
            </div>

            <!-- Preview Table -->
            <div class="border border-gray-200 rounded-xl overflow-hidden mb-6">
              <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-medium text-gray-700">10 Data Pertama</h3>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th
                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        No
                      </th>
                      <th
                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Nama
                      </th>
                      <th
                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Nopol
                      </th>
                      <th
                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Alamat
                      </th>
                      <th
                        class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Status
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr
                      v-for="(row, idx) in previewData.preview"
                      :key="idx"
                      :class="row.valid ? 'bg-white' : 'bg-red-50'"
                    >
                      <td class="px-4 py-3 text-sm text-gray-500">
                        {{ idx + 1 }}
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                        {{ row.nama || '-' }}
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-700 font-mono">
                        {{ row.nopol || '-' }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate"
                      >
                        {{ row.alamat || '-' }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        <span
                          v-if="row.valid"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                          ✓ Valid
                        </span>
                        <span
                          v-else
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                        >
                          ✗ Invalid
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div
                v-if="previewData.total_rows > 10"
                class="bg-gray-50 px-4 py-2 text-center text-sm text-gray-500 border-t border-gray-200"
              >
                ... dan {{ previewData.total_rows - 10 }} data lainnya
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
              <button
                @click="resetWizard"
                class="flex-1 py-3 px-6 rounded-xl font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-all"
              >
                ← Kembali
              </button>
              <button
                @click="confirmImport"
                :disabled="!canProceedToImport"
                :class="[
                  'flex-1 py-3 px-6 rounded-xl font-medium text-white transition-all flex items-center justify-center',
                  canProceedToImport
                    ? 'bg-green-600 hover:bg-green-700 shadow-lg hover:shadow-xl'
                    : 'bg-gray-300 cursor-not-allowed',
                ]"
              >
                <svg
                  v-if="uploading"
                  class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                {{ uploading ? 'Mengimport...' : 'Import Sekarang →' }}
              </button>
            </div>
          </div>

          <!-- Step 3: Complete -->
          <div
            v-if="currentStep === 3 && importResult"
            class="p-6 md:p-8 text-center"
          >
            <div class="mb-6">
              <div
                class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"
              >
                <svg
                  class="w-10 h-10 text-green-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  ></path>
                </svg>
              </div>
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
            <div
              v-if="importResult.errors && importResult.errors.length > 0"
              class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-left max-h-40 overflow-y-auto"
            >
              <h3 class="font-medium text-red-800 mb-2">Detail Error:</h3>
              <ul class="text-sm text-red-700 space-y-1">
                <li
                  v-for="(err, i) in importResult.errors.slice(0, 20)"
                  :key="i"
                >
                  {{ err }}
                </li>
              </ul>
              <p
                v-if="importResult.errors.length > 20"
                class="text-xs text-red-500 mt-2"
              >
                ... dan {{ importResult.errors.length - 20 }} error lainnya
              </p>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
              <button
                @click="resetWizard"
                class="flex-1 py-3 px-6 rounded-xl font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-all"
              >
                Import Lagi
              </button>
              <button
                @click="goToLotreUndian"
                class="flex-1 py-3 px-6 rounded-xl font-medium text-white bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl transition-all"
              >
                Ke Halaman Lotre →
              </button>
            </div>
          </div>
        </div>

        <!-- Danger Zone -->
        <div
          class="mt-8 bg-white rounded-2xl shadow-lg border border-red-100 overflow-hidden"
        >
          <div class="bg-red-50 px-6 py-4 border-b border-red-100">
            <h3 class="font-semibold text-red-800 flex items-center">
              <span class="text-xl mr-2">⚠️</span> Zona Bahaya
            </h3>
          </div>
          <div class="p-6">
            <p class="text-gray-600 text-sm mb-4">
              Hapus semua data peserta lotre. Tindakan ini tidak dapat
              dibatalkan.
            </p>
            <button
              @click="confirmClearAll"
              :disabled="clearing || currentStats.total_participants === 0"
              :class="[
                'py-2 px-4 rounded-lg font-medium transition-all',
                currentStats.total_participants > 0
                  ? 'text-red-600 border border-red-300 hover:bg-red-50'
                  : 'text-gray-400 border border-gray-200 cursor-not-allowed',
              ]"
            >
              {{ clearing ? 'Menghapus...' : 'Hapus Semua Data' }}
            </button>
          </div>
        </div>

        <!-- Quick Link -->
        <div class="mt-6 text-center">
          <a
            :href="route('lotre.index')"
            class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center"
          >
            <span class="text-xl mr-1">🎰</span>
            Lihat Halaman Lotre Undian
            <svg
              class="w-4 h-4 ml-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              ></path>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
