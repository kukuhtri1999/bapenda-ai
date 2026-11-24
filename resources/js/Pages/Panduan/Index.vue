<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  guides: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  activeCategory: { type: String, default: null },
  searchQuery: { type: String, default: '' },
  seoTitle: { type: String, default: 'Panduan & FAQ Pajak Kendaraan — KB Samsat Lamongan' },
});

const logoUrl = '/images/logo-samsat-lamongan.png?v=20261124';
const queryInput = ref(props.searchQuery || '');

const submitSearch = () => {
  const params = {};
  if (queryInput.value.trim()) params.q = queryInput.value.trim();
  if (props.activeCategory) params.kategori = props.activeCategory;
  router.get('/panduan', params, { preserveState: true });
};

const selectCategory = (catName) => {
  const params = {};
  if (catName) params.kategori = catName;
  if (queryInput.value.trim()) params.q = queryInput.value.trim();
  router.get('/panduan', params, { preserveState: true });
};
</script>

<template>
  <VApp>
    <Head :title="seoTitle" />

    <div class="panduan-wrap">
      <!-- Top Govt Header -->
      <header class="panduan-header">
        <div class="panduan-header__inner">
          <Link href="/" class="panduan-brand text-decoration-none">
            <img :src="logoUrl" alt="Logo KB Samsat Lamongan" width="42" height="42" class="me-3 flex-shrink-0 object-contain" />
            <div>
              <div class="panduan-brand__name">KB Samsat Lamongan</div>
              <div class="panduan-brand__sub">Bapenda Provinsi Jawa Timur</div>
            </div>
          </Link>
          <div class="d-flex align-center gap-2">
            <Link href="/" class="panduan-nav-btn text-decoration-none d-none d-sm-inline-flex">
              <VIcon size="16" class="me-1">mdi-home-outline</VIcon>
              Beranda
            </Link>
            <Link href="/wajib-pajak" class="panduan-cta-btn text-decoration-none">
              <VIcon size="16" class="me-1">mdi-chat-processing</VIcon>
              Tanya SALMA AI
            </Link>
          </div>
        </div>
      </header>

      <!-- Hero Banner -->
      <section class="panduan-hero">
        <VContainer>
          <nav aria-label="Breadcrumb" class="panduan-breadcrumb mb-3">
            <Link href="/" class="text-decoration-none text-white opacity-75">Beranda</Link>
            <span class="mx-2 opacity-50">/</span>
            <span class="text-white font-weight-medium">Panduan &amp; FAQ Pajak</span>
          </nav>

          <h1 class="panduan-hero__title">
            Panduan Resmi Pajak Kendaraan KB Samsat Lamongan
          </h1>
          <p class="panduan-hero__desc">
            Informasi lengkap syarat, tarif, dasar hukum, balik nama (BBNKB), perpanjangan STNK, mutasi, pemutihan pajak, dan jadwal layanan di Kabupaten Lamongan.
          </p>

          <form @submit.prevent="submitSearch" class="panduan-search mt-5">
            <VTextField
              v-model="queryInput"
              placeholder="Cari panduan (misal: balik nama, STNK 5 tahunan, denda pajak, pemutihan)..."
              variant="solo"
              bg-color="white"
              hide-details
              clearable
              density="comfortable"
              prepend-inner-icon="mdi-magnify"
              @click:clear="() => { queryInput = ''; submitSearch(); }"
            >
              <template #append-inner>
                <VBtn type="submit" color="#C0392B" variant="flat" size="small">Cari</VBtn>
              </template>
            </VTextField>
          </form>
        </VContainer>
      </section>

      <!-- Main Directory -->
      <main class="panduan-main">
        <VContainer class="py-8">
          <!-- Category Filter Chips -->
          <div class="d-flex flex-wrap align-center gap-2 mb-6" v-if="categories.length">
            <VChip
              :color="!activeCategory ? '#C0392B' : undefined"
              :variant="!activeCategory ? 'flat' : 'outlined'"
              class="cursor-pointer"
              @click="selectCategory(null)"
            >
              Semua Kategori
            </VChip>
            <VChip
              v-for="cat in categories"
              :key="cat.name"
              :color="activeCategory === cat.name ? '#C0392B' : undefined"
              :variant="activeCategory === cat.name ? 'flat' : 'outlined'"
              class="cursor-pointer"
              @click="selectCategory(cat.name)"
            >
              {{ cat.name }} ({{ cat.total }})
            </VChip>
          </div>

          <!-- Guide Cards -->
          <VRow v-if="guides.data && guides.data.length">
            <VCol v-for="item in guides.data" :key="item.id" cols="12" md="6" lg="4">
              <article class="panduan-card h-100">
                <div class="d-flex align-center justify-space-between mb-2">
                  <VChip size="x-small" color="#C0392B" variant="tonal" class="font-weight-bold">
                    {{ item.category }}
                  </VChip>
                  <span v-if="item.updated_at" class="text-caption text-grey">{{ item.updated_at }}</span>
                </div>
                <h2 class="panduan-card__title mb-2">
                  <Link :href="item.url" class="text-decoration-none panduan-card__link">
                    {{ item.title }}
                  </Link>
                </h2>
                <p class="panduan-card__excerpt mb-4">
                  {{ item.excerpt }}
                </p>
                <Link :href="item.url" class="panduan-card__readmore text-decoration-none">
                  Baca Panduan Lengkap
                  <VIcon size="16" class="ms-1">mdi-arrow-right</VIcon>
                </Link>
              </article>
            </VCol>
          </VRow>

          <VCard v-else variant="outlined" class="pa-8 text-center my-6">
            <VIcon size="48" color="grey-lighten-1" class="mb-3">mdi-file-search-outline</VIcon>
            <h2 class="text-h6 font-weight-bold mb-2">Panduan tidak ditemukan</h2>
            <p class="text-body-2 text-grey-darken-1 mb-4">
              Coba kata kunci lain atau tanyakan langsung ke asisten cerdas SALMA AI.
            </p>
            <div class="d-flex justify-center gap-3 flex-wrap">
              <VBtn variant="outlined" @click="() => { queryInput = ''; selectCategory(null); }">Reset Filter</VBtn>
              <VBtn color="#C0392B" variant="flat" href="/wajib-pajak">Tanya SALMA AI</VBtn>
            </div>
          </VCard>

          <!-- Pagination -->
          <nav v-if="guides.links && guides.links.length > 3" aria-label="Navigasi Halaman" class="d-flex justify-center flex-wrap gap-2 mt-8">
            <template v-for="(link, idx) in guides.links" :key="idx">
              <Link
                v-if="link.url"
                :href="link.url"
                class="panduan-page-btn text-decoration-none"
                :class="{ 'panduan-page-btn--active': link.active }"
                v-html="link.label"
              />
              <span v-else class="panduan-page-btn panduan-page-btn--disabled" v-html="link.label" />
            </template>
          </nav>

          <!-- Bottom AI CTA -->
          <div class="panduan-bottom-cta mt-10">
            <div>
              <h2 class="text-h6 font-weight-bold text-white mb-1">Butuh Jawaban Spesifik Sesuai Kendaraan Anda?</h2>
              <p class="text-body-2 text-white mb-0" style="opacity: 0.85;">
                Konsultasi gratis 24 jam bersama asisten virtual resmi SALMA AI KB Samsat Lamongan.
              </p>
            </div>
            <Link href="/wajib-pajak" class="panduan-cta-white text-decoration-none">
              <VIcon size="18" class="me-2">mdi-chat-processing</VIcon>
              Mulai Konsultasi SALMA AI
            </Link>
          </div>
        </VContainer>
      </main>
    </div>
  </VApp>
</template>

<style scoped>
.panduan-wrap { min-height: 100vh; background: #F8FAFC; font-family: 'Poppins', 'Figtree', sans-serif; }
.panduan-header { background: #1B2838; color: #fff; position: sticky; top: 0; z-index: 40; box-shadow: 0 2px 12px rgba(0,0,0,.15); }
.panduan-header__inner { max-width: 1280px; margin: 0 auto; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.panduan-brand { display: flex; align-items: center; color: #fff; }
.panduan-brand__name { font-weight: 700; font-size: 1rem; line-height: 1.2; }
.panduan-brand__sub { font-size: 0.72rem; opacity: 0.75; }
.panduan-nav-btn { display: inline-flex; align-items: center; color: #fff; border: 1px solid rgba(255,255,255,.35); padding: 7px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 500; }
.panduan-nav-btn:hover { background: rgba(255,255,255,.1); }
.panduan-cta-btn { display: inline-flex; align-items: center; background: #C0392B; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; }
.panduan-cta-btn:hover { background: #A93226; }

.panduan-hero { background: linear-gradient(135deg, #1B2838 0%, #243B55 60%, #7B241C 100%); color: #fff; padding: 44px 0 52px; }
.panduan-breadcrumb { font-size: 0.84rem; }
.panduan-hero__title { font-size: clamp(1.6rem, 3vw, 2.35rem); font-weight: 700; line-height: 1.25; margin-bottom: 12px; max-width: 820px; }
.panduan-hero__desc { font-size: 0.98rem; opacity: 0.88; max-width: 740px; line-height: 1.65; margin-bottom: 0; }
.panduan-search { max-width: 680px; }

.panduan-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 14px; padding: 20px; display: flex; flex-direction: column; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.panduan-card:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(15,23,42,.08); border-color: #CBD5E1; }
.panduan-card__title { font-size: 1.03rem; font-weight: 700; line-height: 1.4; }
.panduan-card__link { color: #1E293B; }
.panduan-card__link:hover { color: #C0392B; }
.panduan-card__excerpt { font-size: 0.86rem; color: #475569; line-height: 1.6; flex-grow: 1; }
.panduan-card__readmore { display: inline-flex; align-items: center; font-size: 0.85rem; font-weight: 600; color: #C0392B; margin-top: auto; }

.panduan-page-btn { padding: 7px 13px; border-radius: 8px; border: 1px solid #CBD5E1; background: #fff; color: #334155; font-size: 0.85rem; font-weight: 500; }
.panduan-page-btn--active { background: #C0392B; border-color: #C0392B; color: #fff; }
.panduan-page-btn--disabled { opacity: 0.45; cursor: not-allowed; }

.panduan-bottom-cta { background: linear-gradient(135deg, #1B2838 0%, #C0392B 100%); border-radius: 16px; padding: 28px 32px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; }
.panduan-cta-white { display: inline-flex; align-items: center; background: #fff; color: #C0392B; padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; white-space: nowrap; }
</style>
