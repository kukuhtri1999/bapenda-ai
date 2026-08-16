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
      // Deep clone objects/arrays
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

// ── Image Upload Helper ──────────────────────────────────────────────────────
const uploadImageFile = async (event, targetKey, arrayIndex = null, arrayField = null) => {
  const file = event.target.files[0];
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

// ── Array Helpers for Dynamic Content ─────────────────────────────────────────
const addHeroImage = () => {
  if (!Array.isArray(form.hero_backgrounds)) form.hero_backgrounds = [];
  form.hero_backgrounds.push('https://picsum.photos/1920/800');
};

const removeHeroImage = (idx) => {
  if (confirm('Hapus gambar latar slider ini?')) {
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
    <div class="cms-page p-4 sm:p-6 max-w-7xl mx-auto">
      <!-- ═══ TOP HEADER & ACTIONS ═══ -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-red-50 text-red-700 border border-red-200">
              Content Management System
            </span>
            <span class="text-xs text-gray-500">
              {{ stats.totalItems || 0 }} Item Konten Terkelola
            </span>
          </div>
          <h1 class="text-2xl font-black text-gray-900 tracking-tight">
            CMS Beranda & Footer
          </h1>
          <p class="text-sm text-gray-500">
            Kelola teks, gambar latar slider, jadwal layanan, peta lokasi, kontak, dan branding footer secara dinamis.
          </p>
        </div>

        <div class="flex items-center flex-wrap gap-2">
          <a
            href="/"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition no-underline"
          >
            <VIcon size="16">mdi-open-in-new</VIcon>
            Lihat Beranda
          </a>
          <button
            @click="confirmReset"
            :disabled="resetting"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition disabled:opacity-50"
          >
            <VIcon size="16">mdi-restore</VIcon>
            {{ resetting ? 'Mereset...' : 'Reset Default' }}
          </button>
          <button
            @click="saveChanges"
            :disabled="saving"
            class="inline-flex items-center gap-1.5 px-6 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-md hover:shadow-lg transition disabled:opacity-50"
          >
            <VIcon size="18">{{ saving ? 'mdi-loading mdi-spin' : 'mdi-content-save' }}</VIcon>
            {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </div>

      <!-- ═══ SECTION TABS ═══ -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <VTabs v-model="activeTab" color="#C0392B" bg-color="#F8FAFC" class="border-b border-gray-200" show-arrows>
          <VTab value="hero"><VIcon start size="18">mdi-image-multiple</VIcon> Hero Banner Slider</VTab>
          <VTab value="services"><VIcon start size="18">mdi-star-four-points</VIcon> Layanan Unggulan</VTab>
          <VTab value="schedules"><VIcon start size="18">mdi-calendar-clock</VIcon> Jadwal & Lokasi Peta</VTab>
          <VTab value="salma"><VIcon start size="18">mdi-chat-processing</VIcon> SALMA AI Showcase</VTab>
          <VTab value="payment"><VIcon start size="18">mdi-contactless-payment</VIcon> Pembayaran Digital</VTab>
          <VTab value="contact"><VIcon start size="18">mdi-phone-in-talk</VIcon> Kontak & Jam Operasional</VTab>
          <VTab value="footer"><VIcon start size="18">mdi-page-layout-footer</VIcon> Footer & Branding</VTab>
        </VTabs>

        <div class="p-6">
          <VWindow v-model="activeTab">
            <!-- ════ TAB 1: HERO BANNER SLIDER ════ -->
            <VWindowItem value="hero">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Hero Banner & Slider</h3>
                  <p class="text-xs text-gray-500">Teks statis di bagian depan dan daftar gambar latar belakang slider.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Badge Teks Hero</label>
                    <VTextField v-model="form.hero_badge" variant="outlined" density="comfortable" placeholder="Pelayanan Publik Resmi" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Utama (Baris Baru = Enter)</label>
                    <VTextarea v-model="form.hero_title" rows="2" variant="outlined" density="comfortable" placeholder="Layanan Pajak\nKendaraan Modern" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi / Subjudul Hero</label>
                  <VTextarea v-model="form.hero_subtitle" rows="3" variant="outlined" density="comfortable" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tombol 1 (Merah) - Teks</label>
                    <VTextField v-model="form.hero_cta_primary_text" variant="outlined" density="compact" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tombol 1 - Target Scroll</label>
                    <VTextField v-model="form.hero_cta_primary_target" variant="outlined" density="compact" hint="ID target: pembayaran, layanan, salma" persistent-hint />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tombol 2 (Outline) - Teks</label>
                    <VTextField v-model="form.hero_cta_secondary_text" variant="outlined" density="compact" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tombol 2 - Target Scroll</label>
                    <VTextField v-model="form.hero_cta_secondary_target" variant="outlined" density="compact" hint="ID target: kontak, jadwal" persistent-hint />
                  </div>
                </div>

                <!-- Background Images Manager -->
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <div>
                      <h4 class="text-sm font-bold text-gray-900">Gambar Latar Belakang Slider</h4>
                      <p class="text-xs text-gray-500">Gambar berotasi otomatis setiap 5 detik dengan efek crossfade.</p>
                    </div>
                    <button
                      @click="addHeroImage"
                      class="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-lg transition"
                    >
                      <VIcon size="14">mdi-plus</VIcon> Tambah Gambar
                    </button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                      v-for="(imgUrl, idx) in form.hero_backgrounds || []"
                      :key="idx"
                      class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col justify-between"
                    >
                      <div class="mb-3">
                        <div class="relative w-full h-36 bg-gray-200 rounded-lg overflow-hidden mb-2 border border-gray-300">
                          <img :src="imgUrl" alt="Preview Banner" class="w-full h-full object-cover" />
                          <span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-bold bg-black/60 text-white rounded">
                            Slide #{{ idx + 1 }}
                          </span>
                        </div>
                        <VTextField
                          v-model="form.hero_backgrounds[idx]"
                          label="URL Gambar"
                          variant="outlined"
                          density="compact"
                          class="mb-2"
                        />
                      </div>

                      <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                        <label class="cursor-pointer text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                          <VIcon size="14">mdi-upload</VIcon> Unggah Foto
                          <input type="file" accept="image/*" class="hidden" @change="uploadImageFile($event, 'hero_backgrounds', idx)" />
                        </label>
                        <button
                          @click="removeHeroImage(idx)"
                          class="text-xs font-semibold text-red-600 hover:text-red-700 flex items-center gap-1"
                        >
                          <VIcon size="14">mdi-delete</VIcon> Hapus
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </VWindowItem>

            <!-- ════ TAB 2: LAYANAN UNGGULAN ════ -->
            <VWindowItem value="services">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Bagian Layanan Unggulan</h3>
                  <p class="text-xs text-gray-500">Ubah judul, pengantar, dan rincian kartu 6 layanan utama.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Badge Tag</label>
                    <VTextField v-model="form.services_badge" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Utama</label>
                    <VTextField v-model="form.services_title" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Highlight Merah</label>
                    <VTextField v-model="form.services_title_highlight" variant="outlined" density="comfortable" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi Pengantar</label>
                  <VTextarea v-model="form.services_desc" rows="2" variant="outlined" density="comfortable" />
                </div>

                <!-- Services Cards Editor -->
                <div>
                  <h4 class="text-sm font-bold text-gray-900 mb-3">Daftar Kartu Layanan (6 Kartu)</h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                      v-for="(service, idx) in form.services_list || []"
                      :key="idx"
                      class="p-4 rounded-xl border border-gray-200 bg-gray-50 space-y-3"
                    >
                      <div class="flex items-center justify-between">
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-white border border-gray-300 text-gray-700">
                          Kartu #{{ idx + 1 }}
                        </span>
                        <div class="flex items-center gap-2">
                          <label class="text-xs font-bold text-gray-600">Aksen:</label>
                          <input type="color" v-model="service.color" class="w-6 h-6 rounded cursor-pointer border border-gray-300" />
                        </div>
                      </div>

                      <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-1">
                          <VTextField v-model="service.icon" label="MDI Icon" variant="outlined" density="compact" />
                        </div>
                        <div class="col-span-2">
                          <VTextField v-model="service.title" label="Judul Layanan" variant="outlined" density="compact" />
                        </div>
                      </div>

                      <VTextarea v-model="service.desc" label="Deskripsi Layanan" rows="2" variant="outlined" density="compact" />
                    </div>
                  </div>
                </div>
              </div>
            </VWindowItem>

            <!-- ════ TAB 3: JADWAL & LOKASI PETA ════ -->
            <VWindowItem value="schedules">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Jadwal Layanan & Peta Google Maps</h3>
                  <p class="text-xs text-gray-500">Kelola jadwal Samsat Keliling, Payment Point, dan Samsat Malam (BELOK WANGI).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Badge Tag</label>
                    <VTextField v-model="form.schedules_badge" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Utama</label>
                    <VTextField v-model="form.schedules_title" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Highlight Merah</label>
                    <VTextField v-model="form.schedules_title_highlight" variant="outlined" density="comfortable" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi Pengantar</label>
                  <VTextarea v-model="form.schedules_desc" rows="2" variant="outlined" density="comfortable" />
                </div>

                <!-- Samsat Keliling Schedule -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                  <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <VIcon color="#C0392B" size="18">mdi-bus-clock</VIcon>
                    1. Jadwal Samsat Keliling Pagi (Senin – Sabtu)
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                      v-for="(daySched, dIdx) in form.keliling_schedules || []"
                      :key="dIdx"
                      class="bg-white p-3 rounded-lg border border-gray-200 space-y-2"
                    >
                      <div class="flex items-center justify-between font-bold text-sm text-gray-800 border-b border-gray-100 pb-1">
                        <span>{{ daySched.day }} ({{ daySched.short }})</span>
                        <button
                          @click="addKelilingLocation(dIdx)"
                          class="text-xs text-red-600 hover:text-red-700 font-semibold"
                        >
                          + Titik Lokasi
                        </button>
                      </div>
                      <div v-for="(loc, lIdx) in daySched.locations || []" :key="lIdx" class="flex items-center gap-1">
                        <span class="text-xs font-bold text-gray-400 w-4">{{ lIdx + 1 }}.</span>
                        <input
                          v-model="daySched.locations[lIdx]"
                          type="text"
                          class="flex-1 text-xs px-2 py-1.5 rounded border border-gray-300 focus:outline-none focus:border-red-500"
                        />
                        <button
                          @click="removeKelilingLocation(dIdx, lIdx)"
                          class="text-gray-400 hover:text-red-600 p-1"
                          title="Hapus lokasi"
                        >
                          <VIcon size="14">mdi-close</VIcon>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Payment Point Menetap -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                  <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <VIcon color="#C0392B" size="18">mdi-office-building-marker</VIcon>
                    2. Lokasi Layanan Payment Point Tetap
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                      v-for="(place, pIdx) in form.layanan_menetap || []"
                      :key="pIdx"
                      class="bg-white p-3 rounded-lg border border-gray-200 space-y-2"
                    >
                      <VTextField v-model="place.name" label="Nama Lokasi" variant="outlined" density="compact" />
                      <VTextField v-model="place.address" label="Alamat / Patokan" variant="outlined" density="compact" />
                      <VTextField v-model="place.hours" label="Hari & Jam Buka" variant="outlined" density="compact" />
                    </div>
                  </div>
                </div>

                <!-- BELOK WANGI -->
                <div class="p-4 bg-gray-900 text-white rounded-xl border border-amber-400/30">
                  <h4 class="text-sm font-bold text-amber-300 mb-3 flex items-center gap-2">
                    <VIcon color="#FFD700" size="18">mdi-weather-night</VIcon>
                    3. Samsat Keliling Malam (BELOK WANGI)
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                      v-for="(belok, bIdx) in form.belok_wangi_schedules || []"
                      :key="bIdx"
                      class="bg-white/10 p-3 rounded-lg border border-white/15 space-y-2"
                    >
                      <VTextField v-model="belok.days" label="Hari Operasional" variant="outlined" density="compact" dark />
                      <VTextField v-model="belok.location" label="Titik Lokasi" variant="outlined" density="compact" dark />
                    </div>
                  </div>
                </div>
              </div>
            </VWindowItem>

            <!-- ════ TAB 4: SALMA AI SHOWCASE ════ -->
            <VWindowItem value="salma">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Bagian SALMA AI Showcase</h3>
                  <p class="text-xs text-gray-500">Kelola judul, deskripsi, 4 poin fitur, dan gambar maskot SALMA AI.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Badge Tag</label>
                    <VTextField v-model="form.salma_badge" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Singkat</label>
                    <VTextField v-model="form.salma_title" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Kepanjangan</label>
                    <VTextField v-model="form.salma_full_name" variant="outlined" density="comfortable" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi SALMA AI</label>
                  <VTextarea v-model="form.salma_desc" rows="3" variant="outlined" density="comfortable" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Features List -->
                  <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-900 mb-3">4 Fitur Unggulan SALMA</h4>
                    <div class="space-y-2">
                      <div v-for="(feat, fIdx) in form.salma_features || []" :key="fIdx" class="flex gap-2">
                        <VTextField v-model="feat.icon" label="MDI Icon" variant="outlined" density="compact" style="max-width: 140px;" />
                        <VTextField v-model="feat.text" label="Teks Fitur" variant="outlined" density="compact" class="flex-1" />
                      </div>
                    </div>
                  </div>

                  <!-- Mascot & CTA -->
                  <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-4">
                    <h4 class="text-sm font-bold text-gray-900 mb-2">Maskot & Tombol Percakapan</h4>
                    <div class="flex items-center gap-4">
                      <img :src="form.salma_mascot_image" alt="Mascot" class="w-16 h-20 object-contain bg-white rounded-lg border border-gray-300 p-1" />
                      <div class="flex-1">
                        <VTextField v-model="form.salma_mascot_image" label="URL Maskot (GIF/PNG)" variant="outlined" density="compact" />
                        <label class="cursor-pointer text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 mt-1">
                          <VIcon size="14">mdi-upload</VIcon> Unggah Maskot Baru
                          <input type="file" accept="image/*" class="hidden" @change="uploadImageFile($event, 'salma_mascot_image')" />
                        </label>
                      </div>
                    </div>
                    <VTextField v-model="form.salma_cta_text" label="Teks Tombol Percakapan" variant="outlined" density="compact" />
                  </div>
                </div>
              </div>
            </VWindowItem>

            <!-- ════ TAB 5: PEMBAYARAN DIGITAL ════ -->
            <VWindowItem value="payment">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Kanal Pembayaran Digital</h3>
                  <p class="text-xs text-gray-500">Kelola kategori dan platform e-Samsat (E-Commerce, E-Wallet, Perbankan).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Badge Tag</label>
                    <VTextField v-model="form.payment_badge" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Utama</label>
                    <VTextField v-model="form.payment_title" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Highlight Merah</label>
                    <VTextField v-model="form.payment_title_highlight" variant="outlined" density="comfortable" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi Pengantar</label>
                  <VTextarea v-model="form.payment_desc" rows="2" variant="outlined" density="comfortable" />
                </div>

                <!-- Categories & Platforms -->
                <div class="space-y-4">
                  <div
                    v-for="(cat, cIdx) in form.payment_categories || []"
                    :key="cIdx"
                    class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3"
                  >
                    <div class="flex items-center gap-3">
                      <VTextField v-model="cat.name" label="Nama Kategori" variant="outlined" density="compact" />
                      <VTextField v-model="cat.icon" label="MDI Icon" variant="outlined" density="compact" style="max-width: 160px;" />
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                      <div
                        v-for="(platform, pIdx) in cat.platforms || []"
                        :key="pIdx"
                        class="bg-white p-3 rounded-lg border border-gray-200 space-y-2"
                      >
                        <div class="w-full h-12 flex items-center justify-center bg-gray-50 rounded border border-gray-100">
                          <img :src="platform.logo" :alt="platform.name" class="max-h-8 object-contain" />
                        </div>
                        <VTextField v-model="platform.name" label="Nama Platform" variant="outlined" density="compact" />
                        <VTextField v-model="platform.logo" label="Logo URL" variant="outlined" density="compact" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </VWindowItem>

            <!-- ════ TAB 6: KONTAK & JAM OPERASIONAL ════ -->
            <VWindowItem value="contact">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Informasi Kontak & Jam Operasional</h3>
                  <p class="text-xs text-gray-500">Kelola alamat kantor, jam layanan, nomor telepon, dan kartu bantuan chat.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Badge Tag</label>
                    <VTextField v-model="form.contact_badge" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Utama</label>
                    <VTextField v-model="form.contact_title" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Highlight Putih</label>
                    <VTextField v-model="form.contact_title_highlight" variant="outlined" density="comfortable" />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <h4 class="text-sm font-bold text-gray-900">Informasi Alamat & Jam Buka</h4>
                    <VTextField v-model="form.contact_address" label="Alamat Kantor" variant="outlined" density="compact" />
                    <VTextField v-model="form.contact_city_postal" label="Kabupaten & Kode Pos" variant="outlined" density="compact" />
                    <VTextField v-model="form.contact_hours_weekday" label="Jam Layanan (Senin - Kamis, Sabtu)" variant="outlined" density="compact" />
                    <VTextField v-model="form.contact_hours_friday" label="Jam Layanan (Jumat)" variant="outlined" density="compact" />
                    <VTextField v-model="form.contact_phone" label="Nomor Telepon Kantor" variant="outlined" density="compact" />
                  </div>

                  <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                    <h4 class="text-sm font-bold text-gray-900">Kartu Bantuan Chat (Kanan)</h4>
                    <VTextField v-model="form.contact_help_card_title" label="Judul Kartu" variant="outlined" density="compact" />
                    <VTextarea v-model="form.contact_help_card_desc" label="Deskripsi Kartu" rows="4" variant="outlined" density="compact" />
                  </div>
                </div>
              </div>
            </VWindowItem>

            <!-- ════ TAB 7: FOOTER & BRANDING ════ -->
            <VWindowItem value="footer">
              <div class="space-y-6">
                <div class="border-b border-gray-100 pb-3">
                  <h3 class="text-lg font-bold text-gray-900">Pengaturan Footer & Media Sosial</h3>
                  <p class="text-xs text-gray-500">Kelola identitas lembaga, profil singkat, media sosial, dan teks copyright.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lembaga</label>
                    <VTextField v-model="form.footer_agency_name" variant="outlined" density="comfortable" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Sub-nama Lembaga</label>
                    <VTextField v-model="form.footer_agency_sub" variant="outlined" density="comfortable" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi Singkat Lembaga</label>
                  <VTextarea v-model="form.footer_agency_desc" rows="3" variant="outlined" density="comfortable" />
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Teks Hak Cipta (Copyright)</label>
                  <VTextField v-model="form.footer_copyright_text" variant="outlined" density="comfortable" />
                </div>

                <!-- Social Media Links -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                  <h4 class="text-sm font-bold text-gray-900 mb-3">Tautan Akun Media Sosial</h4>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                      v-for="(soc, sIdx) in form.footer_social_links || []"
                      :key="sIdx"
                      class="bg-white p-3 rounded-lg border border-gray-200 space-y-2"
                    >
                      <div class="flex items-center gap-2 font-bold text-xs capitalize text-gray-700">
                        <VIcon size="16">{{ soc.icon }}</VIcon> {{ soc.platform }}
                      </div>
                      <VTextField v-model="soc.url" label="URL Profil" variant="outlined" density="compact" />
                    </div>
                  </div>
                </div>
              </div>
            </VWindowItem>
          </VWindow>
        </div>
      </div>
    </div>

    <!-- Snackbar Notification -->
    <VSnackbar v-model="snackbar.show" :color="snackbar.color" location="top" :timeout="3500">
      {{ snackbar.message }}
    </VSnackbar>
  </AppLayout>
</template>

<style scoped>
.cms-page :deep(.v-field) {
  border-radius: 10px !important;
}
.cms-page :deep(.v-tab) {
  text-transform: none;
  font-weight: 600;
  font-size: 0.85rem;
}
</style>
