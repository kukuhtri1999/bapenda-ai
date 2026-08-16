<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import FloatingChat from '@/Components/FloatingChat.vue';
import PwaInstallButton from '@/Components/PwaInstallButton.vue';

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
  laravelVersion: { type: String, required: true },
  phpVersion: { type: String, required: true },
  cms: { type: Object, default: () => ({}) },
});

const logoUrl = import.meta.env.VITE_APP_LOGO;
const isChatPageOpen = ref(false);
const mobileMenu = ref(false);
const navSolid = ref(false);

// Helper getter with robust fallback
const getCms = (key, fallback) => {
  return props.cms && props.cms[key] !== undefined && props.cms[key] !== null && props.cms[key] !== ''
    ? props.cms[key]
    : fallback;
};

// ── Hero Section (Dynamic via CMS) ───────────────────────────────────────────
const heroBadge = computed(() => getCms('hero_badge', 'Pelayanan Publik Resmi'));
const heroTitle = computed(() => getCms('hero_title', "Layanan Pajak\nKendaraan Modern"));
const heroSubtitle = computed(() => getCms('hero_subtitle', 'Bayar pajak kendaraan bermotor dari mana saja, kapan saja melalui berbagai kanal digital dan layanan resmi Samsat Lamongan.'));
const heroCtaPrimaryText = computed(() => getCms('hero_cta_primary_text', 'Cara Bayar'));
const heroCtaPrimaryTarget = computed(() => getCms('hero_cta_primary_target', 'pembayaran'));
const heroCtaSecondaryText = computed(() => getCms('hero_cta_secondary_text', 'Hubungi Kami'));
const heroCtaSecondaryTarget = computed(() => getCms('hero_cta_secondary_target', 'kontak'));
const heroBackgrounds = computed(() => getCms('hero_backgrounds', [
  'https://picsum.photos/id/1076/1920/800',
  'https://picsum.photos/id/1048/1920/800',
  'https://picsum.photos/id/180/1920/800',
]));

const activeSlide = ref(0);
let slideInterval = null;

const nextSlide = () => {
  if (!heroBackgrounds.value || heroBackgrounds.value.length === 0) return;
  activeSlide.value = (activeSlide.value + 1) % heroBackgrounds.value.length;
};
const prevSlide = () => {
  if (!heroBackgrounds.value || heroBackgrounds.value.length === 0) return;
  activeSlide.value = (activeSlide.value - 1 + heroBackgrounds.value.length) % heroBackgrounds.value.length;
};
const goToSlide = (idx) => {
  activeSlide.value = idx;
  resetSlideInterval();
};
const resetSlideInterval = () => {
  clearInterval(slideInterval);
  slideInterval = setInterval(nextSlide, 5000);
};

// ── Layanan Unggulan (Dynamic via CMS) ───────────────────────────────────────
const servicesBadge = computed(() => getCms('services_badge', 'Layanan Kami'));
const servicesTitle = computed(() => getCms('services_title', 'Layanan Unggulan'));
const servicesTitleHighlight = computed(() => getCms('services_title_highlight', 'KB Samsat Lamongan'));
const servicesDesc = computed(() => getCms('services_desc', 'Berbagai layanan perpajakan dan kesamsatan untuk memudahkan masyarakat Lamongan dan Jawa Timur'));
const layananUnggulan = computed(() => getCms('services_list', [
  { icon: 'mdi-car-side', title: 'Pajak Tahunan', desc: 'Pembayaran Pajak Kendaraan Bermotor (PKB) tahunan dengan mudah dan cepat tanpa antri lama.', color: '#C0392B' },
  { icon: 'mdi-card-account-details-outline', title: 'STNK 5 Tahunan', desc: 'Perpanjangan masa berlaku STNK dan penggantian plat nomor kendaraan (TNKB) 5 tahunan.', color: '#1B2838' },
  { icon: 'mdi-swap-horizontal-bold', title: 'Balik Nama (BBNKB)', desc: 'Proses Bea Balik Nama Kendaraan Bermotor antar pemilik pertama ke pemilik berikutnya.', color: '#2980B9' },
  { icon: 'mdi-file-document-swap-outline', title: 'Mutasi Masuk / Keluar', desc: 'Proses administrasi perpindahan berkas kendaraan bermotor antar wilayah kabupaten atau provinsi.', color: '#27AE60' },
  { icon: 'mdi-bus-clock', title: 'Samsat Keliling', desc: 'Layanan pembayaran pajak tahunan bergerak yang hadir di berbagai kecamatan di Lamongan.', color: '#8E44AD' },
  { icon: 'mdi-weather-night', title: 'BELOK WANGI', desc: 'Beda Lokasi Wayah Bengi — Layanan Samsat Keliling Malam setiap pukul 18.00–20.00 WIB.', color: '#E67E22' },
]));

// ── Jadwal & Lokasi State (Dynamic via CMS) ──────────────────────────────────
const schedulesBadge = computed(() => getCms('schedules_badge', 'Jadwal & Lokasi'));
const schedulesTitle = computed(() => getCms('schedules_title', 'Jadwal Layanan'));
const schedulesTitleHighlight = computed(() => getCms('schedules_title_highlight', '& Lokasi Samsat'));
const schedulesDesc = computed(() => getCms('schedules_desc', 'Pilih lokasi layanan untuk melihat peta dan petunjuk arah langsung'));

const activeServiceTab = ref(0);
const activeKelilingDay = ref(0);
const selectedKelilingLoc = ref(0);
const selectedMenetapLoc = ref(0);
const selectedBelokLoc = ref(0);

const kelilingPagiSchedule = computed(() => getCms('keliling_schedules', [
  { day: 'Senin', short: 'Sen', locations: ['Pertigaan Sambopinggir (Karangbinangun)', 'Depan Pantai Lorena (Paciran)', 'Depan Terminal MPU Sukodadi'] },
  { day: 'Selasa', short: 'Sel', locations: ['Depan Kantor Kec. Karanggeneng', 'Jl. Raya Pangean (Maduran)', 'Depan Kantor Kec. Kembangbahu'] },
  { day: 'Rabu', short: 'Rab', locations: ['Depan Kantor Kec. Mantup', 'Balai Desa Sugio', 'Jl. Raya Pangean (Maduran)'] },
  { day: 'Kamis', short: 'Kam', locations: ['Desa Kandangrejo (Kedungpring)', 'Kantor Kec. Modo', 'Depan Masjid Moropelang (Babat)'] },
  { day: 'Jumat', short: 'Jum', locations: ['Balai Desa Puter (Kembangbahu)', 'Depan Pantai Lorena (Paciran)', 'Samping Koramil Sugio'] },
  { day: 'Sabtu', short: 'Sab', locations: ['Depan Kantor Kec. Mantup', 'Kantor Kec. Karanggeneng', 'Pertigaan Lonjong (Glagah)'] },
]));

const layananMenetap = computed(() => getCms('layanan_menetap', [
  { name: 'Samsat Walkthru', address: 'Jl. Veteran No. 2, Lamongan', hours: 'Senin – Sabtu', icon: 'mdi-office-building-marker', color: '#C0392B' },
  { name: 'Mal Pelayanan Publik (MPP)', address: 'Jl. Lamongrejo No. 120, Lamongan', hours: 'Senin – Jumat', icon: 'mdi-domain', color: '#1B2838' },
  { name: 'Payment Point Ngimbang', address: 'Kantor Kec. Ngimbang, Lamongan', hours: 'Senin – Jumat', icon: 'mdi-map-marker-radius-outline', color: '#2980B9' },
  { name: 'Payment Point Babat', address: 'Bank Jatim KCP Babat, Lamongan', hours: 'Senin – Jumat', icon: 'mdi-map-marker-radius-outline', color: '#2980B9' },
  { name: 'Payment Point Brondong', address: 'Bank Jatim KCP Brondong, Lamongan', hours: 'Senin – Jumat', icon: 'mdi-map-marker-radius-outline', color: '#2980B9' },
]));

const belokWangiSchedule = computed(() => getCms('belok_wangi_schedules', [
  { days: 'Senin & Kamis', location: 'Depan Kantor KB Samsat Lamongan', icon: 'mdi-office-building' },
  { days: 'Selasa & Jumat', location: 'Alun-Alun Lamongan', icon: 'mdi-city-variant-outline' },
  { days: 'Rabu', location: 'Terminal Sukodadi Lamongan', icon: 'mdi-bus-stop' },
]));

// Computed Maps Queries
const currentKelilingMapQuery = computed(() => {
  const daySchedule = kelilingPagiSchedule.value[activeKelilingDay.value];
  if (!daySchedule || !daySchedule.locations || !daySchedule.locations[selectedKelilingLoc.value]) {
    return 'KB Samsat Lamongan';
  }
  const loc = daySchedule.locations[selectedKelilingLoc.value];
  return `${loc}, Lamongan, Jawa Timur`;
});

const currentKelilingLocName = computed(() => {
  const daySchedule = kelilingPagiSchedule.value[activeKelilingDay.value];
  return daySchedule?.locations?.[selectedKelilingLoc.value] || 'Lokasi Samsat Keliling';
});

const currentMenetapMapQuery = computed(() => {
  const item = layananMenetap.value[selectedMenetapLoc.value];
  return item ? `${item.name}, ${item.address}` : 'KB Samsat Lamongan';
});

const currentMenetapLocName = computed(() => {
  const item = layananMenetap.value[selectedMenetapLoc.value];
  return item ? `${item.name} — ${item.address}` : 'Layanan Payment Point';
});

const currentBelokMapQuery = computed(() => {
  const item = belokWangiSchedule.value[selectedBelokLoc.value];
  return item ? `${item.location}, Lamongan, Jawa Timur` : 'KB Samsat Lamongan';
});

const currentBelokLocName = computed(() => {
  const item = belokWangiSchedule.value[selectedBelokLoc.value];
  return item ? `${item.location} (${item.days})` : 'Lokasi BELOK WANGI';
});

const handleSelectKelilingDay = (idx) => {
  activeKelilingDay.value = idx;
  selectedKelilingLoc.value = 0;
};

// ── SALMA AI Showcase (Dynamic via CMS) ──────────────────────────────────────
const salmaBadge = computed(() => getCms('salma_badge', 'AI-Powered'));
const salmaTitle = computed(() => getCms('salma_title', 'SALMA AI'));
const salmaFullName = computed(() => getCms('salma_full_name', 'Samsat Lamongan Modern Assistant'));
const salmaDesc = computed(() => getCms('salma_desc', 'Asisten cerdas berbasis kecerdasan buatan yang siap menjawab seluruh pertanyaan Anda seputar pajak kendaraan bermotor, prosedur STNK, jadwal Samsat, dan informasi resmi lainnya secara instan — kapan saja, di mana saja.'));
const salmaFeatures = computed(() => getCms('salma_features', [
  { icon: 'mdi-clock-fast', text: 'Respon Instan 24/7' },
  { icon: 'mdi-shield-check', text: 'Informasi Resmi & Akurat' },
  { icon: 'mdi-brain', text: 'Didukung GPT-5.6 AI' },
  { icon: 'mdi-translate', text: 'Bahasa Indonesia & Jawa' },
]));
const salmaMascotImage = computed(() => getCms('salma_mascot_image', '/images/salma2.gif'));
const salmaCtaText = computed(() => getCms('salma_cta_text', 'Mulai Percakapan dengan SALMA'));

// ── Pembayaran Digital (Dynamic via CMS) ─────────────────────────────────────
const paymentBadge = computed(() => getCms('payment_badge', 'E-Samsat'));
const paymentTitle = computed(() => getCms('payment_title', 'Pembayaran Digital'));
const paymentTitleHighlight = computed(() => getCms('payment_title_highlight', 'Pajak Kendaraan'));
const paymentDesc = computed(() => getCms('payment_desc', 'Bayar pajak kendaraan kapan saja dan di mana saja tanpa perlu antri'));

const activePaymentTab = ref(0);
const paymentCategories = computed(() => getCms('payment_categories', [
  {
    name: 'E-Commerce',
    icon: 'mdi-shopping-outline',
    color: '#00AA5B',
    platforms: [
      { name: 'Tokopedia', logo: '/images/payment/tokopedia.png', bg: '#FFFFFF' },
      { name: 'Shopee', logo: '/images/payment/shopee.png', bg: '#ffffff' },
      { name: 'Alfamart', logo: '/images/payment/alfamart.png', bg: '#CC192B' },
      { name: 'Indomaret', logo: '/images/payment/indomaret.png', bg: '#003F8E' },
    ],
  },
  {
    name: 'E-Wallet',
    icon: 'mdi-wallet-outline',
    color: '#00AED6',
    platforms: [
      { name: 'GoPay', logo: '/images/payment/gopay.png', bg: '#00AED6' },
      { name: 'LinkAja', logo: '/images/payment/linkaja.svg', bg: '#E82529' },
      { name: 'iSaku', logo: '/images/payment/isaku.png', bg: '#ffffff' },
      { name: 'QRIS', logo: '/images/payment/qris.svg', bg: '#FFFFFF' },
    ],
  },
  {
    name: 'Perbankan',
    icon: 'mdi-bank-outline',
    color: '#003087',
    platforms: [
      { name: 'Bank Jatim', logo: '/images/payment/bankjatim.png', bg: '#FFFFFF' },
      { name: 'Bukopin', logo: '/images/payment/new/bank-bukopin.png', bg: '#FFFFFF' },
      { name: 'BTN', logo: '/images/payment/btn.png', bg: '#FFFFFF' },
      { name: 'Pos Indonesia', logo: '/images/payment/pos-indonesia.png', bg: '#Ffffff' },
    ],
  },
]));

// ── Kontak & Jam Operasional (Dynamic via CMS) ────────────────────────────────
const contactBadge = computed(() => getCms('contact_badge', 'Hubungi Kami'));
const contactTitle = computed(() => getCms('contact_title', 'Kontak &'));
const contactTitleHighlight = computed(() => getCms('contact_title_highlight', 'KB Samsat Lamongan'));
const contactAddress = computed(() => getCms('contact_address', 'Jl. Veteran No. 1A, Tumenggungan, Lamongan'));
const contactCityPostal = computed(() => getCms('contact_city_postal', 'Kabupaten Lamongan, Jawa Timur 62211'));
const contactHoursWeekday = computed(() => getCms('contact_hours_weekday', 'Senin – Kamis, Sabtu: 08.00 – 12.00 WIB'));
const contactHoursFriday = computed(() => getCms('contact_hours_friday', 'Jumat: 08.00 – 11.00 WIB'));
const contactPhone = computed(() => getCms('contact_phone', '(0322) 311234'));
const contactHelpCardTitle = computed(() => getCms('contact_help_card_title', 'Butuh Bantuan?'));
const contactHelpCardDesc = computed(() => getCms('contact_help_card_desc', 'Tanyakan apa saja kepada SALMA AI — Asisten pintar yang siap membantu Anda 24 jam nonstop.'));

// ── Footer & Branding (Dynamic via CMS) ──────────────────────────────────────
const footerAgencyName = computed(() => getCms('footer_agency_name', 'KB Samsat Lamongan'));
const footerAgencySub = computed(() => getCms('footer_agency_sub', 'Badan Pendapatan Daerah Provinsi Jawa Timur'));
const footerAgencyDesc = computed(() => getCms('footer_agency_desc', 'Kantor Bersama Samsat Lamongan melayani pembayaran Pajak Kendaraan Bermotor, pengesahan STNK, dan layanan kesamsatan lainnya bagi masyarakat Kabupaten Lamongan dan Jawa Timur.'));
const footerSocialLinks = computed(() => getCms('footer_social_links', [
  { platform: 'instagram', icon: 'mdi-instagram', url: 'https://instagram.com' },
  { platform: 'facebook', icon: 'mdi-facebook', url: 'https://facebook.com' },
  { platform: 'youtube', icon: 'mdi-youtube', url: 'https://youtube.com' },
]));
const footerCopyrightText = computed(() => getCms('footer_copyright_text', '© 2026 KB Samsat Lamongan — Bapenda Provinsi Jawa Timur. All rights reserved.'));

// ── Navigation & Actions ─────────────────────────────────────────────────────
const navLinks = [
  { label: 'Beranda', target: 'hero' },
  { label: 'Layanan', target: 'layanan' },
  { label: 'Jadwal & Lokasi', target: 'jadwal' },
  { label: 'SALMA AI', target: 'salma' },
  { label: 'Pembayaran', target: 'pembayaran' },
  { label: 'Kontak', target: 'kontak' },
];

const scrollTo = (id) => {
  mobileMenu.value = false;
  if (id === 'hero') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const startChat = () => {
  router.visit('/wajib-pajak');
};

// ── Today's schedule highlight ───────────────────────────────────────────────
const todayDayIndex = new Date().getDay();
const todayKelilingIndex = todayDayIndex >= 1 && todayDayIndex <= 6 ? todayDayIndex - 1 : 0;

// ── Lifecycle ────────────────────────────────────────────────────────────────
let scrollHandler = null;

onMounted(() => {
  document.documentElement.style.scrollBehavior = 'smooth';
  activeKelilingDay.value = todayKelilingIndex;

  // Start hero auto-slide
  slideInterval = setInterval(nextSlide, 5000);

  // Navbar scroll solid
  scrollHandler = () => {
    navSolid.value = window.scrollY > 80;
  };
  window.addEventListener('scroll', scrollHandler, { passive: true });
});

onUnmounted(() => {
  clearInterval(slideInterval);
  if (scrollHandler) window.removeEventListener('scroll', scrollHandler);
});
</script>

<template>
  <VApp>
    <Head title="KB Samsat Lamongan — Pelayanan Publik Pajak Kendaraan Bermotor" />

    <!-- ═══ STICKY NAVBAR ═══ -->
    <header class="gov-navbar" :class="{ 'gov-navbar--solid': navSolid }">
      <div class="gov-navbar__inner">
        <!-- Logo cluster -->
        <div class="gov-navbar__brand" @click="scrollTo('hero')">
          <VImg :src="logoUrl" alt="Logo Bapenda" contain width="34" height="34" class="me-2" />
          <VImg src="/images/logo-jatim.png" alt="Jawa Timur" contain width="34" height="34" class="me-2 d-none d-sm-block" />
          <VImg src="/images/Lambang_Polda_Jatim.png" alt="Polri" contain width="34" height="34" class="me-2 d-none d-md-block" />
          <VImg src="/images/jasa-raharja.png" alt="Jasa Raharja" contain width="34" height="34" class="me-2 d-none d-md-block" />
          <div class="gov-navbar__title">
            <span class="gov-navbar__name">KB Samsat Lamongan</span>
            <span class="gov-navbar__sub d-none d-md-block">Bapenda Provinsi Jawa Timur</span>
          </div>
        </div>

        <!-- Desktop Nav Links -->
        <nav class="gov-navbar__links d-none d-lg-flex">
          <a v-for="link in navLinks" :key="link.target" @click.prevent="scrollTo(link.target)" class="gov-navbar__link">
            {{ link.label }}
          </a>
        </nav>

        <!-- CTA + Mobile toggle -->
        <div class="gov-navbar__actions">
          <VBtn v-if="$page.props.auth?.user" color="white" variant="outlined" size="small" class="me-2 d-none d-sm-flex" :href="route('dashboard')">Dashboard</VBtn>
          <VBtn color="#C0392B" variant="flat" size="small" class="gov-navbar__cta" @click="startChat">
            <VIcon size="18" class="me-1">mdi-chat-processing</VIcon>
            <span class="d-none d-sm-inline">Tanya SALMA</span>
            <span class="d-sm-none">Chat</span>
          </VBtn>
          <VBtn icon variant="text" color="white" class="d-lg-none ms-1" @click="mobileMenu = !mobileMenu">
            <VIcon>{{ mobileMenu ? 'mdi-close' : 'mdi-menu' }}</VIcon>
          </VBtn>
        </div>
      </div>

      <!-- Mobile Dropdown Menu -->
      <Transition name="slide-down">
        <div v-if="mobileMenu" class="gov-navbar__mobile">
          <a v-for="link in navLinks" :key="link.target" @click.prevent="scrollTo(link.target)" class="gov-navbar__mobile-link">
            {{ link.label }}
          </a>
        </div>
      </Transition>
    </header>

    <VMain class="pa-0" style="padding-top: 0 !important;">
      <!-- ═══ SECTION 1: HERO IMAGE SLIDER ═══ -->
      <section id="hero" class="hero-slider">
        <div class="hero-slider__track">
          <div
            v-for="(bgImage, idx) in heroBackgrounds"
            :key="idx"
            class="hero-slider__slide"
            :class="{ 'hero-slider__slide--active': activeSlide === idx }"
            :style="{ backgroundImage: `url(${bgImage})` }"
          >
            <div class="hero-slider__overlay"></div>
          </div>
        </div>

        <!-- Static Content Overlay -->
        <div class="hero-slider__content">
          <VContainer>
            <VRow align="center" style="min-height: 85vh;">
              <VCol cols="12" md="7" lg="6">
                <div class="hero-slider__text">
                  <div class="hero-slider__badge">
                    <VIcon size="14" color="white" class="me-1">mdi-shield-check</VIcon>
                    {{ heroBadge }}
                  </div>
                  <h1 class="hero-slider__title whitespace-pre-line">{{ heroTitle }}</h1>
                  <p class="hero-slider__subtitle">{{ heroSubtitle }}</p>
                  <div class="hero-slider__btns">
                    <VBtn size="large" color="#C0392B" variant="flat" class="hero-btn me-3 mb-3" @click="scrollTo(heroCtaPrimaryTarget)">
                      {{ heroCtaPrimaryText }}
                      <VIcon end>mdi-arrow-right</VIcon>
                    </VBtn>
                    <VBtn size="large" variant="outlined" color="white" class="hero-btn mb-3" @click="scrollTo(heroCtaSecondaryTarget)">
                      <VIcon start>mdi-phone</VIcon>
                      {{ heroCtaSecondaryText }}
                    </VBtn>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VContainer>
        </div>

        <!-- Slider Controls -->
        <button class="hero-slider__arrow hero-slider__arrow--prev" @click="prevSlide(); resetSlideInterval()" aria-label="Slide sebelumnya">
          <VIcon color="white" size="28">mdi-chevron-left</VIcon>
        </button>
        <button class="hero-slider__arrow hero-slider__arrow--next" @click="nextSlide(); resetSlideInterval()" aria-label="Slide berikutnya">
          <VIcon color="white" size="28">mdi-chevron-right</VIcon>
        </button>

        <!-- Dots -->
        <div class="hero-slider__dots">
          <button
            v-for="(_, idx) in heroBackgrounds"
            :key="idx"
            class="hero-slider__dot"
            :class="{ 'hero-slider__dot--active': activeSlide === idx }"
            @click="goToSlide(idx)"
            :aria-label="`Pergi ke slide ${idx + 1}`"
          ></button>
        </div>

        <!-- Diagonal clip -->
        <div class="hero-slider__clip"></div>
      </section>

      <!-- ═══ SECTION 2: LAYANAN UNGGULAN ═══ -->
      <section id="layanan" class="section-layanan">
        <VContainer>
          <div class="section-header">
            <div class="section-header__badge">
              <VIcon size="16" class="me-1">mdi-star-four-points</VIcon>
              {{ servicesBadge }}
            </div>
            <h2 class="section-header__title">
              {{ servicesTitle }}<br><span>{{ servicesTitleHighlight }}</span>
            </h2>
            <p class="section-header__desc">{{ servicesDesc }}</p>
          </div>
          <VRow>
            <VCol v-for="(item, idx) in layananUnggulan" :key="idx" cols="12" sm="6" lg="4">
              <div class="service-card">
                <div class="service-card__icon" :style="{ backgroundColor: item.color + '12', color: item.color }">
                  <VIcon :color="item.color" size="28">{{ item.icon }}</VIcon>
                </div>
                <h3 class="service-card__title">{{ item.title }}</h3>
                <p class="service-card__desc">{{ item.desc }}</p>
              </div>
            </VCol>
          </VRow>
        </VContainer>
      </section>

      <!-- ═══ SECTION 3: JADWAL & LOKASI (WITH GOOGLE MAPS WIDGET) ═══ -->
      <section id="jadwal" class="section-jadwal">
        <VContainer>
          <div class="section-header">
            <div class="section-header__badge section-header__badge--alt">
              <VIcon size="16" class="me-1">mdi-calendar-clock</VIcon>
              {{ schedulesBadge }}
            </div>
            <h2 class="section-header__title">
              {{ schedulesTitle }}<br><span>{{ schedulesTitleHighlight }}</span>
            </h2>
            <p class="section-header__desc">{{ schedulesDesc }}</p>
          </div>

          <!-- Service Tabs -->
          <VTabs v-model="activeServiceTab" color="#C0392B" bg-color="transparent" align-tabs="center" class="jadwal-tabs mb-8">
            <VTab :value="0"><VIcon start size="18">mdi-bus-clock</VIcon> Samsat Keliling</VTab>
            <VTab :value="1"><VIcon start size="18">mdi-map-marker-multiple</VIcon> Payment Point</VTab>
            <VTab :value="2"><VIcon start size="18">mdi-weather-night</VIcon> BELOK WANGI</VTab>
          </VTabs>

          <VWindow v-model="activeServiceTab">
            <!-- Tab 1: Keliling Pagi -->
            <VWindowItem :value="0">
              <VCard class="jadwal-card" elevation="0">
                <div class="jadwal-card__header jadwal-card__header--keliling">
                  <div class="d-flex align-center gap-3 flex-wrap">
                    <div class="jadwal-card__icon-wrap"><VIcon color="white" size="24">mdi-bus-clock</VIcon></div>
                    <div>
                      <div class="text-caption text-white text-uppercase font-weight-medium" style="letter-spacing:.08em;opacity:.8">Samsat Keliling</div>
                      <h3 class="text-h6 font-weight-bold text-white mb-0">Jadwal Samsat Keliling Pagi</h3>
                    </div>
                    <VSpacer />
                    <VChip color="white" variant="flat" size="small"><VIcon start size="12" color="success">mdi-circle</VIcon> Aktif</VChip>
                  </div>
                  <p class="text-white text-body-2 mt-2 mb-0" style="opacity:.75">Klik pada lokasi untuk melihat letak titik layanan pada peta Google Maps</p>
                </div>
                <VCardText class="pa-4 pa-md-6">
                  <!-- Day Tabs -->
                  <VTabs
                    :model-value="activeKelilingDay"
                    @update:model-value="handleSelectKelilingDay"
                    color="#C0392B"
                    bg-color="transparent"
                    show-arrows
                    density="compact"
                    class="keliling-tabs mb-5"
                  >
                    <VTab v-for="(s, idx) in kelilingPagiSchedule" :key="idx" :value="idx" class="text-body-2 font-weight-semibold">
                      <span class="d-none d-sm-inline">{{ s.day }}</span>
                      <span class="d-sm-none">{{ s.short }}</span>
                    </VTab>
                  </VTabs>

                  <!-- Locations List -->
                  <VRow>
                    <VCol
                      v-for="(loc, li) in kelilingPagiSchedule[activeKelilingDay]?.locations || []"
                      :key="li"
                      cols="12"
                      sm="6"
                      md="4"
                    >
                      <div
                        class="loc-card"
                        :class="{ 'loc-card--active': selectedKelilingLoc === li }"
                        @click="selectedKelilingLoc = li"
                      >
                        <div class="loc-card__num">{{ li + 1 }}</div>
                        <div class="loc-card__text">
                          <div class="font-weight-medium text-grey-900">{{ loc }}</div>
                          <div class="text-caption" :class="selectedKelilingLoc === li ? 'text-primary font-weight-bold' : 'text-grey-600'">
                            {{ selectedKelilingLoc === li ? '● Lokasi Terpilih di Peta' : 'Klik untuk tampilkan peta' }}
                          </div>
                        </div>
                      </div>
                    </VCol>
                  </VRow>

                  <!-- Google Maps Widget Box -->
                  <div class="gmaps-widget mt-6">
                    <div class="gmaps-widget__header">
                      <div class="d-flex align-center gap-2">
                        <VIcon color="#C0392B" size="20">mdi-map-marker-radius</VIcon>
                        <span class="gmaps-widget__title">{{ currentKelilingLocName }}</span>
                      </div>
                      <a
                        :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(currentKelilingMapQuery)}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="gmaps-widget__direct-link"
                      >
                        <VIcon size="14" class="me-1">mdi-open-in-new</VIcon>
                        Petunjuk Arah Maps
                      </a>
                    </div>
                    <div class="gmaps-widget__frame-wrap">
                      <iframe
                        :src="`https://maps.google.com/maps?q=${encodeURIComponent(currentKelilingMapQuery)}&t=&z=15&ie=UTF8&iwloc=&output=embed`"
                        class="gmaps-widget__iframe"
                        loading="lazy"
                        allowfullscreen
                        title="Peta Samsat Keliling Lamongan"
                      ></iframe>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VWindowItem>

            <!-- Tab 2: Payment Point Menetap -->
            <VWindowItem :value="1">
              <VCard class="jadwal-card" elevation="0">
                <div class="jadwal-card__header jadwal-card__header--menetap">
                  <div class="d-flex align-center gap-3">
                    <div class="jadwal-card__icon-wrap"><VIcon color="white" size="24">mdi-map-marker-multiple</VIcon></div>
                    <div>
                      <div class="text-caption text-white text-uppercase font-weight-medium" style="letter-spacing:.08em;opacity:.8">Lokasi Tetap</div>
                      <h3 class="text-h6 font-weight-bold text-white mb-0">Layanan Payment Point</h3>
                    </div>
                  </div>
                  <p class="text-white text-body-2 mt-2 mb-0" style="opacity:.75">Pilihan lokasi pembayaran tetap untuk kemudahan Anda di berbagai titik</p>
                </div>
                <VCardText class="pa-4 pa-md-6">
                  <VRow>
                    <VCol
                      v-for="(p, idx) in layananMenetap"
                      :key="idx"
                      cols="12"
                      sm="6"
                      md="4"
                    >
                      <div
                        class="pp-card"
                        :class="{ 'pp-card--active': selectedMenetapLoc === idx }"
                        @click="selectedMenetapLoc = idx"
                      >
                        <div class="pp-card__icon" :style="{ backgroundColor: p.color + '14' }">
                          <VIcon :color="p.color" size="22">{{ p.icon }}</VIcon>
                        </div>
                        <div class="pp-card__info">
                          <div class="text-body-2 font-weight-bold mb-1 text-grey-900">{{ p.name }}</div>
                          <div class="text-caption text-grey-600 mb-1">
                            <VIcon size="12" class="me-1">mdi-map-marker-outline</VIcon>{{ p.address }}
                          </div>
                          <div class="d-flex align-center justify-space-between mt-2">
                            <VChip size="x-small" color="#C0392B" variant="tonal">
                              <VIcon start size="10">mdi-clock-outline</VIcon>{{ p.hours }}
                            </VChip>
                            <span v-if="selectedMenetapLoc === idx" class="text-caption text-primary font-weight-bold">
                              ● Peta Aktif
                            </span>
                          </div>
                        </div>
                      </div>
                    </VCol>
                  </VRow>

                  <!-- Google Maps Widget Box -->
                  <div class="gmaps-widget mt-6">
                    <div class="gmaps-widget__header">
                      <div class="d-flex align-center gap-2">
                        <VIcon color="#C0392B" size="20">mdi-office-building-marker</VIcon>
                        <span class="gmaps-widget__title">{{ currentMenetapLocName }}</span>
                      </div>
                      <a
                        :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(currentMenetapMapQuery)}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="gmaps-widget__direct-link"
                      >
                        <VIcon size="14" class="me-1">mdi-open-in-new</VIcon>
                        Petunjuk Arah Maps
                      </a>
                    </div>
                    <div class="gmaps-widget__frame-wrap">
                      <iframe
                        :src="`https://maps.google.com/maps?q=${encodeURIComponent(currentMenetapMapQuery)}&t=&z=15&ie=UTF8&iwloc=&output=embed`"
                        class="gmaps-widget__iframe"
                        loading="lazy"
                        allowfullscreen
                        title="Peta Payment Point Lamongan"
                      ></iframe>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VWindowItem>

            <!-- Tab 3: BELOK WANGI -->
            <VWindowItem :value="2">
              <VCard class="jadwal-card jadwal-card--night" elevation="0">
                <div class="jadwal-card__header jadwal-card__header--night">
                  <div class="d-flex align-center gap-3 flex-wrap">
                    <div class="jadwal-card__icon-wrap jadwal-card__icon-wrap--night"><VIcon color="#FFD700" size="24">mdi-weather-night</VIcon></div>
                    <div>
                      <div class="d-flex align-center gap-2 mb-1">
                        <span class="text-caption text-amber-300 text-uppercase font-weight-medium" style="letter-spacing:.08em">Layanan Malam</span>
                        <VChip size="x-small" color="amber-darken-1" variant="flat">SPESIAL</VChip>
                      </div>
                      <h3 class="text-h6 font-weight-bold text-white mb-0">
                        Samsat Keliling Malam
                        <span class="belok-badge ms-2">BELOK WANGI</span>
                      </h3>
                    </div>
                    <VSpacer />
                    <div class="belok-time d-none d-sm-flex">
                      <VIcon color="#FFD700" size="16" class="me-1">mdi-clock-outline</VIcon>
                      <span class="text-white font-weight-bold text-body-2">18.00 – 20.00 WIB</span>
                    </div>
                  </div>
                  <p class="text-white text-body-2 mt-2 mb-0"><strong class="text-amber-300">Beda Lokasi Wayah Bengi</strong> — Khusus untuk Anda yang sibuk di siang hari</p>
                  <div class="mt-2 d-flex d-sm-none align-center gap-1">
                    <VIcon color="#FFD700" size="14">mdi-clock-outline</VIcon>
                    <span class="text-white text-body-2 font-weight-bold">18.00 – 20.00 WIB</span>
                  </div>
                </div>
                <VCardText class="pa-4 pa-md-6">
                  <VRow>
                    <VCol
                      v-for="(b, idx) in belokWangiSchedule"
                      :key="idx"
                      cols="12"
                      sm="4"
                    >
                      <div
                        class="belok-card"
                        :class="{ 'belok-card--active': selectedBelokLoc === idx }"
                        @click="selectedBelokLoc = idx"
                      >
                        <div class="belok-card__icon"><VIcon color="#FFD700" size="24">{{ b.icon }}</VIcon></div>
                        <div class="text-amber-300 text-caption font-weight-bold text-uppercase mb-1">{{ b.days }}</div>
                        <div class="text-white text-body-2 font-weight-medium">{{ b.location }}</div>
                        <span v-if="selectedBelokLoc === idx" class="text-caption text-amber-300 font-weight-bold mt-1">
                          ● Peta Aktif
                        </span>
                      </div>
                    </VCol>
                  </VRow>

                  <!-- Google Maps Widget Box -->
                  <div class="gmaps-widget gmaps-widget--night mt-6">
                    <div class="gmaps-widget__header gmaps-widget__header--night">
                      <div class="d-flex align-center gap-2">
                        <VIcon color="#FFD700" size="20">mdi-weather-night</VIcon>
                        <span class="gmaps-widget__title text-white">{{ currentBelokLocName }}</span>
                      </div>
                      <a
                        :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(currentBelokMapQuery)}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="gmaps-widget__direct-link gmaps-widget__direct-link--night"
                      >
                        <VIcon size="14" class="me-1">mdi-open-in-new</VIcon>
                        Petunjuk Arah Maps
                      </a>
                    </div>
                    <div class="gmaps-widget__frame-wrap">
                      <iframe
                        :src="`https://maps.google.com/maps?q=${encodeURIComponent(currentBelokMapQuery)}&t=&z=15&ie=UTF8&iwloc=&output=embed`"
                        class="gmaps-widget__iframe"
                        loading="lazy"
                        allowfullscreen
                        title="Peta BELOK WANGI Lamongan"
                      ></iframe>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VWindowItem>
          </VWindow>
        </VContainer>
      </section>

      <!-- ═══ SECTION 4: SALMA AI SHOWCASE ═══ -->
      <section id="salma" class="section-salma">
        <VContainer>
          <VRow align="center">
            <VCol cols="12" md="6" class="mb-8 mb-md-0">
              <div class="salma-badge">
                <VIcon size="16" class="me-1">mdi-chat-processing</VIcon>
                {{ salmaBadge }}
              </div>
              <h2 class="salma-title">{{ salmaTitle }}</h2>
              <p class="salma-full-name">{{ salmaFullName }}</p>
              <p class="salma-desc">{{ salmaDesc }}</p>
              <div class="salma-features">
                <div v-for="(feat, fIdx) in salmaFeatures" :key="fIdx" class="salma-feature">
                  <VIcon color="#C0392B" size="20">{{ feat.icon }}</VIcon> {{ feat.text }}
                </div>
              </div>
              <VBtn size="x-large" color="#C0392B" variant="flat" class="hero-btn mt-6" @click="startChat">
                <VIcon start>mdi-chat-processing</VIcon>
                {{ salmaCtaText }}
              </VBtn>
            </VCol>
            <VCol cols="12" md="6" class="text-center">
              <div class="salma-visual">
                <div class="salma-glow"></div>
                <img :src="salmaMascotImage" alt="SALMA AI Mascot" class="salma-mascot" loading="lazy" />
                <div class="salma-visual__label">
                  <VIcon size="14" color="#C0392B" class="me-1">mdi-circle-small</VIcon>
                  Online — Siap Melayani
                </div>
              </div>
            </VCol>
          </VRow>
        </VContainer>
      </section>

      <!-- ═══ SECTION 5: PEMBAYARAN DIGITAL ═══ -->
      <section id="pembayaran" class="section-payment">
        <VContainer>
          <div class="section-header">
            <div class="section-header__badge">
              <VIcon size="16" class="me-1">mdi-contactless-payment</VIcon>
              {{ paymentBadge }}
            </div>
            <h2 class="section-header__title">
              {{ paymentTitle }}<br><span>{{ paymentTitleHighlight }}</span>
            </h2>
            <p class="section-header__desc">{{ paymentDesc }}</p>
          </div>

          <VCard class="payment-card" elevation="0">
            <VCardText class="pa-4 pa-md-6">
              <VTabs v-model="activePaymentTab" color="#C0392B" bg-color="transparent" density="compact" class="payment-tabs mb-6">
                <VTab v-for="(cat, idx) in paymentCategories" :key="idx" :value="idx" class="text-body-2 font-weight-semibold">
                  <VIcon start size="16">{{ cat.icon }}</VIcon>
                  {{ cat.name }}
                </VTab>
              </VTabs>
              <VWindow v-model="activePaymentTab">
                <VWindowItem v-for="(cat, ci) in paymentCategories" :key="ci" :value="ci">
                  <VRow>
                    <VCol v-for="(pl, pi) in cat.platforms" :key="pi" cols="6" sm="4" md="3">
                      <div class="pay-logo">
                        <div class="pay-logo__img" :style="{ backgroundColor: pl.bg }">
                          <img :src="pl.logo" :alt="pl.name" />
                        </div>
                        <div class="pay-logo__name">{{ pl.name }}</div>
                      </div>
                    </VCol>
                  </VRow>
                </VWindowItem>
              </VWindow>
            </VCardText>
          </VCard>
        </VContainer>
      </section>

      <!-- ═══ SECTION 6: KONTAK ═══ -->
      <section id="kontak" class="section-kontak">
        <VContainer>
          <VRow align="center">
            <VCol cols="12" md="6" class="mb-8 mb-md-0">
              <div class="section-header text-left">
                <div class="section-header__badge section-header__badge--white">
                  <VIcon size="16" class="me-1">mdi-phone-in-talk</VIcon>
                  {{ contactBadge }}
                </div>
                <h2 class="section-header__title text-white">
                  {{ contactTitle }}<br><span style="opacity:.85">{{ contactTitleHighlight }}</span>
                </h2>
              </div>
              <div class="kontak-list">
                <div class="kontak-item">
                  <VAvatar color="white" size="48" class="me-4"><VIcon color="#C0392B">mdi-map-marker</VIcon></VAvatar>
                  <div>
                    <div class="text-white font-weight-medium">{{ contactAddress }}</div>
                    <div class="text-white" style="opacity:.7">{{ contactCityPostal }}</div>
                  </div>
                </div>
                <div class="kontak-item">
                  <VAvatar color="white" size="48" class="me-4"><VIcon color="#C0392B">mdi-clock</VIcon></VAvatar>
                  <div>
                    <div class="text-white font-weight-medium">{{ contactHoursWeekday }}</div>
                    <div class="text-white" style="opacity:.7">{{ contactHoursFriday }}</div>
                  </div>
                </div>
                <div class="kontak-item">
                  <VAvatar color="white" size="48" class="me-4"><VIcon color="#C0392B">mdi-phone</VIcon></VAvatar>
                  <div>
                    <div class="text-white font-weight-medium">{{ contactPhone }}</div>
                    <div class="text-white" style="opacity:.7">Telepon Kantor</div>
                  </div>
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <VCard color="white" variant="flat" class="kontak-cta-card">
                <VCardText class="pa-8 text-center">
                  <VAvatar color="#C0392B" size="100" class="mb-5"><VIcon size="48" color="white">mdi-chat-processing</VIcon></VAvatar>
                  <h3 class="text-h5 font-weight-bold mb-3" style="color:#1B2838">{{ contactHelpCardTitle }}</h3>
                  <p class="text-grey-700 mb-6">{{ contactHelpCardDesc }}</p>
                  <VBtn size="x-large" color="#C0392B" variant="flat" block class="hero-btn" @click="startChat">
                    <VIcon start>mdi-chat-processing</VIcon>
                    Mulai Chat dengan SALMA
                  </VBtn>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VContainer>
      </section>
    </VMain>

    <!-- ═══ FOOTER (DYNAMIC VIA CMS) ═══ -->
    <footer class="gov-footer">
      <VContainer>
        <VRow>
          <!-- Footer Branding with Clean Multi-Logo Cluster -->
          <VCol cols="12" md="4" class="mb-6 mb-md-0">
            <div class="footer-brand mb-4">
              <div class="footer-logos d-flex align-center flex-wrap gap-2 mb-3">
                <div class="footer-logo-badge" title="Bapenda Jawa Timur">
                  <VImg src="/images/logo-bapenda-jatim.png" alt="Bapenda Jatim" contain width="32" height="32" />
                </div>
                <div class="footer-logo-badge" title="Pemerintah Provinsi Jawa Timur">
                  <VImg src="/images/logo-jatim.png" alt="Pemprov Jatim" contain width="32" height="32" />
                </div>
                <div class="footer-logo-badge" title="Polda Jawa Timur / Polri">
                  <VImg src="/images/Lambang_Polda_Jatim.png" alt="Polda Jatim" contain width="32" height="32" />
                </div>
                <div class="footer-logo-badge" title="Jasa Raharja">
                  <VImg src="/images/jasa-raharja.png" alt="Jasa Raharja" contain width="32" height="32" />
                </div>
              </div>
              <div>
                <h4 class="text-h6 font-weight-bold text-white mb-0">{{ footerAgencyName }}</h4>
                <p class="text-caption text-white-70 mb-0">{{ footerAgencySub }}</p>
              </div>
            </div>
            <p style="color:rgba(255,255,255,.6);line-height:1.7" class="text-body-2">
              {{ footerAgencyDesc }}
            </p>
          </VCol>
          <VCol cols="6" sm="4" md="2">
            <h5 class="text-body-1 font-weight-bold text-white mb-4">Layanan</h5>
            <div class="footer-link" @click="scrollTo('layanan')">Pajak Tahunan</div>
            <div class="footer-link" @click="scrollTo('layanan')">STNK 5 Tahunan</div>
            <div class="footer-link" @click="scrollTo('layanan')">Balik Nama</div>
            <div class="footer-link" @click="scrollTo('layanan')">Mutasi Kendaraan</div>
            <div class="footer-link" @click="scrollTo('jadwal')">Samsat Keliling</div>
          </VCol>
          <VCol cols="6" sm="4" md="2">
            <h5 class="text-body-1 font-weight-bold text-white mb-4">Informasi</h5>
            <div class="footer-link" @click="scrollTo('jadwal')">Jadwal & Lokasi</div>
            <div class="footer-link" @click="scrollTo('pembayaran')">Pembayaran Digital</div>
            <div class="footer-link" @click="scrollTo('salma')">SALMA AI</div>
            <div class="footer-link" @click="scrollTo('kontak')">Hubungi Kami</div>
          </VCol>
          <VCol cols="12" sm="4" md="4">
            <h5 class="text-body-1 font-weight-bold text-white mb-4">Kontak</h5>
            <div class="footer-contact"><VIcon size="16" class="me-2" style="color:rgba(255,255,255,.5)">mdi-map-marker</VIcon> {{ contactAddress }}</div>
            <div class="footer-contact"><VIcon size="16" class="me-2" style="color:rgba(255,255,255,.5)">mdi-phone</VIcon> {{ contactPhone }}</div>
            <div class="footer-contact"><VIcon size="16" class="me-2" style="color:rgba(255,255,255,.5)">mdi-clock</VIcon> {{ contactHoursWeekday }}</div>
            <div class="d-flex gap-2 mt-4">
              <a
                v-for="(soc, sIdx) in footerSocialLinks"
                :key="sIdx"
                :href="soc.url"
                target="_blank"
                rel="noopener noreferrer"
                class="social-btn"
                :title="soc.platform"
              >
                <VIcon size="18">{{ soc.icon }}</VIcon>
              </a>
            </div>
          </VCol>
        </VRow>
        <VDivider class="my-6" style="border-color:rgba(255,255,255,.12)" />
        <div class="d-flex flex-column flex-sm-row align-center justify-space-between">
          <p class="text-body-2 mb-0" style="color:rgba(255,255,255,.5)">{{ footerCopyrightText }}</p>
          <p class="text-body-2 mb-0" style="color:rgba(255,255,255,.5)">Powered by <strong class="text-white">SALMA AI</strong></p>
        </div>
      </VContainer>
    </footer>

    <!-- PWA Install Button -->
    <PwaInstallButton />
    <!-- Floating Chat Component -->
    <FloatingChat v-if="!isChatPageOpen" />
  </VApp>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════
   DESIGN SYSTEM — Government Red (#C0392B) + Navy (#1B2838)
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── NAVBAR ─────────────────────────────────────────────────────────────── */
.gov-navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  background: transparent;
  transition: all .35s cubic-bezier(.4,0,.2,1);
}
.gov-navbar--solid {
  background: rgba(27,40,56,.97);
  backdrop-filter: blur(12px);
  box-shadow: 0 2px 20px rgba(0,0,0,.15);
}
.gov-navbar__inner {
  max-width: 1280px; margin: 0 auto;
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 24px;
}
.gov-navbar__brand {
  display: flex; align-items: center; cursor: pointer;
}
.gov-navbar__title { display: flex; flex-direction: column; }
.gov-navbar__name { color: #fff; font-weight: 700; font-size: 1rem; line-height: 1.2; }
.gov-navbar__sub { color: rgba(255,255,255,.65); font-size: .72rem; }
.gov-navbar__links { display: flex; gap: 4px; }
.gov-navbar__link {
  color: rgba(255,255,255,.85); font-size: .875rem; font-weight: 500;
  padding: 8px 14px; border-radius: 8px; cursor: pointer;
  transition: all .2s ease; text-decoration: none;
}
.gov-navbar__link:hover { color: #fff; background: rgba(255,255,255,.1); }
.gov-navbar__actions { display: flex; align-items: center; }
.gov-navbar__cta { border-radius: 24px !important; text-transform: none; font-weight: 600; letter-spacing: 0; }
.gov-navbar__mobile {
  background: rgba(27,40,56,.98); backdrop-filter: blur(12px);
  padding: 8px 24px 16px; display: flex; flex-direction: column;
}
.gov-navbar__mobile-link {
  color: rgba(255,255,255,.85); padding: 12px 0; font-size: .95rem;
  border-bottom: 1px solid rgba(255,255,255,.08); cursor: pointer; text-decoration: none;
}
.gov-navbar__mobile-link:last-child { border-bottom: none; }

/* Mobile menu transition */
.slide-down-enter-active, .slide-down-leave-active { transition: all .3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-10px); }

/* ── HERO SLIDER (STATIC TEXT OVER ROTATING BACKGROUND) ─────────────────── */
.hero-slider {
  position: relative; width: 100%; min-height: 92vh; overflow: hidden;
  background: #1B2838;
}
.hero-slider__track { position: absolute; inset: 0; }
.hero-slider__slide {
  position: absolute; inset: 0;
  background-size: cover; background-position: center;
  opacity: 0; transition: opacity 1.2s ease-in-out;
}
.hero-slider__slide--active { opacity: 1; }
.hero-slider__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(27,40,56,.85) 0%, rgba(192,57,43,.45) 100%);
}
.hero-slider__content {
  position: relative; z-index: 2; min-height: 92vh;
  display: flex; align-items: center;
}
.hero-slider__badge {
  display: inline-flex; align-items: center;
  background: rgba(192,57,43,.9); color: #fff;
  padding: 6px 16px; border-radius: 24px; font-size: .75rem;
  font-weight: 600; letter-spacing: .04em; margin-bottom: 20px;
}
.hero-slider__title {
  color: #fff; font-size: 3.2rem; font-weight: 800; line-height: 1.15;
  margin-bottom: 16px; text-shadow: 0 2px 20px rgba(0,0,0,.3);
}
.hero-slider__subtitle {
  color: rgba(255,255,255,.85); font-size: 1.15rem; line-height: 1.7;
  margin-bottom: 32px; max-width: 540px;
}
.hero-btn {
  border-radius: 50px !important; text-transform: none; font-weight: 600;
  padding: 12px 28px !important; letter-spacing: 0;
  box-shadow: 0 4px 20px rgba(0,0,0,.2);
  transition: all .3s ease;
}
.hero-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.3); }

/* Slider Arrows */
.hero-slider__arrow {
  position: absolute; top: 50%; transform: translateY(-50%); z-index: 5;
  width: 48px; height: 48px; border-radius: 50%;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
  backdrop-filter: blur(8px); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .25s ease;
}
.hero-slider__arrow:hover { background: rgba(192,57,43,.7); border-color: transparent; }
.hero-slider__arrow--prev { left: 24px; }
.hero-slider__arrow--next { right: 24px; }

/* Slider Dots */
.hero-slider__dots {
  position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 10px; z-index: 5;
}
.hero-slider__dot {
  width: 12px; height: 12px; border-radius: 50%;
  background: rgba(255,255,255,.4); border: 2px solid transparent;
  cursor: pointer; transition: all .3s ease;
}
.hero-slider__dot--active { background: #C0392B; border-color: #fff; transform: scale(1.2); }

/* Diagonal clip */
.hero-slider__clip {
  position: absolute; bottom: -1px; left: 0; right: 0; height: 80px;
  background: #fff; clip-path: polygon(0 100%, 100% 100%, 100% 0);
  z-index: 3;
}

/* ── SECTION HEADER ─────────────────────────────────────────────────────── */
.section-header { text-align: center; margin-bottom: 48px; }
.section-header__badge {
  display: inline-flex; align-items: center;
  background: #C0392B12; color: #C0392B;
  padding: 6px 16px; border-radius: 24px; font-size: .78rem;
  font-weight: 600; margin-bottom: 16px;
}
.section-header__badge--alt { background: #1B283812; color: #1B2838; }
.section-header__badge--white { background: rgba(255,255,255,.15); color: #fff; }
.section-header__title {
  font-size: 2.2rem; font-weight: 800; color: #1B2838; line-height: 1.2;
}
.section-header__title span { color: #C0392B; }
.section-header__desc {
  color: #666; font-size: 1rem; max-width: 600px; margin: 12px auto 0; line-height: 1.7;
}
.text-left .section-header__title { text-align: left; }

/* ── LAYANAN UNGGULAN ───────────────────────────────────────────────────── */
.section-layanan { padding: 80px 0; background: #fff; }
.service-card {
  background: #fff; border: 1px solid #eee; border-radius: 16px;
  padding: 28px 24px;
  transition: all .35s cubic-bezier(.4,0,.2,1);
  height: 100%;
}
.service-card:hover {
  transform: translateY(-4px); border-color: #C0392B25;
  box-shadow: 0 10px 30px rgba(192,57,43,.08);
}
.service-card__icon {
  width: 56px; height: 56px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 18px;
}
.service-card__title { font-size: 1.1rem; font-weight: 700; color: #1B2838; margin-bottom: 8px; }
.service-card__desc { color: #666; font-size: .88rem; line-height: 1.6; margin-bottom: 0; }

/* ── JADWAL & LOKASI ─────────────────────────────────────────────────────── */
.section-jadwal { padding: 80px 0; background: #f9fafb; }
.jadwal-tabs :deep(.v-tab) { text-transform: none; font-size: .9rem; border-radius: 10px !important; }
.jadwal-card { border-radius: 20px !important; overflow: hidden; border: 1px solid #eee; background: #fff; }
.jadwal-card__header { padding: 20px 24px; }
.jadwal-card__header--keliling { background: linear-gradient(135deg, #1B2838 0%, #34495E 100%); }
.jadwal-card__header--menetap { background: linear-gradient(135deg, #C0392B 0%, #E74C3C 100%); }
.jadwal-card__header--night { background: linear-gradient(135deg, #0d0d2b 0%, #1a1a4e 100%); border-bottom: 1px solid rgba(255,215,0,.15); }
.jadwal-card__icon-wrap {
  width: 44px; height: 44px; border-radius: 12px;
  background: rgba(255,255,255,.15); display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
}
.jadwal-card__icon-wrap--night { background: rgba(255,215,0,.15); border: 1px solid rgba(255,215,0,.25); }
.jadwal-card--night { background: #0d0d2b !important; border-color: rgba(255,215,0,.15) !important; }

.keliling-tabs :deep(.v-tab) { text-transform: none; border-radius: 8px !important; min-width: 52px; font-size: .85rem; }

.loc-card {
  display: flex; align-items: flex-start; gap: 12px;
  background: #f8f9fa; border: 2px solid transparent; border-radius: 12px;
  padding: 14px 16px; transition: all .25s ease; cursor: pointer; height: 100%;
}
.loc-card:hover { background: #fef5f4; border-color: #C0392B30; transform: translateY(-2px); }
.loc-card--active { background: #fef5f4 !important; border-color: #C0392B !important; box-shadow: 0 4px 16px rgba(192,57,43,.12); }
.loc-card__num {
  width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, #C0392B, #E74C3C);
  color: #fff; font-weight: 700; font-size: .75rem;
  display: flex; align-items: center; justify-content: center;
}
.loc-card__text { flex: 1; font-size: .88rem; color: #444; line-height: 1.4; }

.pp-card {
  display: flex; align-items: flex-start; gap: 14px;
  background: #fafafa; border: 2px solid transparent; border-radius: 14px;
  padding: 16px; transition: all .25s ease; cursor: pointer; height: 100%;
}
.pp-card:hover { background: #fef5f4; border-color: #C0392B30; transform: translateY(-2px); }
.pp-card--active { background: #fef5f4 !important; border-color: #C0392B !important; box-shadow: 0 4px 16px rgba(192,57,43,.12); }
.pp-card__icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pp-card__info { flex: 1; }

.belok-badge {
  display: inline-flex; padding: 2px 10px; border-radius: 20px;
  background: rgba(255,215,0,.15); border: 1px solid rgba(255,215,0,.4);
  color: #ffd700; font-size: .7rem; font-weight: 700; letter-spacing: .05em;
}
.belok-time {
  align-items: center; background: rgba(255,215,0,.12);
  border: 1px solid rgba(255,215,0,.3); border-radius: 20px; padding: 4px 14px;
}
.belok-card {
  background: rgba(255,255,255,.04); border: 2px solid rgba(255,215,0,.2);
  border-radius: 14px; padding: 20px; text-align: center;
  transition: all .25s ease; height: 100%; cursor: pointer;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
}
.belok-card:hover { background: rgba(255,215,0,.08); border-color: rgba(255,215,0,.5); transform: translateY(-3px); }
.belok-card--active { background: rgba(255,215,0,.12) !important; border-color: #FFD700 !important; box-shadow: 0 0 20px rgba(255,215,0,.25); }
.belok-card__icon {
  width: 48px; height: 48px; border-radius: 12px;
  background: rgba(255,215,0,.12); display: flex;
  align-items: center; justify-content: center;
}

/* ── GOOGLE MAPS WIDGET BOX ─────────────────────────────────────────────── */
.gmaps-widget {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
  overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,.05);
}
.gmaps-widget--night {
  background: #141438; border-color: rgba(255,215,0,.25);
  box-shadow: 0 6px 20px rgba(0,0,0,.3);
}
.gmaps-widget__header {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px; padding: 14px 20px;
  background: #f8fafc; border-bottom: 1px solid #e2e8f0;
}
.gmaps-widget__header--night {
  background: #1a1a4e; border-bottom-color: rgba(255,215,0,.2);
}
.gmaps-widget__title { font-weight: 700; font-size: .92rem; color: #1e293b; }
.gmaps-widget__direct-link {
  display: inline-flex; align-items: center;
  color: #C0392B; font-size: .82rem; font-weight: 600;
  text-decoration: none; padding: 6px 12px; border-radius: 6px;
  background: rgba(192,57,43,.08); transition: all .2s ease;
}
.gmaps-widget__direct-link:hover {
  background: #C0392B; color: #fff;
}
.gmaps-widget__direct-link--night {
  color: #FFD700; background: rgba(255,215,0,.12);
}
.gmaps-widget__direct-link--night:hover {
  background: #FFD700; color: #0d0d2b;
}
.gmaps-widget__frame-wrap {
  position: relative; width: 100%; height: 360px;
}
.gmaps-widget__iframe {
  width: 100%; height: 100%; border: none; display: block;
}

/* ── SALMA AI SECTION ───────────────────────────────────────────────────── */
.section-salma {
  padding: 80px 0;
  background: linear-gradient(180deg, #fff 0%, #fef5f4 100%);
}
.salma-badge {
  display: inline-flex; align-items: center;
  background: #C0392B12; color: #C0392B;
  padding: 6px 16px; border-radius: 24px; font-size: .78rem;
  font-weight: 600; margin-bottom: 16px;
}
.salma-title { font-size: 3rem; font-weight: 800; color: #1B2838; margin-bottom: 4px; }
.salma-full-name { color: #C0392B; font-size: 1.15rem; font-weight: 600; margin-bottom: 16px; }
.salma-desc { color: #555; font-size: 1rem; line-height: 1.8; margin-bottom: 24px; max-width: 520px; }
.salma-features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.salma-feature {
  display: flex; align-items: center; gap: 10px;
  font-size: .9rem; font-weight: 500; color: #333;
  padding: 10px 14px; border-radius: 10px; background: #fff;
  border: 1px solid #eee; transition: all .2s ease;
}
.salma-feature:hover { border-color: #C0392B30; background: #fef5f4; }

.salma-visual {
  position: relative; display: inline-block;
}
.salma-glow {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
  width: 320px; height: 320px; border-radius: 50%;
  background: radial-gradient(circle, rgba(192,57,43,.12) 0%, transparent 70%);
  animation: salma-pulse 4s ease-in-out infinite;
}
.salma-mascot {
  width: 280px; height: auto; position: relative; z-index: 2;
  filter: drop-shadow(0 8px 32px rgba(192,57,43,.15));
}
.salma-visual__label {
  display: inline-flex; align-items: center;
  background: #fff; border: 1px solid #eee; border-radius: 20px;
  padding: 6px 16px; font-size: .78rem; font-weight: 600; color: #333;
  position: relative; z-index: 2; margin-top: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,.06);
}
@keyframes salma-pulse {
  0%, 100% { transform: translate(-50%,-50%) scale(1); opacity: .6; }
  50% { transform: translate(-50%,-50%) scale(1.08); opacity: 1; }
}

/* ── PAYMENT ────────────────────────────────────────────────────────────── */
.section-payment { padding: 80px 0; background: #fff; }
.payment-card { border-radius: 20px !important; border: 1px solid #eee; }
.payment-tabs :deep(.v-tab) { text-transform: none; border-radius: 8px !important; font-size: .88rem; }
.pay-logo { text-align: center; transition: transform .25s ease; cursor: default; }
.pay-logo:hover { transform: translateY(-4px); }
.pay-logo__img {
  width: 100%; height: 80px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.1);
  border: 1px solid rgba(0,0,0,.06); padding: 10px 12px;
}
.pay-logo__img img { max-width: 100%; max-height: 58px; object-fit: contain; }
.pay-logo__name { font-size: .78rem; color: #666; font-weight: 500; margin-top: 8px; }

/* ── KONTAK ─────────────────────────────────────────────────────────────── */
.section-kontak {
  padding: 80px 0;
  background: linear-gradient(135deg, #1B2838 0%, #2C3E50 50%, #C0392B 100%);
}
.kontak-list { display: flex; flex-direction: column; gap: 20px; }
.kontak-item { display: flex; align-items: center; }
.kontak-cta-card { border-radius: 24px !important; }

/* ── FOOTER ─────────────────────────────────────────────────────────────── */
.gov-footer {
  background: #111827; padding: 56px 0 24px;
}
.footer-brand { display: flex; flex-direction: column; }
.footer-logos { display: flex; align-items: center; gap: 8px; }
.footer-logo-badge {
  width: 44px; height: 44px; border-radius: 10px;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14);
  display: flex; align-items: center; justify-content: center;
  padding: 4px; transition: all .25s ease;
}
.footer-logo-badge:hover {
  background: rgba(255,255,255,.16); border-color: rgba(255,255,255,.3);
  transform: translateY(-2px);
}
.footer-link {
  color: rgba(255,255,255,.55); font-size: .85rem; padding: 5px 0;
  cursor: pointer; transition: color .2s ease;
}
.footer-link:hover { color: #fff; }
.footer-contact {
  color: rgba(255,255,255,.55); font-size: .85rem; padding: 5px 0;
  display: flex; align-items: center;
}
.social-btn {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,.2);
  color: #fff; display: flex; align-items: center; justify-content: center;
  transition: all .2s ease; text-decoration: none;
}
.social-btn:hover {
  background: rgba(255,255,255,.15); border-color: #fff; transform: translateY(-2px);
}
.text-white-70 { color: rgba(255,255,255,.7) !important; }

/* ═══ RESPONSIVE ═══════════════════════════════════════════════════════════ */
@media (max-width: 960px) {
  .hero-slider { min-height: 80vh; }
  .hero-slider__title { font-size: 2.2rem !important; }
  .hero-slider__subtitle { font-size: 1rem; }
  .hero-slider__arrow { display: none; }
  .section-header__title { font-size: 1.8rem; }
  .salma-title { font-size: 2.2rem; }
  .salma-features { grid-template-columns: 1fr; }
  .gmaps-widget__frame-wrap { height: 280px; }
}
@media (max-width: 600px) {
  .gov-navbar__inner { padding: 10px 16px; }
  .hero-slider { min-height: 75vh; }
  .hero-slider__content { padding-top: 80px; }
  .hero-slider__title { font-size: 1.8rem !important; }
  .hero-slider__btns { display: flex; flex-direction: column; }
  .hero-slider__btns .hero-btn { width: 100%; }
  .hero-slider__dots { bottom: 90px; }
  .hero-slider__clip { height: 40px; }
  .section-layanan, .section-jadwal, .section-salma, .section-payment, .section-kontak { padding: 48px 0; }
  .section-header { margin-bottom: 32px; }
  .section-header__title { font-size: 1.5rem; }
  .salma-mascot { width: 200px; }
  .belok-badge { display: none; }
  .pay-logo__img { height: 64px; }
  .gmaps-widget__header { flex-direction: column; align-items: flex-start; }
  .gmaps-widget__frame-wrap { height: 240px; }
}
</style>
