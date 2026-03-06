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

// Services data
const services = ref([
  {
    icon: 'mdi-credit-card',
    title: 'Pembayaran Pajak',
    description:
      'Layanan pembayaran pajak kendaraan bermotor dengan berbagai metode',
    features: ['Online', 'Offline', 'Transfer Bank'],
  },
  {
    icon: 'mdi-card-account-details',
    title: 'Pengurusan STNK',
    description: 'Pengesahan, penggantian, dan penerbitan STNK baru',
    features: ['Pengesahan', 'Ganti Hilang', 'STNK Baru'],
  },
  {
    icon: 'mdi-account-switch',
    title: 'Balik Nama',
    description: 'Proses balik nama kendaraan bermotor dan mutasi',
    features: ['Dalam Daerah', 'Luar Daerah', 'Mutasi'],
  },
  {
    icon: 'mdi-file-document',
    title: 'Pengurusan BPKB',
    description: 'Layanan pengurusan dan penggantian BPKB kendaraan',
    features: ['BPKB Baru', 'Ganti Rusak', 'Ganti Hilang'],
  },
  {
    icon: 'mdi-web',
    title: 'Layanan Online',
    description: 'Platform digital untuk berbagai layanan Samsat',
    features: ['e-Samsat', 'Cek Pajak', 'Info Denda'],
  },
  {
    icon: 'mdi-information',
    title: 'Informasi Umum',
    description: 'Informasi tarif, syarat, dan prosedur layanan Samsat',
    features: ['FAQ', 'Tarif', 'Syarat'],
  },
]);

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

onMounted(() => {
  // Add smooth scrolling behavior
  document.documentElement.style.scrollBehavior = 'smooth';
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
          <VRow>
            <VCol cols="12" class="text-center mb-12">
              <h2 class="text-h3 font-weight-bold text-primary mb-4">
                Layanan Samsat Lamongan
              </h2>
              <p class="text-h6 text-grey-700 max-width-600 mx-auto">
                Informasi lengkap tentang berbagai layanan yang tersedia di
                Samsat Lamongan
              </p>
            </VCol>
          </VRow>

          <VRow>
            <VCol
              v-for="(service, index) in services"
              :key="index"
              cols="12"
              md="6"
              lg="4"
            >
              <VCard
                class="service-card h-100"
                variant="outlined"
                :color="index % 2 === 0 ? 'grey-lighten-5' : 'white'"
              >
                <VCardText class="pa-6 text-center">
                  <VAvatar
                    :color="getServiceColor(index)"
                    size="80"
                    class="mb-4"
                  >
                    <VIcon :icon="service.icon" color="white" size="40"></VIcon>
                  </VAvatar>
                  <h4 class="text-h6 font-weight-bold text-black mb-3">
                    {{ service.title }}
                  </h4>
                  <p class="text-black mb-4">
                    {{ service.description }}
                  </p>
                  <VChipGroup class="justify-center">
                    <VChip
                      v-for="feature in service.features"
                      :key="feature"
                      size="small"
                      color="primary"
                      variant="flat"
                    >
                      <span class="text-white">{{ feature }}</span>
                    </VChip>
                  </VChipGroup>
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
            <p class="text-white-70">
              Powered by AI Technology | Laravel v{{ laravelVersion }} (PHP v{{
                phpVersion
              }})
            </p>
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
  background: #ffffff;
}

.service-card {
  transition: all 0.3s ease;
  border-radius: 16px !important;
  border: 2px solid transparent;
}

.service-card:hover {
  border-color: #e9a5f1;
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(233, 165, 241, 0.2) !important;
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
