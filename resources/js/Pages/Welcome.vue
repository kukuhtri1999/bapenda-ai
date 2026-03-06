<script setup>
import { ref, onMounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import FloatingChat from '@/Components/FloatingChat.vue';
import PwaInstallButton from '@/Components/PwaInstallButton.vue';

defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  laravelVersion: {
    type: String,
    required: true,
  },
  phpVersion: {
    type: String,
    required: true,
  },
});

// Reactive data
const chatDialog = ref(false);
const isChatPageOpen = ref(false);
const servicesSection = ref(null);

const logoUrl = import.meta.env.VITE_APP_LOGO;

// Popular questions data
const popularQuestions = ref([
  {
    icon: 'mdi-credit-card',
    title: 'Cara Bayar Pajak',
    description: 'Informasi lengkap cara pembayaran pajak kendaraan bermotor',
    text: 'Bagaimana cara bayar pajak kendaraan?',
  },
  {
    icon: 'mdi-card-account-details',
    title: 'Pengesahan STNK',
    description: 'Syarat dan prosedur pengesahan STNK kendaraan',
    text: 'Apa saja syarat untuk pengesahan STNK?',
  },
  {
    icon: 'mdi-map-marker',
    title: 'Lokasi Samsat',
    description: 'Alamat lengkap dan jam operasional Samsat Lamongan',
    text: 'Dimana lokasi Samsat Lamongan dan jam operasionalnya?',
  },
  {
    icon: 'mdi-currency-usd',
    title: 'Tarif Pajak',
    description: 'Informasi perhitungan tarif pajak kendaraan bermotor',
    text: 'Bagaimana perhitungan tarif pajak kendaraan bermotor?',
  },
  {
    icon: 'mdi-account-switch',
    title: 'Balik Nama',
    description: 'Syarat dan prosedur balik nama kendaraan bermotor',
    text: 'Apa syarat untuk balik nama kendaraan?',
  },
  {
    icon: 'mdi-web',
    title: 'Cek Pajak Online',
    description: 'Cara mengecek pajak kendaraan secara online',
    text: 'Bagaimana cara cek pajak kendaraan secara online?',
  },
]);

// Jadwal Samsat Keliling Pagi
const activeKelilingDay = ref(0);
const kelilingPagiSchedule = [
  {
    day: 'Senin',
    short: 'Sen',
    locations: [
      'Pertigaan Sambopinggir (Karangbinangun)',
      'Depan Pantai Lorena (Paciran)',
      'Depan Terminal MPU Sukodadi',
    ],
  },
  {
    day: 'Selasa',
    short: 'Sel',
    locations: [
      'Depan Kantor Kec. Karanggeneng',
      'Jl. Raya Pangean (Maduran)',
      'Depan Kantor Kec. Kembangbahu',
    ],
  },
  {
    day: 'Rabu',
    short: 'Rab',
    locations: [
      'Depan Kantor Kec. Mantup',
      'Balai Desa Sugio',
      'Jl. Raya Pangean (Maduran)',
    ],
  },
  {
    day: 'Kamis',
    short: 'Kam',
    locations: [
      'Desa Kandangrejo (Kedungpring)',
      'Kantor Kec. Modo',
      'Depan Masjid Moropelang (Babat)',
    ],
  },
  {
    day: 'Jumat',
    short: 'Jum',
    locations: [
      'Balai Desa Puter (Kembangbahu)',
      'Depan Pantai Lorena (Paciran)',
      'Samping Koramil Sugio',
    ],
  },
  {
    day: 'Sabtu',
    short: 'Sab',
    locations: [
      'Depan Kantor Kec. Mantup',
      'Kantor Kec. Karanggeneng',
      'Pertigaan Lonjong (Glagah)',
    ],
  },
];

// Layanan Menetap (Payment Point)
const layananMenetap = [
  {
    name: 'Samsat Walkthru',
    address: 'Jl. Veteran No. 2, Lamongan',
    hours: 'Senin – Sabtu',
    icon: 'mdi-office-building-marker',
    color: '#6C33A0',
  },
  {
    name: 'Mal Pelayanan Publik (MPP)',
    address: 'Jl. Lamongrejo No. 120, Lamongan',
    hours: 'Senin – Jumat',
    icon: 'mdi-domain',
    color: '#C68EFD',
  },
  {
    name: 'Payment Point Ngimbang',
    address: 'Kantor Kec. Ngimbang',
    hours: 'Senin – Jumat',
    icon: 'mdi-map-marker-radius-outline',
    color: '#8F87F1',
  },
  {
    name: 'Payment Point Babat',
    address: 'Bank Jatim KCP Babat',
    hours: 'Senin – Jumat',
    icon: 'mdi-map-marker-radius-outline',
    color: '#8F87F1',
  },
  {
    name: 'Payment Point Brondong',
    address: 'Bank Jatim KCP Brondong',
    hours: 'Senin – Jumat',
    icon: 'mdi-map-marker-radius-outline',
    color: '#8F87F1',
  },
];

// BELOK WANGI – Samsat Keliling Malam
const belokWangiSchedule = [
  {
    days: 'Senin & Kamis',
    location: 'Depan Kantor KB Samsat',
    icon: 'mdi-office-building',
  },
  {
    days: 'Selasa & Jumat',
    location: 'Alun-Alun Lamongan',
    icon: 'mdi-city-variant-outline',
  },
  { days: 'Rabu', location: 'Terminal Sukodadi', icon: 'mdi-bus-stop' },
];

// Pembayaran Digital
const activePaymentTab = ref(0);
const paymentCategories = [
  {
    name: 'E-Commerce',
    icon: 'mdi-shopping-outline',
    color: '#00AA5B',
    platforms: [
      {
        name: 'Tokopedia',
        logo: '/images/payment/tokopedia.png',
        bg: '#FFFFFF',
      },
      { name: 'Shopee', logo: '/images/payment/shopee.png', bg: '#ffffff' },
      { name: 'Alfamart', logo: '/images/payment/alfamart.png', bg: '#CC192B' },
      {
        name: 'Indomaret',
        logo: '/images/payment/indomaret.png',
        bg: '#003F8E',
      },
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
      {
        name: 'Bank Jatim',
        logo: '/images/payment/bankjatim.png',
        bg: '#FFFFFF',
      },
      {
        name: 'Bukopin',
        logo: '/images/payment/new/bank-bukopin.png',
        bg: '#FFFFFF',
      },
      { name: 'BTN', logo: '/images/payment/btn.png', bg: '#FFFFFF' },
      {
        name: 'Pos Indonesia',
        logo: '/images/payment/pos-indonesia.png',
        bg: '#Ffffff',
      },
    ],
  },
];

// Methods
const startChat = () => {
  // Navigate to wajib pajak form first
  router.visit('/wajib-pajak');
};

const askQuestion = (question) => {
  // Store question in localStorage and navigate to wajib pajak form
  localStorage.setItem('initial_question', question);
  router.visit('/wajib-pajak');
};

const scrollToServices = () => {
  if (servicesSection.value) {
    servicesSection.value.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    });
  }
};

const getCardColor = (index) => {
  const colors = ['#E9A5F1', '#C68EFD', '#8F87F1'];
  return colors[index % colors.length];
};

const getIconColor = (index) => {
  const colors = ['#C68EFD', '#8F87F1', '#E9A5F1'];
  return colors[index % colors.length];
};

const getServiceColor = (index) => {
  const colors = ['#E9A5F1', '#C68EFD', '#8F87F1'];
  return colors[index % colors.length];
};

const todayDayIndex = new Date().getDay(); // 0=Sunday, 1=Monday...
// Map JS day (0-6) to schedule index (0=Senin..5=Sabtu)
const todayKelilingIndex = todayDayIndex >= 1 && todayDayIndex <= 6 ? todayDayIndex - 1 : 0;

onMounted(() => {
  document.documentElement.style.scrollBehavior = 'smooth';
  activeKelilingDay.value = todayKelilingIndex;
});

function handleImageError() {
  document.getElementById('screenshot-container')?.classList.add('!hidden');
  document.getElementById('docs-card')?.classList.add('!row-span-1');
  document.getElementById('docs-card-content')?.classList.add('!flex-row');
  document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
  <VApp>
    <Head title="Selamat Datang" />

    <!-- Hero Section -->
    <VAppBar app :elevation="0" color="transparent" class="landing-navbar">
      <VContainer class="px-2 px-sm-4">
        <VRow align="center" no-gutters>
          <VCol cols="auto">
            <div class="d-flex align-center">
              <VImg :src="logoUrl" alt="Logo" contain width="36" class="me-2" />
              <VImg
                src="/images/logo-jatim.png"
                alt="Logo Jatim"
                contain
                height="36"
                width="36"
                aspect-ratio="1"
                class="me-2 d-none d-sm-flex"
              />
              <VImg
                src="/images/Lambang_Polda_Jatim.png"
                alt="Logo Polri"
                contain
                height="36"
                width="36"
                aspect-ratio="1"
                class="me-2 d-none d-sm-flex"
              />
              <VImg
                src="/images/jasa-raharja.png"
                alt="Jasa Raharja"
                contain
                height="36"
                width="36"
                aspect-ratio="1"
                class="me-2 d-none d-md-flex"
              />
              <span class="navbar-brand-text text-white font-weight-bold"
                >SALMA AI</span
              >
            </div>
          </VCol>
          <VSpacer></VSpacer>
          <VCol cols="auto" v-if="canLogin">
            <template v-if="$page.props.auth.user">
              <VBtn
                color="white"
                variant="outlined"
                size="small"
                class="me-2"
                :href="route('dashboard')"
              >
                Dashboard
              </VBtn>
            </template>
            <VBtn
              color="white"
              variant="flat"
              size="small"
              class="ms-1"
              @click="startChat"
            >
              <VIcon size="18" class="me-1">mdi-robot</VIcon>
              <span class="d-none d-sm-inline">Mulai Chat</span>
              <span class="d-sm-none">Chat</span>
            </VBtn>
          </VCol>
        </VRow>
      </VContainer>
    </VAppBar>

    <VMain class="pa-0">
      <!-- Hero Section -->
      <section class="hero-section align-content-center">
        <VContainer class="fill-height">
          <VRow align="center" justify="center" class="text-center">
            <VCol cols="12" md="8" lg="8">
              <div class="hero-content">
                <div
                  class="salma-msg-avatar me-2 mt-1 flex-shrink-0 place-items-center"
                >
                  <img
                    src="/images/salma2.gif"
                    alt="SALMA"
                    loading="lazy"
                    class="salma-mascot"
                  />
                </div>
                <h1 class="display-1 text-h2 font-weight-bold text-white mb-6">
                  SALMA AI <br />
                  <span class="text-3xl">Samsat Lamongan Modern Assistant</span>
                </h1>
                <p class="text-xl text-white-80 mb-8">
                  Dapatkan informasi lengkap seputar pajak kendaraan, STNK, dan
                  layanan Samsat dengan bantuan AI yang cerdas dan responsif
                  24/7
                </p>

                <div class="hero-actions">
                  <VBtn
                    size="x-large"
                    color="white"
                    variant="flat"
                    class="me-4 mb-4"
                    @click="startChat"
                  >
                    <VIcon left size="24">mdi-robot</VIcon>
                    Tanya AI Sekarang
                  </VBtn>
                  <!-- <v-btn
                                        size="x-large"
                                        color="white"
                                        variant="outlined"
                                        class="me-4 mb-4"
                                        :href="route('pkb.index')"
                                    >
                                        <v-icon left>mdi-car</v-icon>
                                        Cek PKB
                                    </v-btn> -->
                  <VBtn
                    size="x-large"
                    color="white"
                    variant="outlined"
                    class="mb-4"
                    @click="scrollToServices"
                  >
                    <VIcon left>mdi-information</VIcon>
                    Lihat Layanan
                  </VBtn>
                </div>

                <!-- Stats -->
                <!-- <VRow class="mt-8">
                  <VCol cols="4">
                    <div class="stat-item">
                      <h3 class="text-h4 font-weight-bold text-white">24/7</h3>
                      <p class="text-white-70">Layanan Online</p>
                    </div>
                  </VCol>
                  <VCol cols="4">
                    <div class="stat-item">
                      <h3 class="text-h4 font-weight-bold text-white">1000+</h3>
                      <p class="text-white-70">FAQ Tersedia</p>
                    </div>
                  </VCol>
                  <VCol cols="4">
                    <div class="stat-item">
                      <h3 class="text-h4 font-weight-bold text-white">
                        Instan
                      </h3>
                      <p class="text-white-70">Respon Cepat</p>
                    </div>
                  </VCol>
                </VRow> -->
              </div>
            </VCol>
          </VRow>
        </VContainer>

        <!-- Floating Elements -->
        <div class="floating-elements">
          <!-- Animated Geometric Shapes -->
          <div class="geometric-shape shape-1"></div>
          <div class="geometric-shape shape-2"></div>
          <div class="geometric-shape shape-3"></div>
          <div class="geometric-shape shape-4"></div>
          <div class="geometric-shape shape-5"></div>
          <div class="geometric-shape shape-6"></div>

          <!-- Floating Particles -->
          <div class="particles-container">
            <div class="particle particle-1"></div>
            <div class="particle particle-2"></div>
            <div class="particle particle-3"></div>
            <div class="particle particle-4"></div>
            <div class="particle particle-5"></div>
            <div class="particle particle-6"></div>
            <div class="particle particle-7"></div>
            <div class="particle particle-8"></div>
            <div class="particle particle-9"></div>
            <div class="particle particle-10"></div>
          </div>

          <!-- Gradient Orbs -->
          <div class="gradient-orb orb-1"></div>
          <div class="gradient-orb orb-2"></div>
          <div class="gradient-orb orb-3"></div>

          <!-- Tech Grid Lines -->
          <div class="tech-grid">
            <div class="grid-line horizontal line-1"></div>
            <div class="grid-line horizontal line-2"></div>
            <div class="grid-line vertical line-3"></div>
            <div class="grid-line vertical line-4"></div>
          </div>

          <!-- Animated Dots Pattern -->
          <div class="dots-pattern">
            <div class="dot-row row-1">
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
            </div>
            <div class="dot-row row-2">
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
            </div>
            <div class="dot-row row-3">
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick Actions Section -->
      <section class="quick-actions-section py-16">
        <VContainer>
          <VRow>
            <VCol cols="12" class="text-center mb-8">
              <h2 class="text-h3 font-weight-bold text-primary mb-4">
                Pertanyaan Populer
              </h2>
              <p class="text-h6 text-grey-700">
                Klik untuk langsung mendapatkan jawaban dari AI Assistant
              </p></VCol
            >
          </VRow>

          <VRow>
            <VCol
              v-for="(question, index) in popularQuestions"
              :key="index"
              cols="12"
              md="6"
              lg="4"
            >
              <VCard
                class="question-card h-100"
                :color="getCardColor(index)"
                variant="flat"
                @click="askQuestion(question.text)"
              >
                <VCardText class="pa-6">
                  <div class="d-flex align-center mb-4">
                    <VAvatar
                      :color="getIconColor(index)"
                      size="48"
                      class="me-3"
                    >
                      <VIcon
                        :icon="question.icon"
                        color="white"
                        size="24"
                      ></VIcon>
                    </VAvatar>
                    <h4 class="text-h6 font-weight-bold text-white">
                      {{ question.title }}
                    </h4>
                  </div>
                  <p class="text-white-80 mb-0">
                    {{ question.description }}
                  </p>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VContainer>
      </section>

      <!-- Services Section -->
      <section ref="servicesSection" class="services-section py-16">
        <VContainer>
          <!-- Header -->
          <VRow>
            <VCol cols="12" class="text-center mb-2">
              <VChip
                color="primary"
                variant="flat"
                size="small"
                class="mb-4 px-4"
              >
                <VIcon start size="14">mdi-map-marker-check</VIcon>
                Layanan Kami
              </VChip>
              <h2 class="text-h3 font-weight-bold text-primary mb-3">
                Layanan Samsat Lamongan
              </h2>
              <p class="text-body-1 text-grey-700 max-width-700 mx-auto">
                Temukan jadwal dan lokasi layanan pajak kendaraan yang paling
                dekat dan nyaman untuk Anda
              </p>
            </VCol>
          </VRow>

          <!-- 1. Jadwal Samsat Keliling Pagi -->
          <VRow class="mt-10">
            <VCol cols="12">
              <VCard
                class="service-block-card service-block-keliling"
                elevation="0"
              >
                <VCardText class="pa-0">
                  <div
                    class="service-block-header service-block-header--keliling pa-5 pa-md-6"
                  >
                    <div class="d-flex align-center gap-3 flex-wrap">
                      <div class="service-block-icon-wrap">
                        <VIcon color="white" size="28">mdi-bus-clock</VIcon>
                      </div>
                      <div>
                        <div
                          class="text-caption text-white-70 text-uppercase font-weight-medium letter-spacing-1 mb-1"
                        >
                          Samsat Keliling
                        </div>
                        <h3 class="text-h5 font-weight-bold text-white mb-0">
                          Jadwal Samsat Keliling Pagi
                        </h3>
                      </div>
                      <VSpacer />
                      <VChip
                        color="white"
                        text-color="primary"
                        variant="flat"
                        size="small"
                        class="ms-auto"
                      >
                        <VIcon start size="12" color="success"
                          >mdi-circle</VIcon
                        >
                        Aktif
                      </VChip>
                    </div>
                    <p class="text-white-70 text-body-2 mt-3 mb-0">
                      Layanan berpindah setiap hari ke berbagai kecamatan di
                      Lamongan
                    </p>
                  </div>

                  <!-- Day Tabs -->
                  <div class="pa-4 pa-md-6">
                    <VTabs
                      v-model="activeKelilingDay"
                      color="primary"
                      bg-color="transparent"
                      show-arrows
                      density="compact"
                      class="keliling-day-tabs mb-5"
                    >
                      <VTab
                        v-for="(schedule, idx) in kelilingPagiSchedule"
                        :key="idx"
                        :value="idx"
                        class="keliling-day-tab text-body-2 font-weight-semibold"
                      >
                        <span class="d-none d-sm-inline">{{
                          schedule.day
                        }}</span>
                        <span class="d-sm-none">{{ schedule.short }}</span>
                      </VTab>
                    </VTabs>

                    <VWindow v-model="activeKelilingDay">
                      <VWindowItem
                        v-for="(schedule, idx) in kelilingPagiSchedule"
                        :key="idx"
                        :value="idx"
                      >
                        <VRow>
                          <VCol
                            v-for="(loc, locIdx) in schedule.locations"
                            :key="locIdx"
                            cols="12"
                            sm="6"
                            md="4"
                          >
                            <div class="location-card">
                              <div class="location-number">
                                {{ locIdx + 1 }}
                              </div>
                              <div class="location-info">
                                <VIcon
                                  size="16"
                                  color="primary"
                                  class="me-2 flex-shrink-0 mt-1"
                                  >mdi-map-marker</VIcon
                                >
                                <span
                                  class="text-body-2 font-weight-medium text-grey-800"
                                  >{{ loc }}</span
                                >
                              </div>
                            </div>
                          </VCol>
                        </VRow>
                      </VWindowItem>
                    </VWindow>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <!-- 2. Layanan Menetap -->
          <VRow class="mt-8">
            <VCol cols="12">
              <VCard class="service-block-card" elevation="0">
                <VCardText class="pa-0">
                  <div
                    class="service-block-header service-block-header--menetap pa-5 pa-md-6"
                  >
                    <div class="d-flex align-center gap-3 flex-wrap">
                      <div
                        class="service-block-icon-wrap service-block-icon-wrap--menetap"
                      >
                        <VIcon color="white" size="28"
                          >mdi-map-marker-multiple</VIcon
                        >
                      </div>
                      <div>
                        <div
                          class="text-caption text-white-70 text-uppercase font-weight-medium letter-spacing-1 mb-1"
                        >
                          Lokasi Tetap
                        </div>
                        <h3 class="text-h5 font-weight-bold text-white mb-0">
                          Layanan Payment Point 
                        </h3>
                      </div>
                    </div>
                    <p class="text-white-70 text-body-2 mt-3 mb-0">
                      Pilihan lokasi pembayaran tetap untuk kemudahan Anda
                    </p>
                  </div>
                  <div class="pa-4 pa-md-6">
                    <VRow>
                      <VCol
                        v-for="(place, idx) in layananMenetap"
                        :key="idx"
                        cols="12"
                        sm="6"
                        md="4"
                      >
                        <div class="menetap-card">
                          <div
                            class="menetap-icon-wrap"
                            :style="{ backgroundColor: place.color + '18' }"
                          >
                            <VIcon :color="place.color" size="22">{{
                              place.icon
                            }}</VIcon>
                          </div>
                          <div class="menetap-info">
                            <div
                              class="text-body-2 font-weight-bold text-grey-900 mb-1"
                            >
                              {{ place.name }}
                            </div>
                            <div class="text-caption text-grey-600 mb-1">
                              <VIcon size="12" color="grey-500" class="me-1"
                                >mdi-map-marker-outline</VIcon
                              >
                              {{ place.address }}
                            </div>
                            <VChip
                              size="x-small"
                              color="primary"
                              variant="tonal"
                              class="mt-1"
                            >
                              <VIcon start size="10">mdi-clock-outline</VIcon>
                              {{ place.hours }}
                            </VChip>
                          </div>
                        </div>
                      </VCol>
                    </VRow>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <!-- 3. BELOK WANGI -->
          <VRow class="mt-8">
            <VCol cols="12">
              <VCard
                class="service-block-card service-block-night"
                elevation="0"
              >
                <VCardText class="pa-0">
                  <div class="belok-wangi-header pa-5 pa-md-6">
                    <div class="d-flex align-center gap-3 flex-wrap">
                      <div class="belok-wangi-icon-wrap">
                        <VIcon color="#FFD700" size="28"
                          >mdi-weather-night</VIcon
                        >
                      </div>
                      <div>
                        <div class="d-flex align-center gap-2 mb-1">
                          <span
                            class="text-caption text-amber-300 text-uppercase font-weight-medium letter-spacing-1"
                            >Layanan Malam</span
                          >
                          <VChip
                            size="x-small"
                            color="amber-darken-1"
                            variant="flat"
                            >SPESIAL</VChip
                          >
                        </div>
                        <h3 class="text-h5 font-weight-bold text-white mb-0">
                          Samsat Keliling Malam
                          <span class="belok-wangi-badge ms-2"
                            >BELOK WANGI</span
                          >
                        </h3>
                      </div>
                      <VSpacer />
                      <div class="belok-wangi-time d-none d-sm-flex">
                        <VIcon color="#FFD700" size="18" class="me-1"
                          >mdi-clock-outline</VIcon
                        >
                        <span class="text-white font-weight-bold"
                          >18.00 – 20.00 WIB</span
                        >
                      </div>
                    </div>
                    <p class="text-white text-body-2 mt-3 mb-0">
                      <strong class="text-amber-300"
                        >Beda Lokasi Wayah Bengi</strong
                      >
                      — Khusus untuk Anda yang sibuk di siang hari
                    </p>
                    <div class="mt-2 d-flex d-sm-none align-center gap-1">
                      <VIcon color="#FFD700" size="16">mdi-clock-outline</VIcon>
                      <span class="text-white text-body-2 font-weight-bold"
                        >18.00 – 20.00 WIB</span
                      >
                    </div>
                  </div>
                  <div class="pa-4 pa-md-6">
                    <VRow>
                      <VCol
                        v-for="(sesh, idx) in belokWangiSchedule"
                        :key="idx"
                        cols="12"
                        sm="4"
                      >
                        <div class="belok-wangi-card">
                          <div class="belok-wangi-card-icon">
                            <VIcon color="#FFD700" size="24">{{
                              sesh.icon
                            }}</VIcon>
                          </div>
                          <div
                            class="text-amber-300 text-caption font-weight-bold text-uppercase mb-1"
                          >
                            {{ sesh.days }}
                          </div>
                          <div
                            class="text-white text-body-2 font-weight-medium"
                          >
                            {{ sesh.location }}
                          </div>
                        </div>
                      </VCol>
                    </VRow>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <!-- 4. Pembayaran Digital -->
          <VRow class="mt-8">
            <VCol cols="12">
              <VCard class="service-block-card" elevation="0">
                <VCardText class="pa-0">
                  <div
                    class="service-block-header service-block-header--digital pa-5 pa-md-6"
                  >
                    <div class="d-flex align-center gap-3 flex-wrap">
                      <div
                        class="service-block-icon-wrap service-block-icon-wrap--digital"
                      >
                        <VIcon color="white" size="28"
                          >mdi-contactless-payment</VIcon
                        >
                      </div>
                      <div>
                        <div
                          class="text-caption text-white-70 text-uppercase font-weight-medium letter-spacing-1 mb-1"
                        >
                          E-Samsat
                        </div>
                        <h3 class="text-h5 font-weight-bold text-white mb-0">
                          Pembayaran Digital
                        </h3>
                      </div>
                    </div>
                    <p class="text-white-70 text-body-2 mt-3 mb-0">
                      Bayar pajak kendaraan kapan saja dan di mana saja tanpa
                      perlu antri
                    </p>
                  </div>

                  <div class="pa-4 pa-md-6">
                    <!-- Category Tabs -->
                    <VTabs
                      v-model="activePaymentTab"
                      color="primary"
                      bg-color="transparent"
                      density="compact"
                      class="payment-category-tabs mb-6"
                    >
                      <VTab
                        v-for="(cat, idx) in paymentCategories"
                        :key="idx"
                        :value="idx"
                        class="text-body-2 font-weight-semibold"
                      >
                        <VIcon start size="16">{{ cat.icon }}</VIcon>
                        {{ cat.name }}
                      </VTab>
                    </VTabs>

                    <!-- Payment Logos Grid per Category -->
                    <VWindow v-model="activePaymentTab">
                      <VWindowItem
                        v-for="(cat, catIdx) in paymentCategories"
                        :key="catIdx"
                        :value="catIdx"
                      >
                        <VRow class="mt-2">
                          <VCol
                            v-for="(platform, pIdx) in cat.platforms"
                            :key="pIdx"
                            cols="6"
                            sm="4"
                            md="3"
                          >
                            <div class="payment-logo-card">
                              <div
                                class="payment-logo-img-wrap"
                                :style="{ backgroundColor: platform.bg }"
                              >
                                <img
                                  :src="platform.logo"
                                  :alt="platform.name"
                                  class="payment-logo-img"
                                />
                              </div>
                              <div
                                class="payment-logo-name text-caption text-center font-weight-medium mt-2"
                              >
                                {{ platform.name }}
                              </div>
                            </div>
                          </VCol>
                        </VRow>
                      </VWindowItem>
                    </VWindow>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VContainer>
      </section>

      <!-- Contact Section -->
      <section class="contact-section py-16">
        <VContainer>
          <VRow align="center">
            <VCol cols="12" md="6">
              <h2 class="text-h3 font-weight-bold text-white mb-4">
                Kontak Samsat Lamongan
              </h2>
              <p class="text-h6 text-white-80 mb-6">
                Hubungi kami untuk informasi lebih lanjut atau kunjungi langsung
                kantor Samsat Lamongan
              </p>

              <VList class="bg-transparent">
                <VListItem class="pa-0 mb-3">
                  <template v-slot:prepend>
                    <VAvatar color="white" size="48" class="me-4">
                      <VIcon color="primary">mdi-map-marker</VIcon>
                    </VAvatar>
                  </template>
                  <VListItemTitle class="text-white font-weight-medium">
                    Jl. Veteran No. 1A, Tumenggungan, Lamongan
                  </VListItemTitle>
                  <VListItemSubtitle class="text-white-70">
                    Kabupaten Lamongan, Jawa Timur 62211
                  </VListItemSubtitle>
                </VListItem>

                <VListItem class="pa-0 mb-3">
                  <template v-slot:prepend>
                    <VAvatar color="white" size="48" class="me-4">
                      <VIcon color="primary">mdi-clock</VIcon>
                    </VAvatar>
                  </template>
                  <VListItemTitle class="text-white font-weight-medium">
                    Senin - Kamis , Sabtu: 08.00 - 12.00 WIB
                  </VListItemTitle>
                  <VListItemSubtitle class="text-white-70">
                    Sabtu: 08.00 - 11.00 WIB
                  </VListItemSubtitle>
                </VListItem>

                <VListItem class="pa-0 mb-3">
                  <template v-slot:prepend>
                    <VAvatar color="white" size="48" class="me-4">
                      <VIcon color="primary">mdi-phone</VIcon>
                    </VAvatar>
                  </template>
                  <VListItemTitle class="text-white font-weight-medium">
                    (0322) 311234
                  </VListItemTitle>
                  <VListItemSubtitle class="text-white-70">
                    Telepon Kantor
                  </VListItemSubtitle>
                </VListItem>
              </VList>
            </VCol>

            <VCol cols="12" md="6" class="text-center">
              <VCard
                class="pa-8"
                color="white"
                variant="flat"
                style="border-radius: 24px"
              >
                <VAvatar color="primary" size="120" class="mb-6">
                  <VIcon size="60" color="white">mdi-robot</VIcon>
                </VAvatar>
                <h3 class="text-h5 font-weight-bold text-primary mb-4">
                  Mulai Chat dengan AI Assistant
                </h3>
                <p class="text-grey-700 mb-6">
                  Dapatkan jawaban instan untuk pertanyaan Anda tentang layanan
                  Samsat
                </p>
                <VBtn
                  size="x-large"
                  color="primary"
                  variant="flat"
                  @click="startChat"
                  block
                >
                  <VIcon left>mdi-robot</VIcon>
                  Mulai Percakapan
                </VBtn>
              </VCard>
            </VCol>
          </VRow>
        </VContainer>
      </section>
    </VMain>

    <!-- Footer -->
    <VFooter class="footer-section pa-8">
      <VContainer>
        <VRow>
          <VCol cols="12" md="6">
            <div class="d-flex align-center mb-4">
              <VIcon color="white" size="40" class="me-3">mdi-robot</VIcon>
              <div>
                <h4 class="text-h6 font-weight-bold text-white">SALMA AI</h4>
                <p class="text-white-70 mb-0">Samsat Lamongan</p>
              </div>
            </div>
            <p class="text-white-70">
              Sistem AI Customer Service untuk melayani masyarakat Lamongan
              dengan informasi layanan Samsat yang akurat dan terpercaya.
            </p>
          </VCol>
          <VCol cols="12" md="6" class="text-md-end">
            <p class="text-white-70 mb-2">
              © 2025 SALMA AI - Samsat Lamongan. All rights reserved.
            </p>
            <p class="text-white-70">Powered by AI Technology</p>
          </VCol>
        </VRow>
      </VContainer>
    </VFooter>

    <!-- PWA Install Button -->
    <PwaInstallButton />

    <!-- Floating Chat Component -->
    <FloatingChat v-if="!isChatPageOpen" />
  </VApp>
</template>

<style scoped>
/* Hero Section */
.hero-section {
  min-height: 100vh;
  background: linear-gradient(135deg, #e9a5f1 0%, #c68efd 50%, #8f87f1 100%);
  position: relative;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(
      circle at 20% 80%,
      rgba(255, 255, 255, 0.1) 0%,
      transparent 50%
    ),
    radial-gradient(
      circle at 80% 20%,
      rgba(255, 255, 255, 0.08) 0%,
      transparent 50%
    ),
    radial-gradient(
      circle at 40% 40%,
      rgba(255, 255, 255, 0.05) 0%,
      transparent 50%
    );
  z-index: 1;
}

.landing-navbar {
  background: rgba(233, 165, 241, 0.95) !important;
  backdrop-filter: blur(10px);
}

.navbar-brand-text {
  font-size: 1.1rem;
  letter-spacing: 0.02em;
}

@media (max-width: 600px) {
  .navbar-brand-text {
    font-size: 0.95rem;
  }

  .landing-navbar {
    background: rgba(198, 142, 253, 0.98) !important;
  }
}

.hero-content {
  position: relative;
  z-index: 2;
}

.text-accent {
  background: linear-gradient(45deg, #fff 30%, #f8f9fa 90%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-actions .v-btn {
  border-radius: 50px !important;
  text-transform: none;
  font-weight: 600;
  padding: 12px 32px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-actions .v-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
}

.stat-item {
  padding: 16px;
  transition: all 0.3s ease;
}

.stat-item:hover {
  transform: translateY(-3px);
}

/* Floating Elements */
.floating-elements {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

/* Geometric Shapes */
.geometric-shape {
  position: absolute;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.shape-1 {
  width: 80px;
  height: 80px;
  top: 15%;
  left: 8%;
  border-radius: 20px;
  animation: float-rotate 8s ease-in-out infinite;
  animation-delay: -1s;
}

.shape-2 {
  width: 60px;
  height: 60px;
  top: 25%;
  right: 15%;
  border-radius: 50%;
  animation: float-scale 6s ease-in-out infinite;
  animation-delay: -2s;
}

.shape-3 {
  width: 100px;
  height: 100px;
  bottom: 20%;
  left: 12%;
  border-radius: 16px;
  animation: float-rotate 10s ease-in-out infinite reverse;
  animation-delay: -3s;
}

.shape-4 {
  width: 40px;
  height: 40px;
  top: 35%;
  left: 25%;
  border-radius: 8px;
  animation: float-scale 7s ease-in-out infinite;
  animation-delay: -1.5s;
}

.shape-5 {
  width: 70px;
  height: 70px;
  bottom: 30%;
  right: 8%;
  border-radius: 50%;
  animation: float-rotate 9s ease-in-out infinite;
  animation-delay: -4s;
}

.shape-6 {
  width: 50px;
  height: 50px;
  top: 60%;
  right: 25%;
  border-radius: 12px;
  animation: float-scale 5s ease-in-out infinite;
  animation-delay: -2.5s;
}

/* Particles */
.particles-container {
  position: absolute;
  width: 100%;
  height: 100%;
}

.particle {
  position: absolute;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  animation: particle-float 12s linear infinite;
}

.particle-1 {
  width: 4px;
  height: 4px;
  top: 10%;
  left: 5%;
  animation-delay: 0s;
}
.particle-2 {
  width: 6px;
  height: 6px;
  top: 20%;
  left: 15%;
  animation-delay: -2s;
}
.particle-3 {
  width: 3px;
  height: 3px;
  top: 30%;
  left: 25%;
  animation-delay: -4s;
}
.particle-4 {
  width: 5px;
  height: 5px;
  top: 40%;
  left: 35%;
  animation-delay: -1s;
}
.particle-5 {
  width: 4px;
  height: 4px;
  top: 50%;
  left: 45%;
  animation-delay: -3s;
}
.particle-6 {
  width: 6px;
  height: 6px;
  top: 60%;
  left: 55%;
  animation-delay: -5s;
}
.particle-7 {
  width: 3px;
  height: 3px;
  top: 70%;
  left: 65%;
  animation-delay: -2.5s;
}
.particle-8 {
  width: 5px;
  height: 5px;
  top: 80%;
  left: 75%;
  animation-delay: -1.5s;
}
.particle-9 {
  width: 4px;
  height: 4px;
  top: 15%;
  right: 15%;
  animation-delay: -3.5s;
}
.particle-10 {
  width: 6px;
  height: 6px;
  top: 45%;
  right: 25%;
  animation-delay: -4.5s;
}

/* Gradient Orbs */
.gradient-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(40px);
  opacity: 0.6;
  animation: orb-pulse 8s ease-in-out infinite;
}

.orb-1 {
  width: 200px;
  height: 200px;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.3) 0%,
    transparent 70%
  );
  top: 10%;
  right: 10%;
  animation-delay: 0s;
}

.orb-2 {
  width: 150px;
  height: 150px;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.2) 0%,
    transparent 70%
  );
  bottom: 20%;
  left: 15%;
  animation-delay: -3s;
}

.orb-3 {
  width: 120px;
  height: 120px;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.25) 0%,
    transparent 70%
  );
  top: 50%;
  left: 50%;
  animation-delay: -1.5s;
}

/* Tech Grid Lines */
.tech-grid {
  position: absolute;
  width: 100%;
  height: 100%;
  opacity: 0.1;
}

.grid-line {
  position: absolute;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.5) 50%,
    transparent 100%
  );
  animation: grid-pulse 4s ease-in-out infinite;
}

.grid-line.horizontal {
  height: 1px;
  width: 100%;
}

.grid-line.vertical {
  width: 1px;
  height: 100%;
  background: linear-gradient(
    0deg,
    transparent 0%,
    rgba(255, 255, 255, 0.5) 50%,
    transparent 100%
  );
}

.line-1 {
  top: 25%;
  animation-delay: 0s;
}
.line-2 {
  top: 75%;
  animation-delay: -2s;
}
.line-3 {
  left: 30%;
  animation-delay: -1s;
}
.line-4 {
  right: 25%;
  animation-delay: -3s;
}

/* Dots Pattern */
.dots-pattern {
  position: absolute;
  top: 15%;
  right: 5%;
  opacity: 0.3;
}

.dot-row {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.dot {
  width: 6px;
  height: 6px;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  animation: dot-blink 3s ease-in-out infinite;
}

.row-1 .dot:nth-child(1) {
  animation-delay: 0s;
}
.row-1 .dot:nth-child(2) {
  animation-delay: 0.2s;
}
.row-1 .dot:nth-child(3) {
  animation-delay: 0.4s;
}
.row-1 .dot:nth-child(4) {
  animation-delay: 0.6s;
}
.row-1 .dot:nth-child(5) {
  animation-delay: 0.8s;
}

.row-2 .dot:nth-child(1) {
  animation-delay: 1s;
}
.row-2 .dot:nth-child(2) {
  animation-delay: 1.2s;
}
.row-2 .dot:nth-child(3) {
  animation-delay: 1.4s;
}
.row-2 .dot:nth-child(4) {
  animation-delay: 1.6s;
}
.row-2 .dot:nth-child(5) {
  animation-delay: 1.8s;
}

.row-3 .dot:nth-child(1) {
  animation-delay: 2s;
}
.row-3 .dot:nth-child(2) {
  animation-delay: 2.2s;
}
.row-3 .dot:nth-child(3) {
  animation-delay: 2.4s;
}
.row-3 .dot:nth-child(4) {
  animation-delay: 2.6s;
}
.row-3 .dot:nth-child(5) {
  animation-delay: 2.8s;
}

/* Animations */
@keyframes float-rotate {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
    opacity: 0.7;
  }
  25% {
    transform: translateY(-15px) rotate(90deg);
    opacity: 1;
  }
  50% {
    transform: translateY(-10px) rotate(180deg);
    opacity: 0.8;
  }
  75% {
    transform: translateY(-20px) rotate(270deg);
    opacity: 0.9;
  }
}

@keyframes float-scale {
  0%,
  100% {
    transform: translateY(0px) scale(1);
    opacity: 0.6;
  }
  50% {
    transform: translateY(-25px) scale(1.1);
    opacity: 1;
  }
}

@keyframes particle-float {
  0% {
    transform: translateY(0px) translateX(0px);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100vh) translateX(20px);
    opacity: 0;
  }
}

@keyframes orb-pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.4;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
}

@keyframes grid-pulse {
  0%,
  100% {
    opacity: 0.1;
  }
  50% {
    opacity: 0.3;
  }
}

@keyframes dot-blink {
  0%,
  70%,
  100% {
    opacity: 0.3;
    transform: scale(1);
  }
  35% {
    opacity: 1;
    transform: scale(1.2);
  }
}

/* Quick Actions Section */
.quick-actions-section {
  background: linear-gradient(to bottom, #f8f9fa, #ffffff);
}

.salma-mascot {
  width: 180px;
  height: 240px;
  /* border-radius: 50%; */
  overflow: hidden;
  flex-shrink: 0;
}

.question-card {
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 20px !important;
  transform: translateY(0);
}

.question-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15) !important;
}

/* Services Section */
.services-section {
  background: linear-gradient(180deg, #f8f6ff 0%, #ffffff 60%, #f8f9fa 100%);
}

.max-width-700 {
  max-width: 700px;
}

/* Service Block Cards */
.service-block-card {
  border-radius: 20px !important;
  overflow: hidden;
  border: 1px solid rgba(140, 100, 200, 0.12);
  background: #ffffff;
}

/* Keliling Pagi Header */
.service-block-header--keliling {
  background: linear-gradient(135deg, #6c33a0 0%, #9b59d0 50%, #c68efd 100%);
}

/* Layanan Menetap Header */
.service-block-header--menetap {
  background: linear-gradient(135deg, #8f87f1 0%, #c68efd 100%);
}

/* Pembayaran Digital Header */
.service-block-header--digital {
  background: linear-gradient(135deg, #0066cc 0%, #00aed6 100%);
}

.service-block-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
  flex-shrink: 0;
}

.service-block-icon-wrap--menetap {
  background: rgba(255, 255, 255, 0.2);
}

.service-block-icon-wrap--digital {
  background: rgba(255, 255, 255, 0.2);
}

.text-white-70 {
  color: rgba(255, 255, 255, 0.75) !important;
}

.letter-spacing-1 {
  letter-spacing: 0.08em;
}

/* Day Tabs */
.keliling-day-tabs .v-tab {
  text-transform: none;
  border-radius: 8px !important;
  min-width: 56px;
  font-size: 0.875rem;
}

.keliling-day-tabs .v-tab--selected {
  background: rgba(108, 51, 160, 0.08);
}

/* Location Cards */
.location-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #f8f6ff;
  border: 1px solid rgba(140, 100, 200, 0.15);
  border-radius: 12px;
  padding: 14px 16px;
  margin-bottom: 8px;
  transition: all 0.25s ease;
}

.location-card:hover {
  border-color: #c68efd;
  background: #f3eeff;
  transform: translateX(3px);
}

.location-number {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6c33a0, #c68efd);
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.location-info {
  display: flex;
  align-items: flex-start;
  flex: 1;
  min-width: 0;
  line-height: 1.4;
}

/* Layanan Menetap Cards */
.menetap-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  background: #fafafa;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 12px;
  transition: all 0.25s ease;
  height: 100%;
}

.menetap-card:hover {
  border-color: #c68efd;
  background: #f8f6ff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(108, 51, 160, 0.1);
}

.menetap-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* BELOK WANGI Night Section */
.service-block-night {
  background: #0d0d2b !important;
  border: 1px solid rgba(255, 215, 0, 0.15) !important;
}

.belok-wangi-header {
  background: linear-gradient(135deg, #0d0d2b 0%, #1a1a4e 50%, #0d0d2b 100%);
  border-bottom: 1px solid rgba(255, 215, 0, 0.15);
  position: relative;
  overflow: hidden;
}

.belok-wangi-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 200px;
  height: 200px;
  background: radial-gradient(
    circle,
    rgba(255, 215, 0, 0.08) 0%,
    transparent 70%
  );
  pointer-events: none;
}

.belok-wangi-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 215, 0, 0.15);
  border: 1px solid rgba(255, 215, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.belok-wangi-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 20px;
  background: rgba(255, 215, 0, 0.15);
  border: 1px solid rgba(255, 215, 0, 0.4);
  color: #ffd700;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  vertical-align: middle;
}

.belok-wangi-time {
  align-items: center;
  background: rgba(255, 215, 0, 0.12);
  border: 1px solid rgba(255, 215, 0, 0.3);
  border-radius: 20px;
  padding: 4px 14px;
}

.belok-wangi-card {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 215, 0, 0.2);
  border-radius: 14px;
  padding: 20px 18px;
  text-align: center;
  transition: all 0.25s ease;
  height: 100%;
  min-height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.belok-wangi-card:hover {
  background: rgba(255, 215, 0, 0.07);
  border-color: rgba(255, 215, 0, 0.5);
  transform: translateY(-3px);
}

.belok-wangi-card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 215, 0, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 4px;
}

/* Payment Digital */
.payment-category-tabs .v-tab {
  text-transform: none;
  border-radius: 8px !important;
  font-size: 0.875rem;
}

.payment-logo-card {
  width: 100%;
  cursor: default;
  transition: transform 0.25s ease;
}

.payment-logo-card:hover {
  transform: translateY(-4px);
}

.payment-logo-img-wrap {
  width: 100%;
  height: 80px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  border: 1px solid rgba(0, 0, 0, 0.06);
  padding: 10px 12px;
}

.payment-logo-img {
  max-width: 100%;
  max-height: 58px;
  object-fit: contain;
  display: block;
}

.payment-logo-name {
  color: #555 !important;
}

/* Responsive Services */
@media (max-width: 600px) {
  .service-block-card {
    border-radius: 16px !important;
  }
  .belok-wangi-badge {
    display: none;
  }
  .payment-logo-img-wrap {
    height: 64px;
  }
}

/* Contact Section */
.contact-section {
  background: linear-gradient(135deg, #8f87f1 0%, #c68efd 50%, #e9a5f1 100%);
}

/* Footer Section */
.footer-section {
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
}

/* Utilities */
.max-width-600 {
  max-width: 600px;
}

.text-white-80 {
  color: rgba(255, 255, 255, 0.8) !important;
}

.text-white-70 {
  color: rgba(255, 255, 255, 0.7) !important;
}

/* Mobile Responsive */
@media (max-width: 960px) {
  .hero-section {
    min-height: 80vh;
  }

  .display-1 {
    font-size: 2.5rem !important;
  }

  /* Mascot smaller on tablet */
  .salma-mascot {
    width: 120px !important;
    height: 160px !important;
  }

  .hero-actions .v-btn {
    display: block;
    width: 100%;
    margin-bottom: 16px;
  }

  /* Reduce animation complexity on mobile */
  .geometric-shape,
  .gradient-orb {
    display: none;
  }

  .particles-container .particle:nth-child(n + 6) {
    display: none;
  }

  .tech-grid {
    opacity: 0.05;
  }
}

@media (max-width: 600px) {
  .hero-section {
    padding-top: 80px;
  }

  .display-1 {
    font-size: 2rem !important;
    line-height: 1.25 !important;
  }

  .text-h6 {
    font-size: 1rem !important;
  }

  /* Hero description text */
  .hero-content p.text-xl {
    font-size: 1rem !important;
    line-height: 1.6 !important;
  }

  /* Hero buttons full-width stacked */
  .hero-actions {
    display: flex;
    flex-direction: column;
    align-items: stretch;
  }

  .hero-actions .v-btn {
    margin-right: 0 !important;
    width: 100%;
  }

  /* Hide complex animations on small screens */
  .dots-pattern,
  .tech-grid {
    display: none;
  }

  .particles-container .particle:nth-child(n + 4) {
    display: none;
  }

  /* Service cards */
  .service-card {
    border-radius: 12px !important;
  }
}
</style>
