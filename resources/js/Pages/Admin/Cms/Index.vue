<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  contents: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
});

// Initialize form model from props
const form = reactive({});
const initForm = () => {
  Object.keys(props.contents).forEach((section) => {
    props.contents[section].forEach((item) => {
      if (item.type === 'json' && typeof item.value === 'object' && item.value !== null) {
        form[item.key] = JSON.parse(JSON.stringify(item.value));
      } else {
        form[item.key] = item.value;
      }
    });
  });
};
initForm();

const activeTab = ref('hero');
const saving = ref(false);
const resetting = ref(false);
const uploading = ref(false);
const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
});

const showNotification = (message, color = 'success') => {
  snackbar.message = message;
  snackbar.color = color;
  snackbar.show = true;
};

// ── Save CMS Updates ─────────────────────────────────────────────────────────
const saveChanges = async () => {
  saving.value = true;
  try {
    const items = Object.keys(form).map((key) => ({
      key,
      value: form[key],
    }));

    router.post(
      route('admin.cms.update'),
      { items },
      {
        preserveScroll: true,
        onSuccess: () => {
          showNotification('Semua perubahan konten berhasil disimpan!');
        },
        onError: (errors) => {
          showNotification('Gagal menyimpan perubahan. Silakan periksa formulir.', 'error');
          console.error(errors);
        },
        onFinish: () => {
          saving.value = false;
        },
      }
    );
  } catch (err) {
    saving.value = false;
    showNotification('Terjadi kesalahan saat menyimpan data.', 'error');
  }
};

// ── Reset to Defaults ────────────────────────────────────────────────────────
const confirmReset = () => {
  if (confirm('Apakah Anda yakin ingin mereset seluruh konten beranda dan footer ke pengaturan awal bawaan?')) {
    resetting.value = true;
    router.post(
      route('admin.cms.reset-defaults'),
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          initForm();
          showNotification('Konten berhasil direset ke pengaturan awal!');
        },
        onError: () => {
          showNotification('Gagal mereset konten.', 'error');
        },
        onFinish: () => {
          resetting.value = false;
        },
      }
    );
  }
};

// ── Image Upload Helper (Direct File to Storage/Public) ──────────────────────
const uploadImageFile = async (event, targetKey, arrayIndex = null, arrayField = null) => {
  const file = event.target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('image', file);

  uploading.value = true;
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch(route('admin.cms.upload-image'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
      },
      body: formData,
    });

    const data = await res.json();
    if (data.success && data.url) {
      if (arrayIndex !== null && arrayField !== null) {
        form[targetKey][arrayIndex][arrayField] = data.url;
      } else if (arrayIndex !== null) {
        form[targetKey][arrayIndex] = data.url;
      } else {
        form[targetKey] = data.url;
      }
      showNotification('Gambar berhasil diunggah!');
    } else {
      showNotification(data.message || 'Gagal mengunggah gambar', 'error');
    }
  } catch (e) {
    showNotification('Gagal menghubungi server untuk unggah gambar', 'error');
  } finally {
    uploading.value = false;
    event.target.value = '';
  }
};

const triggerFileUpload = (fileInputId) => {
  const el = document.getElementById(fileInputId);
  if (el) el.click();
};

// ── MDI Icon Picker State & Modal ────────────────────────────────────────────
const iconDialog = ref(false);
const iconSearchQuery = ref('');
const iconActiveCategory = ref('Semua');
const iconTargetCallback = ref(null);

const iconCategories = ['Semua', 'Kendaraan', 'Dokumen', 'Waktu', 'Lokasi', 'Komunikasi', 'Keuangan', 'Simbol', 'Sosial'];

const popularIcons = [
  // Pelayanan & Kendaraan
  { name: 'mdi-car-side', label: 'Mobil Samping', cat: 'Kendaraan' },
  { name: 'mdi-car', label: 'Mobil Depan', cat: 'Kendaraan' },
  { name: 'mdi-motorbike', label: 'Sepeda Motor', cat: 'Kendaraan' },
  { name: 'mdi-bus', label: 'Bus', cat: 'Kendaraan' },
  { name: 'mdi-bus-clock', label: 'Bus Keliling', cat: 'Kendaraan' },
  { name: 'mdi-truck', label: 'Truk', cat: 'Kendaraan' },
  { name: 'mdi-car-info', label: 'Info Kendaraan', cat: 'Kendaraan' },
  { name: 'mdi-card-account-details-outline', label: 'STNK / Identitas', cat: 'Dokumen' },
  { name: 'mdi-card-account-details', label: 'Kartu Identitas', cat: 'Dokumen' },
  { name: 'mdi-swap-horizontal-bold', label: 'Balik Nama / Tukar', cat: 'Dokumen' },
  { name: 'mdi-file-document-swap-outline', label: 'Mutasi Berkas', cat: 'Dokumen' },
  { name: 'mdi-file-document-outline', label: 'Dokumen', cat: 'Dokumen' },
  { name: 'mdi-file-check-outline', label: 'Dokumen Selesai', cat: 'Dokumen' },
  { name: 'mdi-license', label: 'Lisensi / Surat', cat: 'Dokumen' },

  // Waktu & Jadwal
  { name: 'mdi-calendar-clock', label: 'Jadwal Kalender', cat: 'Waktu' },
  { name: 'mdi-calendar-month', label: 'Kalender Bulanan', cat: 'Waktu' },
  { name: 'mdi-clock-outline', label: 'Jam Waktu', cat: 'Waktu' },
  { name: 'mdi-clock-fast', label: 'Respon Cepat', cat: 'Waktu' },
  { name: 'mdi-weather-night', label: 'Malam Hari', cat: 'Waktu' },
  { name: 'mdi-weather-sunny', label: 'Siang Hari', cat: 'Waktu' },
  { name: 'mdi-calendar-check', label: 'Jadwal Selesai', cat: 'Waktu' },

  // Lokasi & Kantor
  { name: 'mdi-map-marker', label: 'Pin Lokasi', cat: 'Lokasi' },
  { name: 'mdi-map-marker-radius', label: 'Titik Peta', cat: 'Lokasi' },
  { name: 'mdi-map-marker-outline', label: 'Marker Peta', cat: 'Lokasi' },
  { name: 'mdi-map-marker-multiple', label: 'Banyak Titik', cat: 'Lokasi' },
  { name: 'mdi-office-building-marker', label: 'Gedung Kantor', cat: 'Lokasi' },
  { name: 'mdi-office-building', label: 'Kantor', cat: 'Lokasi' },
  { name: 'mdi-domain', label: 'Gedung Publik', cat: 'Lokasi' },
  { name: 'mdi-city-variant-outline', label: 'Pusat Kota', cat: 'Lokasi' },
  { name: 'mdi-bus-stop', label: 'Terminal / Halte', cat: 'Lokasi' },

  // AI & Komunikasi
  { name: 'mdi-chat-processing', label: 'Chat AI Balas', cat: 'Komunikasi' },
  { name: 'mdi-chat', label: 'Percakapan', cat: 'Komunikasi' },
  { name: 'mdi-robot', label: 'Robot AI', cat: 'Komunikasi' },
  { name: 'mdi-brain', label: 'Kecerdasan AI', cat: 'Komunikasi' },
  { name: 'mdi-translate', label: 'Banyak Bahasa', cat: 'Komunikasi' },
  { name: 'mdi-phone', label: 'Telepon', cat: 'Komunikasi' },
  { name: 'mdi-phone-in-talk', label: 'Panggilan', cat: 'Komunikasi' },
  { name: 'mdi-whatsapp', label: 'WhatsApp', cat: 'Komunikasi' },
  { name: 'mdi-headset', label: 'Customer Service', cat: 'Komunikasi' },

  // Keuangan & Pembayaran
  { name: 'mdi-contactless-payment', label: 'Pembayaran Digital', cat: 'Keuangan' },
  { name: 'mdi-credit-card-outline', label: 'Kartu Bank', cat: 'Keuangan' },
  { name: 'mdi-wallet-outline', label: 'Dompet Digital', cat: 'Keuangan' },
  { name: 'mdi-bank-outline', label: 'Perbankan', cat: 'Keuangan' },
  { name: 'mdi-shopping-outline', label: 'E-Commerce', cat: 'Keuangan' },
  { name: 'mdi-qrcode-scan', label: 'QRIS Scan', cat: 'Keuangan' },
  { name: 'mdi-cash-multiple', label: 'Uang Tunai', cat: 'Keuangan' },

  // Keamanan & Simbol
  { name: 'mdi-shield-check', label: 'Resmi & Aman', cat: 'Simbol' },
  { name: 'mdi-shield-check-outline', label: 'Perlindungan', cat: 'Simbol' },
  { name: 'mdi-star-four-points', label: 'Bintang Keunggulan', cat: 'Simbol' },
  { name: 'mdi-check-decagram', label: 'Terverifikasi', cat: 'Simbol' },
  { name: 'mdi-information', label: 'Informasi', cat: 'Simbol' },
  { name: 'mdi-help-circle', label: 'Bantuan', cat: 'Simbol' },

  // Media Sosial
  { name: 'mdi-instagram', label: 'Instagram', cat: 'Sosial' },
  { name: 'mdi-facebook', label: 'Facebook', cat: 'Sosial' },
  { name: 'mdi-youtube', label: 'YouTube', cat: 'Sosial' },
];

const filteredIcons = computed(() => {
  const query = (iconSearchQuery.value || '').toLowerCase().trim();
  return popularIcons.filter((i) => {
    const matchCat = iconActiveCategory.value === 'Semua' || i.cat === iconActiveCategory.value;
    if (!matchCat) return false;
    if (!query) return true;
    return i.name.toLowerCase().includes(query) || i.label.toLowerCase().includes(query);
  });
});

const openIconPicker = (callback) => {
  iconSearchQuery.value = '';
  iconActiveCategory.value = 'Semua';
  iconTargetCallback.value = callback;
  iconDialog.value = true;
};

const selectIcon = (iconName) => {
  if (typeof iconTargetCallback.value === 'function') {
    iconTargetCallback.value(iconName);
  }
  iconDialog.value = false;
};

// ── Array Helpers ────────────────────────────────────────────────────────────
const addHeroImage = () => {
  if (!Array.isArray(form.hero_backgrounds)) form.hero_backgrounds = [];
  form.hero_backgrounds.push('https://picsum.photos/id/1076/1920/800');
};

const removeHeroImage = (idx) => {
  if (confirm('Hapus slide gambar ini?')) {
    form.hero_backgrounds.splice(idx, 1);
  }
};

const addKelilingLocation = (dayIdx) => {
  if (!form.keliling_schedules[dayIdx].locations) form.keliling_schedules[dayIdx].locations = [];
  form.keliling_schedules[dayIdx].locations.push('Titik Lokasi Baru');
};

const removeKelilingLocation = (dayIdx, locIdx) => {
  form.keliling_schedules[dayIdx].locations.splice(locIdx, 1);
};
</script>

<template>
  <AppLayout title="CMS Beranda & Footer">
    <div class="vuexy-cms-container p-4 sm:p-6 max-w-7xl mx-auto space-y-5">
      <!-- ═══ VUEXY TOP HEADER & ACTIONS ═══ -->
      <div class="vuexy-card p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1.5">
              <span class="vuexy-badge-primary">
                CMS Beranda & Footer
              </span>
              <span class="text-xs text-gray-500 font-medium">
                {{ stats.totalItems || 0 }} Item Konten Terkelola
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight mb-1">
              Pengaturan Teks, Gambar & Jadwal
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mb-0">
              Kelola teks, foto banner slider, layanan, jadwal & peta Google Maps, kontak, dan footer dengan mudah.
            </p>
          </div>

          <div class="flex items-center flex-wrap gap-2.5">
            <a
              href="/"
              target="_blank"
              class="vuexy-btn-secondary no-underline"
            >
              <VIcon size="16" class="me-1">mdi-open-in-new</VIcon>
              Lihat Beranda
            </a>
            <button
              @click="confirmReset"
              :disabled="resetting"
              class="vuexy-btn-warning"
            >
              <VIcon size="16" class="me-1">mdi-restore</VIcon>
              {{ resetting ? 'Mereset...' : 'Reset Default' }}
            </button>
            <button
              @click="saveChanges"
              :disabled="saving"
              class="vuexy-btn-primary"
            >
              <VIcon size="18" class="me-1.5">
                {{ saving ? 'mdi-loading mdi-spin' : 'mdi-content-save' }}
              </VIcon>
              {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ═══ VUEXY TABS CARD ═══ -->
      <div class="vuexy-card overflow-hidden">
        <!-- Navigation Tabs (Vuexy Pill Tabs Style) -->
        <div class="p-3 bg-gray-50/80 border-b border-gray-200">
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            <button
              @click="activeTab = 'hero'"
              :class="['vuexy-tab-btn', activeTab === 'hero' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-image-multiple</VIcon>
              <span>Hero Slider</span>
            </button>
            <button
              @click="activeTab = 'services'"
              :class="['vuexy-tab-btn', activeTab === 'services' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-star-four-points</VIcon>
              <span>Layanan Unggulan</span>
            </button>
            <button
              @click="activeTab = 'schedules'"
              :class="['vuexy-tab-btn', activeTab === 'schedules' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-calendar-clock</VIcon>
              <span>Jadwal & Peta</span>
            </button>
            <button
              @click="activeTab = 'salma'"
              :class="['vuexy-tab-btn', activeTab === 'salma' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-chat-processing</VIcon>
              <span>SALMA AI</span>
            </button>
            <button
              @click="activeTab = 'payment'"
              :class="['vuexy-tab-btn', activeTab === 'payment' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-contactless-payment</VIcon>
              <span>Pembayaran Digital</span>
            </button>
            <button
              @click="activeTab = 'contact'"
              :class="['vuexy-tab-btn', activeTab === 'contact' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-phone-in-talk</VIcon>
              <span>Kontak & Lokasi</span>
            </button>
            <button
              @click="activeTab = 'footer'"
              :class="['vuexy-tab-btn', activeTab === 'footer' ? 'vuexy-tab-btn--active' : '']"
            >
              <VIcon size="17">mdi-page-layout-footer</VIcon>
              <span>Footer & Branding</span>
            </button>
          </div>
        </div>

        <!-- Tab Body Contents -->
        <div class="p-5 sm:p-7">
          <!-- ════ TAB 1: HERO BANNER SLIDER ════ -->
          <div v-show="activeTab === 'hero'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Hero Banner & Slider</h3>
              <p class="text-xs text-gray-500 mb-0">Teks statis di bagian depan dan daftar gambar latar belakang slider.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="vuexy-form-label">Badge Teks Hero</label>
                <input
                  v-model="form.hero_badge"
                  type="text"
                  placeholder="Pelayanan Publik Resmi"
                  class="vuexy-form-input"
                />
              </div>
              <div class="md:col-span-2">
                <label class="vuexy-form-label">Judul Utama (Baris Baru = Enter)</label>
                <textarea
                  v-model="form.hero_title"
                  rows="2"
                  placeholder="Layanan Pajak&#10;Kendaraan Modern"
                  class="vuexy-form-input"
                ></textarea>
              </div>
            </div>

            <div>
              <label class="vuexy-form-label">Deskripsi / Subjudul Hero</label>
              <textarea
                v-model="form.hero_subtitle"
                rows="2"
                placeholder="Bayar pajak kendaraan bermotor dari mana saja..."
                class="vuexy-form-input"
              ></textarea>
            </div>

            <!-- Action buttons config -->
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
              <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">Tombol Aksi Banner</div>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                  <label class="vuexy-form-label">Tombol 1 (Merah) - Teks</label>
                  <input v-model="form.hero_cta_primary_text" type="text" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Tombol 1 - Target Scroll</label>
                  <input v-model="form.hero_cta_primary_target" type="text" placeholder="pembayaran" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Tombol 2 (Outline) - Teks</label>
                  <input v-model="form.hero_cta_secondary_text" type="text" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Tombol 2 - Target Scroll</label>
                  <input v-model="form.hero_cta_secondary_target" type="text" placeholder="kontak" class="vuexy-form-input" />
                </div>
              </div>
            </div>

            <!-- Background Images Manager -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-bold text-gray-800 mb-0.5">Foto Latar Slider (Rotasi Otomatis)</h4>
                  <p class="text-xs text-gray-500 mb-0">Unggah foto berkualitas tinggi (rasio 1920x800).</p>
                </div>
                <button
                  @click="addHeroImage"
                  class="vuexy-btn-primary-sm"
                >
                  <VIcon size="15" class="me-1">mdi-plus</VIcon>
                  Tambah Slide Foto
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="(imgUrl, idx) in form.hero_backgrounds || []"
                  :key="idx"
                  class="vuexy-subcard p-3 flex flex-col justify-between"
                >
                  <div class="relative w-full h-36 bg-gray-200 rounded-lg overflow-hidden mb-3 border border-gray-200">
                    <img :src="imgUrl" alt="Banner Preview" class="w-full h-full object-cover" />
                    <span class="absolute top-2 left-2 px-2 py-0.5 text-[11px] font-bold bg-black/70 text-white rounded">
                      Slide #{{ idx + 1 }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                    <input
                      :id="`hero-file-${idx}`"
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="uploadImageFile($event, 'hero_backgrounds', idx)"
                    />
                    <button
                      @click="triggerFileUpload(`hero-file-${idx}`)"
                      :disabled="uploading"
                      class="vuexy-btn-action-primary"
                    >
                      <VIcon size="14" class="me-1">mdi-camera</VIcon>
                      Ganti Foto
                    </button>

                    <button
                      @click="removeHeroImage(idx)"
                      class="vuexy-btn-action-danger"
                      title="Hapus Slide"
                    >
                      <VIcon size="16">mdi-trash-can-outline</VIcon>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ════ TAB 2: LAYANAN UNGGULAN ════ -->
          <div v-show="activeTab === 'services'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Bagian Layanan Unggulan</h3>
              <p class="text-xs text-gray-500 mb-0">Kelola judul, deskripsi pengantar, dan 6 kartu layanan publik.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="vuexy-form-label">Badge Layanan</label>
                <input v-model="form.services_badge" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Judul Utama</label>
                <input v-model="form.services_title" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Highlight Merah</label>
                <input v-model="form.services_title_highlight" type="text" class="vuexy-form-input" />
              </div>
            </div>

            <div>
              <label class="vuexy-form-label">Deskripsi Pengantar</label>
              <textarea v-model="form.services_desc" rows="2" class="vuexy-form-input"></textarea>
            </div>

            <div>
              <h4 class="text-sm font-bold text-gray-800 mb-3">6 Kartu Layanan Unggulan</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="(service, idx) in form.services_list || []"
                  :key="idx"
                  class="vuexy-subcard p-4 space-y-3"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded">
                      Layanan #{{ idx + 1 }}
                    </span>
                    <div class="flex items-center gap-1.5">
                      <span class="text-xs text-gray-500 font-medium">Aksen:</span>
                      <input type="color" v-model="service.color" class="w-6 h-6 rounded cursor-pointer border border-gray-300" />
                    </div>
                  </div>

                  <!-- Icon Picker Box -->
                  <div>
                    <label class="vuexy-form-label">Icon Layanan</label>
                    <div class="flex items-center gap-2">
                      <div class="w-10 h-10 rounded-lg border border-gray-300 bg-white flex items-center justify-center shadow-xs">
                        <VIcon :icon="service.icon" :color="service.color || '#C0392B'" size="22" />
                      </div>
                      <button
                        type="button"
                        @click="openIconPicker((selected) => service.icon = selected)"
                        class="vuexy-btn-icon-select flex-1"
                      >
                        <VIcon size="15" class="me-1">mdi-emoticon-outline</VIcon>
                        Pilih Icon ({{ service.icon }})
                      </button>
                    </div>
                  </div>

                  <div>
                    <label class="vuexy-form-label">Judul Layanan</label>
                    <input v-model="service.title" type="text" class="vuexy-form-input" />
                  </div>

                  <div>
                    <label class="vuexy-form-label">Deskripsi Layanan</label>
                    <textarea v-model="service.desc" rows="2" class="vuexy-form-input"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ════ TAB 3: JADWAL & LOKASI PETA ════ -->
          <div v-show="activeTab === 'schedules'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Jadwal Layanan & Peta Google Maps</h3>
              <p class="text-xs text-gray-500 mb-0">Kelola jadwal Samsat Keliling Pagi, Payment Point, dan Samsat Malam (BELOK WANGI).</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="vuexy-form-label">Badge Tag</label>
                <input v-model="form.schedules_badge" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Judul Utama</label>
                <input v-model="form.schedules_title" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Highlight Merah</label>
                <input v-model="form.schedules_title_highlight" type="text" class="vuexy-form-input" />
              </div>
            </div>

            <div>
              <label class="vuexy-form-label">Deskripsi Pengantar</label>
              <textarea v-model="form.schedules_desc" rows="2" class="vuexy-form-input"></textarea>
            </div>

            <!-- 1. Samsat Keliling Pagi -->
            <div class="vuexy-subcard p-4 space-y-3">
              <div class="flex items-center gap-2 mb-2">
                <VIcon color="#C0392B" size="20">mdi-bus-clock</VIcon>
                <h4 class="text-sm font-bold text-gray-800 mb-0">1. Jadwal Samsat Keliling Pagi (Senin – Sabtu)</h4>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                  v-for="(daySched, dIdx) in form.keliling_schedules || []"
                  :key="dIdx"
                  class="bg-white p-3 rounded-xl border border-gray-200 shadow-xs space-y-2"
                >
                  <div class="flex items-center justify-between border-b border-gray-100 pb-1.5">
                    <span class="text-xs font-bold text-gray-800">{{ daySched.day }} ({{ daySched.short }})</span>
                    <button
                      @click="addKelilingLocation(dIdx)"
                      class="text-xs text-red-600 hover:text-red-700 font-bold"
                    >
                      + Lokasi
                    </button>
                  </div>
                  <div v-for="(loc, lIdx) in daySched.locations || []" :key="lIdx" class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-gray-400 w-4">{{ lIdx + 1 }}.</span>
                    <input
                      v-model="daySched.locations[lIdx]"
                      type="text"
                      class="vuexy-form-input flex-1 !py-1 text-xs"
                    />
                    <button
                      @click="removeKelilingLocation(dIdx, lIdx)"
                      class="text-gray-400 hover:text-red-600 p-1"
                      title="Hapus"
                    >
                      <VIcon size="14">mdi-close</VIcon>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Payment Point Menetap -->
            <div class="vuexy-subcard p-4 space-y-3">
              <div class="flex items-center gap-2 mb-2">
                <VIcon color="#C0392B" size="20">mdi-office-building-marker</VIcon>
                <h4 class="text-sm font-bold text-gray-800 mb-0">2. Lokasi Layanan Payment Point Tetap</h4>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                  v-for="(place, pIdx) in form.layanan_menetap || []"
                  :key="pIdx"
                  class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-xs space-y-2.5"
                >
                  <div>
                    <label class="vuexy-form-label">Nama Lokasi</label>
                    <input v-model="place.name" type="text" class="vuexy-form-input" />
                  </div>
                  <div>
                    <label class="vuexy-form-label">Alamat / Patokan Maps</label>
                    <input v-model="place.address" type="text" class="vuexy-form-input" />
                  </div>
                  <div>
                    <label class="vuexy-form-label">Hari & Jam Buka</label>
                    <input v-model="place.hours" type="text" class="vuexy-form-input" />
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. BELOK WANGI (Clean White Card Style) -->
            <div class="vuexy-subcard p-4 space-y-3 border-amber-200 bg-amber-50/40">
              <div class="flex items-center gap-2 mb-2">
                <VIcon color="#D97706" size="20">mdi-weather-night</VIcon>
                <h4 class="text-sm font-bold text-gray-800 mb-0">3. Samsat Keliling Malam (BELOK WANGI)</h4>
                <span class="text-[11px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2 py-0.5 rounded">
                  Layanan Malam
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div
                  v-for="(belok, bIdx) in form.belok_wangi_schedules || []"
                  :key="bIdx"
                  class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-xs space-y-2.5"
                >
                  <div>
                    <label class="vuexy-form-label">Hari Operasional</label>
                    <input v-model="belok.days" type="text" class="vuexy-form-input" />
                  </div>
                  <div>
                    <label class="vuexy-form-label">Titik Lokasi</label>
                    <input v-model="belok.location" type="text" class="vuexy-form-input" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ════ TAB 4: SALMA AI SHOWCASE ════ -->
          <div v-show="activeTab === 'salma'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Bagian SALMA AI Showcase</h3>
              <p class="text-xs text-gray-500 mb-0">Kelola judul, deskripsi, status versi beta, 4 poin fitur, dan foto/GIF maskot SALMA AI.</p>
            </div>

            <!-- AI Beta Status Switcher -->
            <div class="p-4 bg-red-50/50 rounded-xl border border-red-200/80 flex items-center justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                  <VIcon size="22">mdi-robot-excited-outline</VIcon>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-800">Status Versi AI (Beta Mode)</span>
                    <span v-if="form.salma_is_beta" class="px-2 py-0.5 text-[10px] font-bold bg-red-600 text-white rounded-full">
                      BETA AKTIF
                    </span>
                    <span v-else class="px-2 py-0.5 text-[10px] font-bold bg-emerald-600 text-white rounded-full">
                      STABLE / PRODUKSI
                    </span>
                  </div>
                  <p class="text-xs text-gray-500 mb-0 mt-0.5">
                    Jika diaktifkan, label badge "BETA" akan ditampilkan di seluruh halaman AI Chat, identitas wajib pajak, dan profil layanan AI.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="form.salma_is_beta" class="sr-only peer" />
                  <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="vuexy-form-label">Badge Tag</label>
                <input v-model="form.salma_badge" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Judul Singkat</label>
                <input v-model="form.salma_title" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Nama Kepanjangan</label>
                <input v-model="form.salma_full_name" type="text" class="vuexy-form-input" />
              </div>
            </div>

            <div>
              <label class="vuexy-form-label">Deskripsi SALMA AI</label>
              <textarea v-model="form.salma_desc" rows="3" class="vuexy-form-input"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- 4 Features with Icon Picker -->
              <div class="vuexy-subcard p-4 space-y-3">
                <h4 class="text-sm font-bold text-gray-800 mb-2">4 Fitur Unggulan SALMA</h4>
                <div class="space-y-2.5">
                  <div v-for="(feat, fIdx) in form.salma_features || []" :key="fIdx" class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="openIconPicker((selected) => feat.icon = selected)"
                      class="w-10 h-10 rounded-lg border border-gray-300 bg-white flex items-center justify-center hover:border-red-500 transition shadow-xs flex-shrink-0"
                      title="Ganti Icon"
                    >
                      <VIcon :icon="feat.icon" size="20" color="#C0392B" />
                    </button>
                    <input v-model="feat.text" type="text" class="vuexy-form-input flex-1" />
                  </div>
                </div>
              </div>

              <!-- Mascot Uploader & CTA Text -->
              <div class="vuexy-subcard p-4 space-y-3">
                <h4 class="text-sm font-bold text-gray-800 mb-1">Foto / GIF Maskot SALMA</h4>
                <div class="flex items-center gap-3">
                  <div class="w-20 h-24 rounded-xl border border-gray-300 bg-white p-2 flex items-center justify-center shadow-xs flex-shrink-0">
                    <img :src="form.salma_mascot_image" alt="Mascot Preview" class="w-full h-full object-contain" />
                  </div>
                  <div class="flex-1 space-y-1.5">
                    <input
                      id="salma-mascot-file"
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="uploadImageFile($event, 'salma_mascot_image')"
                    />
                    <button
                      @click="triggerFileUpload('salma-mascot-file')"
                      :disabled="uploading"
                      class="vuexy-btn-action-primary"
                    >
                      <VIcon size="15" class="me-1">mdi-camera</VIcon>
                      Unggah Maskot Baru
                    </button>
                    <p class="text-[11px] text-gray-500 mb-0">Format: GIF animasi atau PNG transparan</p>
                  </div>
                </div>

                <div class="pt-2">
                  <label class="vuexy-form-label">Teks Tombol Chat</label>
                  <input v-model="form.salma_cta_text" type="text" class="vuexy-form-input" />
                </div>
              </div>
            </div>
          </div>

          <!-- ════ TAB 5: PEMBAYARAN DIGITAL (Fixed White Theme) ════ -->
          <div v-show="activeTab === 'payment'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Kanal Pembayaran Digital (e-Samsat)</h3>
              <p class="text-xs text-gray-500 mb-0">Kelola kategori dan platform pembayaran digital tanpa perlu isi link manual.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="vuexy-form-label">Badge Tag</label>
                <input v-model="form.payment_badge" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Judul Utama</label>
                <input v-model="form.payment_title" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Highlight Merah</label>
                <input v-model="form.payment_title_highlight" type="text" class="vuexy-form-input" />
              </div>
            </div>

            <div>
              <label class="vuexy-form-label">Deskripsi Pengantar</label>
              <textarea v-model="form.payment_desc" rows="2" class="vuexy-form-input"></textarea>
            </div>

            <!-- Categories and Platform Cards -->
            <div class="space-y-4">
              <div
                v-for="(cat, cIdx) in form.payment_categories || []"
                :key="cIdx"
                class="vuexy-subcard p-4 space-y-3"
              >
                <div class="flex items-center gap-2.5 border-b border-gray-200 pb-2.5">
                  <button
                    type="button"
                    @click="openIconPicker((selected) => cat.icon = selected)"
                    class="w-9 h-9 rounded-lg border border-gray-300 bg-white flex items-center justify-center hover:border-red-500 transition shadow-xs"
                    title="Pilih Icon Kategori"
                  >
                    <VIcon :icon="cat.icon" size="18" color="#C0392B" />
                  </button>
                  <div class="flex-1 max-w-xs">
                    <input v-model="cat.name" type="text" placeholder="Nama Kategori" class="vuexy-form-input !font-bold" />
                  </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                  <div
                    v-for="(platform, pIdx) in cat.platforms || []"
                    :key="pIdx"
                    class="bg-white p-3 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between space-y-2"
                  >
                    <div class="w-full h-14 bg-gray-50 rounded-lg border border-gray-100 flex items-center justify-center p-2">
                      <img :src="platform.logo" :alt="platform.name" class="max-h-9 max-w-full object-contain" />
                    </div>

                    <div>
                      <label class="vuexy-form-label !text-[10px]">Nama Platform</label>
                      <input
                        v-model="platform.name"
                        type="text"
                        placeholder="Nama"
                        class="vuexy-form-input !py-1 text-xs"
                      />
                    </div>

                    <input
                      :id="`pay-logo-${cIdx}-${pIdx}`"
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="uploadImageFile($event, 'payment_categories', cIdx, pIdx)"
                    />
                    <button
                      @click="triggerFileUpload(`pay-logo-${cIdx}-${pIdx}`)"
                      :disabled="uploading"
                      class="vuexy-btn-action-primary w-full text-center justify-center"
                    >
                      <VIcon size="13" class="me-1">mdi-camera</VIcon>
                      Ganti Logo
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ════ TAB 6: KONTAK & JAM OPERASIONAL ════ -->
          <div v-show="activeTab === 'contact'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Kontak & Jam Layanan</h3>
              <p class="text-xs text-gray-500 mb-0">Kelola alamat kantor, jam layanan hari biasa, Jumat, dan telepon kantor.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="vuexy-form-label">Badge Tag</label>
                <input v-model="form.contact_badge" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Judul Utama</label>
                <input v-model="form.contact_title" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Highlight Subjudul</label>
                <input v-model="form.contact_title_highlight" type="text" class="vuexy-form-input" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="vuexy-subcard p-4 space-y-3">
                <h4 class="text-sm font-bold text-gray-800 mb-1">Informasi Lokasi & Kontak</h4>
                <div>
                  <label class="vuexy-form-label">Alamat Kantor Utama</label>
                  <input v-model="form.contact_address" type="text" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Kabupaten & Kode Pos</label>
                  <input v-model="form.contact_city_postal" type="text" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Nomor Telepon Kantor</label>
                  <input v-model="form.contact_phone" type="text" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Jam Layanan (Senin – Kamis, Sabtu)</label>
                  <input v-model="form.contact_hours_weekday" type="text" class="vuexy-form-input" />
                </div>
                <div>
                  <label class="vuexy-form-label">Jam Layanan (Jumat)</label>
                  <input v-model="form.contact_hours_friday" type="text" class="vuexy-form-input" />
                </div>
              </div>

              <div class="vuexy-subcard p-4 space-y-3 flex flex-col">
                <h4 class="text-sm font-bold text-gray-800 mb-1">Kartu Bantuan Chat (Kanan Kontak)</h4>
                <div>
                  <label class="vuexy-form-label">Judul Kartu Bantuan</label>
                  <input v-model="form.contact_help_card_title" type="text" class="vuexy-form-input" />
                </div>
                <div class="flex-1">
                  <label class="vuexy-form-label">Deskripsi Kartu Bantuan</label>
                  <textarea v-model="form.contact_help_card_desc" rows="5" class="vuexy-form-input"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- ════ TAB 7: FOOTER & BRANDING ════ -->
          <div v-show="activeTab === 'footer'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-base font-bold text-gray-800 mb-0.5">Pengaturan Footer & Media Sosial</h3>
              <p class="text-xs text-gray-500 mb-0">Kelola identitas lembaga di footer, profil singkat, media sosial, dan teks copyright.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="vuexy-form-label">Nama Lembaga</label>
                <input v-model="form.footer_agency_name" type="text" class="vuexy-form-input" />
              </div>
              <div>
                <label class="vuexy-form-label">Sub-nama Lembaga</label>
                <input v-model="form.footer_agency_sub" type="text" class="vuexy-form-input" />
              </div>
            </div>

            <div>
              <label class="vuexy-form-label">Deskripsi Profil Lembaga</label>
              <textarea v-model="form.footer_agency_desc" rows="3" class="vuexy-form-input"></textarea>
            </div>

            <div>
              <label class="vuexy-form-label">Teks Hak Cipta (Copyright)</label>
              <input v-model="form.footer_copyright_text" type="text" class="vuexy-form-input" />
            </div>

            <!-- Social Media Links -->
            <div class="vuexy-subcard p-4 space-y-3">
              <h4 class="text-sm font-bold text-gray-800 mb-1">Tautan Akun Media Sosial Resmi</h4>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div
                  v-for="(soc, sIdx) in form.footer_social_links || []"
                  :key="sIdx"
                  class="bg-white p-3 rounded-xl border border-gray-200 shadow-xs space-y-2"
                >
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="openIconPicker((selected) => soc.icon = selected)"
                      class="w-8 h-8 rounded-lg border border-gray-300 bg-white flex items-center justify-center hover:border-red-500 transition shadow-xs"
                      title="Ganti Icon"
                    >
                      <VIcon :icon="soc.icon" size="17" color="#C0392B" />
                    </button>
                    <span class="text-xs font-bold capitalize text-gray-800">{{ soc.platform }}</span>
                  </div>
                  <div>
                    <label class="vuexy-form-label !text-[10px]">URL Profil Akun</label>
                    <input v-model="soc.url" type="text" placeholder="https://..." class="vuexy-form-input !py-1 text-xs" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ MDI ICON PICKER MODAL (VUEXY LIGHT STYLE) ═══ -->
    <VDialog v-model="iconDialog" max-width="640px" scrollable>
      <div class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-gray-200">
        <!-- Modal Header -->
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
              <VIcon size="18">mdi-emoticon-outline</VIcon>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-900 mb-0">Pilih Icon Material Design</h3>
              <p class="text-[11px] text-gray-500 mb-0">Pilih icon yang sesuai untuk tampilan kartu/menu</p>
            </div>
          </div>
          <button
            @click="iconDialog = false"
            class="text-gray-400 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-200 transition"
          >
            <VIcon size="18">mdi-close</VIcon>
          </button>
        </div>

        <!-- Search & Category Filters -->
        <div class="p-4 border-b border-gray-100 bg-white space-y-3">
          <div class="relative">
            <VIcon size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">mdi-magnify</VIcon>
            <input
              v-model="iconSearchQuery"
              type="text"
              placeholder="Cari icon (contoh: mobil, jam, peta, chat, uang, bintang)..."
              class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-red-500 focus:outline-none transition"
              autofocus
            />
          </div>

          <!-- Category Chips -->
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            <button
              v-for="cat in iconCategories"
              :key="cat"
              @click="iconActiveCategory = cat"
              :class="[
                'px-2.5 py-1 text-xs font-semibold rounded-lg transition whitespace-nowrap',
                iconActiveCategory === cat
                  ? 'bg-red-600 text-white shadow-xs'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
            >
              {{ cat }}
            </button>
          </div>
        </div>

        <!-- Icons Grid Body -->
        <div class="p-4 overflow-y-auto" style="max-height: 380px;">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button
              v-for="icon in filteredIcons"
              :key="icon.name"
              @click="selectIcon(icon.name)"
              class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-red-500 hover:bg-red-50/60 transition text-center group cursor-pointer"
            >
              <VIcon :icon="icon.name" size="26" class="text-gray-700 group-hover:text-red-600 mb-1.5 transition" />
              <span class="text-xs font-bold text-gray-800 line-clamp-1 group-hover:text-red-700">{{ icon.label }}</span>
              <span class="text-[10px] text-gray-400 font-mono">{{ icon.name }}</span>
            </button>
          </div>

          <div v-if="filteredIcons.length === 0" class="text-center py-8 text-gray-400">
            <VIcon size="36" class="mb-2">mdi-emoticon-sad-outline</VIcon>
            <p class="text-sm font-semibold mb-0">Icon tidak ditemukan untuk pencarian "{{ iconSearchQuery }}"</p>
          </div>
        </div>
      </div>
    </VDialog>

    <!-- Snackbar Notification -->
    <VSnackbar v-model="snackbar.show" :color="snackbar.color" location="top" :timeout="3500">
      {{ snackbar.message }}
    </VSnackbar>
  </AppLayout>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════
   VUEXY LIGHT THEME DESIGN SYSTEM (Clean, High-Contrast & Compact)
   ═══════════════════════════════════════════════════════════════════════════ */

.vuexy-cms-container {
  font-family: inherit;
}

/* ── VUEXY CARD ──────────────────────────────────────────────────────────── */
.vuexy-card {
  background: #ffffff;
  border: 1px solid #E6E6EC;
  border-radius: 14px;
  box-shadow: 0 2px 9px 0 rgba(47, 43, 61, 0.05), 0 0 1px 0 rgba(47, 43, 61, 0.08);
}

.vuexy-subcard {
  background: #F8F7FA;
  border: 1px solid #EBE9F1;
  border-radius: 12px;
}

/* ── VUEXY BADGES & BUTTONS ──────────────────────────────────────────────── */
.vuexy-badge-primary {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-weight: 700;
  color: #C0392B;
  background: rgba(192, 57, 43, 0.08);
  border: 1px solid rgba(192, 57, 43, 0.2);
  padding: 3px 10px;
  border-radius: 20px;
  letter-spacing: 0.02em;
}

.vuexy-btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #C0392B;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 8px 18px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(192, 57, 43, 0.35);
  transition: all 0.2s ease;
}
.vuexy-btn-primary:hover:not(:disabled) {
  background: #A93226;
  box-shadow: 0 4px 12px rgba(192, 57, 43, 0.45);
  transform: translateY(-1px);
}
.vuexy-btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.vuexy-btn-primary-sm {
  display: inline-flex;
  align-items: center;
  background: rgba(192, 57, 43, 0.08);
  color: #C0392B;
  border: 1px solid rgba(192, 57, 43, 0.2);
  font-size: 0.78rem;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.vuexy-btn-primary-sm:hover {
  background: #C0392B;
  color: #ffffff;
}

.vuexy-btn-secondary {
  display: inline-flex;
  align-items: center;
  background: #F1F0F2;
  color: #4B465C;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 8px 14px;
  border-radius: 10px;
  border: 1px solid #DBDADE;
  cursor: pointer;
  transition: all 0.2s ease;
}
.vuexy-btn-secondary:hover {
  background: #E8E7EA;
  color: #2F2B3D;
}

.vuexy-btn-warning {
  display: inline-flex;
  align-items: center;
  background: rgba(245, 158, 11, 0.08);
  color: #B45309;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 8px 14px;
  border-radius: 10px;
  border: 1px solid rgba(245, 158, 11, 0.25);
  cursor: pointer;
  transition: all 0.2s ease;
}
.vuexy-btn-warning:hover:not(:disabled) {
  background: rgba(245, 158, 11, 0.16);
}

.vuexy-btn-action-primary {
  display: inline-flex;
  align-items: center;
  background: rgba(192, 57, 43, 0.08);
  color: #C0392B;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 5px 10px;
  border-radius: 6px;
  border: 1px solid rgba(192, 57, 43, 0.2);
  cursor: pointer;
  transition: all 0.2s ease;
}
.vuexy-btn-action-primary:hover {
  background: #C0392B;
  color: #ffffff;
}

.vuexy-btn-action-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #EA5455;
  background: transparent;
  padding: 4px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}
.vuexy-btn-action-danger:hover {
  background: #FEE2E2;
}

.vuexy-btn-icon-select {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #DBDADE;
  color: #4B465C;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.vuexy-btn-icon-select:hover {
  border-color: #C0392B;
  color: #C0392B;
  background: rgba(192, 57, 43, 0.04);
}

/* ── VUEXY PILL TABS ─────────────────────────────────────────────────────── */
.vuexy-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #5D596C;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}
.vuexy-tab-btn:hover {
  background: #E8E7EA;
  color: #2F2B3D;
}
.vuexy-tab-btn--active {
  background: #C0392B !important;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(192, 57, 43, 0.35);
}

/* ── VUEXY FORM INPUTS ───────────────────────────────────────────────────── */
.vuexy-form-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #4B465C;
  letter-spacing: 0.03em;
  margin-bottom: 4px;
}

.vuexy-form-input {
  width: 100%;
  background: #ffffff;
  border: 1px solid #DBDADE;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 0.85rem;
  color: #2F2B3D;
  outline: none;
  transition: all 0.2s ease;
}
.vuexy-form-input:focus {
  border-color: #C0392B;
  box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.12);
}
.vuexy-form-input::placeholder {
  color: #A5A2AD;
}

/* Hide scrollbar for tab list */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
