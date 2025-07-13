<script setup>
import { ref, onMounted } from "vue";
import { router, Head } from "@inertiajs/vue3";
import FloatingChat from "@/Components/FloatingChat.vue";

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

// Popular questions data
const popularQuestions = ref([
    {
        icon: "mdi-credit-card",
        title: "Cara Bayar Pajak",
        description:
            "Informasi lengkap cara pembayaran pajak kendaraan bermotor",
        text: "Bagaimana cara bayar pajak kendaraan?",
    },
    {
        icon: "mdi-card-account-details",
        title: "Perpanjang STNK",
        description: "Syarat dan prosedur perpanjangan STNK kendaraan",
        text: "Apa saja syarat untuk perpanjang STNK?",
    },
    {
        icon: "mdi-map-marker",
        title: "Lokasi Samsat",
        description: "Alamat lengkap dan jam operasional Samsat Lamongan",
        text: "Dimana lokasi Samsat Lamongan dan jam operasionalnya?",
    },
    {
        icon: "mdi-currency-usd",
        title: "Tarif Pajak",
        description: "Informasi perhitungan tarif pajak kendaraan bermotor",
        text: "Bagaimana perhitungan tarif pajak kendaraan bermotor?",
    },
    {
        icon: "mdi-account-switch",
        title: "Balik Nama",
        description: "Syarat dan prosedur balik nama kendaraan bermotor",
        text: "Apa syarat untuk balik nama kendaraan?",
    },
    {
        icon: "mdi-web",
        title: "Cek Pajak Online",
        description: "Cara mengecek pajak kendaraan secara online",
        text: "Bagaimana cara cek pajak kendaraan secara online?",
    },
]);

// Services data
const services = ref([
    {
        icon: "mdi-credit-card",
        title: "Pembayaran Pajak",
        description:
            "Layanan pembayaran pajak kendaraan bermotor dengan berbagai metode",
        features: ["Online", "Offline", "Transfer Bank"],
    },
    {
        icon: "mdi-card-account-details",
        title: "Pengurusan STNK",
        description: "Perpanjangan, penggantian, dan penerbitan STNK baru",
        features: ["Perpanjang", "Ganti Hilang", "STNK Baru"],
    },
    {
        icon: "mdi-account-switch",
        title: "Balik Nama",
        description: "Proses balik nama kendaraan bermotor dan mutasi",
        features: ["Dalam Daerah", "Luar Daerah", "Mutasi"],
    },
    {
        icon: "mdi-file-document",
        title: "Pengurusan BPKB",
        description: "Layanan pengurusan dan penggantian BPKB kendaraan",
        features: ["BPKB Baru", "Ganti Rusak", "Ganti Hilang"],
    },
    {
        icon: "mdi-web",
        title: "Layanan Online",
        description: "Platform digital untuk berbagai layanan Samsat",
        features: ["e-Samsat", "Cek Pajak", "Info Denda"],
    },
    {
        icon: "mdi-information",
        title: "Informasi Umum",
        description: "Informasi tarif, syarat, dan prosedur layanan Samsat",
        features: ["FAQ", "Tarif", "Syarat"],
    },
]);

// Methods
const startChat = () => {
    // Navigate to customer service page
    router.visit("/customer-service");
};

const askQuestion = (question) => {
    // Store question in localStorage and navigate to chat
    localStorage.setItem("initial_question", question);
    router.visit("/customer-service");
};

const scrollToServices = () => {
    if (servicesSection.value) {
        servicesSection.value.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    }
};

const getCardColor = (index) => {
    const colors = ["#E9A5F1", "#C68EFD", "#8F87F1"];
    return colors[index % colors.length];
};

const getIconColor = (index) => {
    const colors = ["#C68EFD", "#8F87F1", "#E9A5F1"];
    return colors[index % colors.length];
};

const getServiceColor = (index) => {
    const colors = ["#E9A5F1", "#C68EFD", "#8F87F1"];
    return colors[index % colors.length];
};

onMounted(() => {
    // Add smooth scrolling behavior
    document.documentElement.style.scrollBehavior = "smooth";
});

function handleImageError() {
    document.getElementById("screenshot-container")?.classList.add("!hidden");
    document.getElementById("docs-card")?.classList.add("!row-span-1");
    document.getElementById("docs-card-content")?.classList.add("!flex-row");
    document.getElementById("background")?.classList.add("!hidden");
}
</script>

<template>
    <v-app>
        <Head title="Selamat Datang" />

        <!-- Hero Section -->
        <v-app-bar
            app
            :elevation="0"
            color="transparent"
            class="landing-navbar"
        >
            <v-container class="px-4">
                <v-row align="center">
                    <v-col cols="auto">
                        <div class="d-flex align-center">
                            <v-icon color="white" size="40" class="me-3"
                                >mdi-robot</v-icon
                            >
                            <span class="text-h6 font-weight-bold text-white"
                                >Bapenda AI</span
                            >
                        </div>
                    </v-col>
                    <v-spacer></v-spacer>
                    <v-col cols="auto" v-if="canLogin">
                        <template v-if="$page.props.auth.user">
                            <v-btn
                                color="white"
                                variant="outlined"
                                class="me-3"
                                :href="route('dashboard')"
                            >
                                Dashboard
                            </v-btn>
                        </template>
                        <template v-else>
                            <!-- <v-btn
                                color="white"
                                variant="outlined"
                                class="me-3"
                                :href="route('login')"
                            >
                                Login
                            </v-btn>
                            <v-btn
                                v-if="canRegister"
                                color="white"
                                variant="flat"
                                :href="route('register')"
                            >
                                Register
                            </v-btn> -->
                        </template>
                        <v-btn
                            color="white"
                            variant="flat"
                            class="ms-3"
                            @click="startChat"
                        >
                            <v-icon left>mdi-robot</v-icon>
                            Mulai Chat
                        </v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-app-bar>

        <v-main class="pa-0">
            <!-- Hero Section -->
            <section class="hero-section align-content-center">
                <v-container class="fill-height">
                    <v-row align="center" justify="center" class="text-center">
                        <v-col cols="12" md="8" lg="6">
                            <div class="hero-content">
                                <h1
                                    class="display-1 text-h4 font-weight-bold text-white mb-6"
                                >
                                    Asisten AI Customer Service
                                    <span class="text-accent"
                                        >Samsat Lamongan</span
                                    >
                                </h1>
                                <p class="text-h6 text-white-80 mb-8">
                                    Dapatkan informasi lengkap seputar pajak
                                    kendaraan, STNK, dan layanan Samsat dengan
                                    bantuan AI yang cerdas dan responsif 24/7
                                </p>

                                <div class="hero-actions">
                                    <v-btn
                                        size="x-large"
                                        color="white"
                                        variant="flat"
                                        class="me-4 mb-4"
                                        @click="startChat"
                                    >
                                        <v-icon left size="24"
                                            >mdi-robot</v-icon
                                        >
                                        Tanya AI Sekarang
                                    </v-btn>
                                    <v-btn
                                        size="x-large"
                                        color="white"
                                        variant="outlined"
                                        class="mb-4"
                                        @click="scrollToServices"
                                    >
                                        <v-icon left>mdi-information</v-icon>
                                        Lihat Layanan
                                    </v-btn>
                                </div>

                                <!-- Stats -->
                                <v-row class="mt-8">
                                    <v-col cols="4">
                                        <div class="stat-item">
                                            <h3
                                                class="text-h4 font-weight-bold text-white"
                                            >
                                                24/7
                                            </h3>
                                            <p class="text-white-70">
                                                Layanan Online
                                            </p>
                                        </div>
                                    </v-col>
                                    <v-col cols="4">
                                        <div class="stat-item">
                                            <h3
                                                class="text-h4 font-weight-bold text-white"
                                            >
                                                1000+
                                            </h3>
                                            <p class="text-white-70">
                                                FAQ Tersedia
                                            </p>
                                        </div>
                                    </v-col>
                                    <v-col cols="4">
                                        <div class="stat-item">
                                            <h3
                                                class="text-h4 font-weight-bold text-white"
                                            >
                                                Instan
                                            </h3>
                                            <p class="text-white-70">
                                                Respon Cepat
                                            </p>
                                        </div>
                                    </v-col>
                                </v-row>
                            </div>
                        </v-col>
                    </v-row>
                </v-container>

                <!-- Floating Elements -->
                <div class="floating-elements">
                    <div class="floating-circle circle-1"></div>
                    <div class="floating-circle circle-2"></div>
                    <div class="floating-circle circle-3"></div>
                </div>
            </section>

            <!-- Quick Actions Section -->
            <section class="quick-actions-section py-16">
                <v-container>
                    <v-row>
                        <v-col cols="12" class="text-center mb-8">
                            <h2
                                class="text-h3 font-weight-bold text-primary mb-4"
                            >
                                Pertanyaan Populer
                            </h2>
                            <p class="text-h6 text-grey-700">
                                Klik untuk langsung mendapatkan jawaban dari AI
                                Assistant
                            </p>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col
                            v-for="(question, index) in popularQuestions"
                            :key="index"
                            cols="12"
                            md="6"
                            lg="4"
                        >
                            <v-card
                                class="question-card h-100"
                                :color="getCardColor(index)"
                                variant="flat"
                                @click="askQuestion(question.text)"
                            >
                                <v-card-text class="pa-6">
                                    <div class="d-flex align-center mb-4">
                                        <v-avatar
                                            :color="getIconColor(index)"
                                            size="48"
                                            class="me-3"
                                        >
                                            <v-icon
                                                :icon="question.icon"
                                                color="white"
                                                size="24"
                                            ></v-icon>
                                        </v-avatar>
                                        <h4
                                            class="text-h6 font-weight-bold text-white"
                                        >
                                            {{ question.title }}
                                        </h4>
                                    </div>
                                    <p class="text-white-80 mb-0">
                                        {{ question.description }}
                                    </p>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>
            </section>

            <!-- Services Section -->
            <section ref="servicesSection" class="services-section py-16">
                <v-container>
                    <v-row>
                        <v-col cols="12" class="text-center mb-12">
                            <h2
                                class="text-h3 font-weight-bold text-primary mb-4"
                            >
                                Layanan Samsat Lamongan
                            </h2>
                            <p
                                class="text-h6 text-grey-700 max-width-600 mx-auto"
                            >
                                Informasi lengkap tentang berbagai layanan yang
                                tersedia di Samsat Lamongan
                            </p>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col
                            v-for="(service, index) in services"
                            :key="index"
                            cols="12"
                            md="6"
                            lg="4"
                        >
                            <v-card
                                class="service-card h-100"
                                variant="outlined"
                                :color="
                                    index % 2 === 0 ? 'grey-lighten-5' : 'white'
                                "
                            >
                                <v-card-text class="pa-6 text-center">
                                    <v-avatar
                                        :color="getServiceColor(index)"
                                        size="80"
                                        class="mb-4"
                                    >
                                        <v-icon
                                            :icon="service.icon"
                                            color="white"
                                            size="40"
                                        ></v-icon>
                                    </v-avatar>
                                    <h4
                                        class="text-h6 font-weight-bold text-black mb-3"
                                    >
                                        {{ service.title }}
                                    </h4>
                                    <p class="text-black mb-4">
                                        {{ service.description }}
                                    </p>
                                    <v-chip-group class="justify-center">
                                        <v-chip
                                            v-for="feature in service.features"
                                            :key="feature"
                                            size="small"
                                            color="green"
                                            variant="flat"
                                        >
                                            <span class="text-white">{{
                                                feature
                                            }}</span>
                                        </v-chip>
                                    </v-chip-group>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>
            </section>

            <!-- Contact Section -->
            <section class="contact-section py-16">
                <v-container>
                    <v-row align="center">
                        <v-col cols="12" md="6">
                            <h2
                                class="text-h3 font-weight-bold text-white mb-4"
                            >
                                Kontak Samsat Lamongan
                            </h2>
                            <p class="text-h6 text-white-80 mb-6">
                                Hubungi kami untuk informasi lebih lanjut atau
                                kunjungi langsung kantor Samsat Lamongan
                            </p>

                            <v-list class="bg-transparent">
                                <v-list-item class="pa-0 mb-3">
                                    <template v-slot:prepend>
                                        <v-avatar
                                            color="white"
                                            size="48"
                                            class="me-4"
                                        >
                                            <v-icon color="primary"
                                                >mdi-map-marker</v-icon
                                            >
                                        </v-avatar>
                                    </template>
                                    <v-list-item-title
                                        class="text-white font-weight-medium"
                                    >
                                        Jl. Veteran No. 1A, Tumenggungan,
                                        Lamongan
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-white-70">
                                        Kabupaten Lamongan, Jawa Timur 62211
                                    </v-list-item-subtitle>
                                </v-list-item>

                                <v-list-item class="pa-0 mb-3">
                                    <template v-slot:prepend>
                                        <v-avatar
                                            color="white"
                                            size="48"
                                            class="me-4"
                                        >
                                            <v-icon color="primary"
                                                >mdi-clock</v-icon
                                            >
                                        </v-avatar>
                                    </template>
                                    <v-list-item-title
                                        class="text-white font-weight-medium"
                                    >
                                        Senin - Jumat: 08.00 - 15.00 WIB
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-white-70">
                                        Sabtu: 08.00 - 12.00 WIB
                                    </v-list-item-subtitle>
                                </v-list-item>

                                <v-list-item class="pa-0 mb-3">
                                    <template v-slot:prepend>
                                        <v-avatar
                                            color="white"
                                            size="48"
                                            class="me-4"
                                        >
                                            <v-icon color="primary"
                                                >mdi-phone</v-icon
                                            >
                                        </v-avatar>
                                    </template>
                                    <v-list-item-title
                                        class="text-white font-weight-medium"
                                    >
                                        (0322) 311234
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-white-70">
                                        Telepon Kantor
                                    </v-list-item-subtitle>
                                </v-list-item>
                            </v-list>
                        </v-col>

                        <v-col cols="12" md="6" class="text-center">
                            <v-card
                                class="pa-8"
                                color="white"
                                variant="flat"
                                style="border-radius: 24px"
                            >
                                <v-avatar
                                    color="primary"
                                    size="120"
                                    class="mb-6"
                                >
                                    <v-icon size="60" color="white"
                                        >mdi-robot</v-icon
                                    >
                                </v-avatar>
                                <h3
                                    class="text-h5 font-weight-bold text-primary mb-4"
                                >
                                    Mulai Chat dengan AI Assistant
                                </h3>
                                <p class="text-grey-700 mb-6">
                                    Dapatkan jawaban instan untuk pertanyaan
                                    Anda tentang layanan Samsat
                                </p>
                                <v-btn
                                    size="x-large"
                                    color="primary"
                                    variant="flat"
                                    @click="startChat"
                                    block
                                >
                                    <v-icon left>mdi-robot</v-icon>
                                    Mulai Percakapan
                                </v-btn>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>
            </section>
        </v-main>

        <!-- Footer -->
        <v-footer class="footer-section pa-8">
            <v-container>
                <v-row>
                    <v-col cols="12" md="6">
                        <div class="d-flex align-center mb-4">
                            <v-icon color="white" size="40" class="me-3"
                                >mdi-robot</v-icon
                            >
                            <div>
                                <h4 class="text-h6 font-weight-bold text-white">
                                    Bapenda AI
                                </h4>
                                <p class="text-white-70 mb-0">
                                    Samsat Lamongan
                                </p>
                            </div>
                        </div>
                        <p class="text-white-70">
                            Sistem AI Customer Service untuk melayani masyarakat
                            Lamongan dengan informasi layanan Samsat yang akurat
                            dan terpercaya.
                        </p>
                    </v-col>
                    <v-col cols="12" md="6" class="text-md-end">
                        <p class="text-white-70 mb-2">
                            © 2025 Bapenda Samsat Lamongan. All rights
                            reserved.
                        </p>
                        <p class="text-white-70">
                            Powered by AI Technology | Laravel v{{
                                laravelVersion
                            }}
                            (PHP v{{ phpVersion }})
                        </p>
                    </v-col>
                </v-row>
            </v-container>
        </v-footer>

        <!-- Floating Chat Component -->
        <FloatingChat v-if="!isChatPageOpen" />
    </v-app>
</template>

<style scoped>
/* Hero Section */
.hero-section {
    min-height: 100vh;
    background: linear-gradient(135deg, #e9a5f1 0%, #c68efd 50%, #8f87f1 100%);
    position: relative;
    overflow: hidden;
}

.landing-navbar {
    background: rgba(233, 165, 241, 0.95) !important;
    backdrop-filter: blur(10px);
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
}

.stat-item {
    padding: 16px;
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

.floating-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    animation: float 6s ease-in-out infinite;
}

.circle-1 {
    width: 200px;
    height: 200px;
    top: 10%;
    left: 10%;
    animation-delay: -2s;
}

.circle-2 {
    width: 150px;
    height: 150px;
    top: 60%;
    right: 20%;
    animation-delay: -4s;
}

.circle-3 {
    width: 100px;
    height: 100px;
    top: 30%;
    right: 10%;
    animation-delay: -1s;
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
    }
}

/* Quick Actions Section */
.quick-actions-section {
    background: linear-gradient(to bottom, #f8f9fa, #ffffff);
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

    .hero-actions .v-btn {
        display: block;
        width: 100%;
        margin-bottom: 16px;
    }

    .floating-circle {
        display: none;
    }
}

@media (max-width: 600px) {
    .hero-section {
        padding-top: 80px;
    }

    .display-1 {
        font-size: 2rem !important;
    }

    .text-h6 {
        font-size: 1.1rem !important;
    }
}
</style>
