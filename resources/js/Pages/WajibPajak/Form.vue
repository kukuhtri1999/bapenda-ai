<script setup>
import { ref } from "vue";
import { router, Head } from "@inertiajs/vue3";

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
    return csrfTokenElement ? csrfTokenElement.getAttribute("content") : "";
};

const form = ref({
    nama: props.existingData?.nama || "",
    nopol: props.existingData?.nopol || "",
    nomer_wa: props.existingData?.nomer_wa || "",
});

const formValid = ref(false);
const loading = ref(false);
const errors = ref({});

// Snackbar
const snackbar = ref({
    show: false,
    message: "",
    color: "success",
    timeout: 4000,
    icon: "mdi-check-circle",
});

const showSnackbar = (
    message,
    color = "success",
    icon = "mdi-check-circle",
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
    let value = form.value.nopol.replace(/\s/g, "").toUpperCase();
    if (value.length > 0) {
        value = value
            .replace(/([A-Z]{1,2})([0-9]{1,4})([A-Z]{0,3})/, "$1 $2 $3")
            .trim();
    }
    form.value.nopol = value;
};

const startChatSession = async () => {
    try {
        loading.value = true;
        errors.value = {};

        const csrfToken = getCsrfToken();

        const response = await fetch("/api/wajib-pajak/start-chat", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (data.success) {
            showSnackbar(
                "Data berhasil disimpan! Mengarahkan ke chat...",
                "success",
                "mdi-check-circle",
            );

            // Redirect to chat after short delay
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            if (data.errors) {
                errors.value = data.errors;
            } else {
                showSnackbar(
                    data.message || "Terjadi kesalahan saat menyimpan data",
                    "error",
                    "mdi-alert-circle",
                );
            }
        }
    } catch (error) {
        console.error("Network error:", error);
        showSnackbar(
            "Terjadi kesalahan jaringan. Silakan coba lagi.",
            "error",
            "mdi-wifi-off",
        );
    } finally {
        loading.value = false;
    }
};

const proceedToChat = () => {
    window.location.href = "/customer-service";
};
</script>

<template>
    <v-app>
        <Head title="Form Data Wajib Pajak - Chat AI" />

        <v-main class="bg-gradient">
            <v-container class="py-8">
                <v-row justify="center">
                    <v-col cols="12" md="8" lg="6">
                        <v-card class="pa-8 form-card" elevation="12">
                            <div class="text-center mb-8">
                                <v-avatar
                                    color="primary"
                                    size="80"
                                    class="mb-4"
                                >
                                    <v-icon size="40" color="white"
                                        >mdi-chat</v-icon
                                    >
                                </v-avatar>
                                <h1
                                    class="text-h4 font-weight-bold text-primary mb-2"
                                >
                                    Layanan Chat AI Bapenda
                                </h1>
                                <p class="text-grey-600 mb-2">
                                    Untuk menggunakan layanan chat AI, silakan
                                    isi data terlebih dahulu
                                </p>
                                <v-chip
                                    color="info"
                                    variant="outlined"
                                    size="small"
                                >
                                    <v-icon left size="16"
                                        >mdi-shield-check</v-icon
                                    >
                                    Data Diperlukan untuk Akses Chat
                                </v-chip>
                            </div>

                            <!-- Show existing data if available -->
                            <v-alert
                                v-if="canProceedToChat"
                                type="success"
                                variant="tonal"
                                class="mb-6"
                                prominent
                            >
                                <template #title>Data Sudah Tersimpan</template>
                                <p class="mb-4">
                                    Anda sudah mengisi data sebelumnya:
                                </p>
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

                                <v-btn
                                    color="success"
                                    size="large"
                                    class="mr-3"
                                    @click="proceedToChat"
                                >
                                    <v-icon left>mdi-chat</v-icon>
                                    Lanjut ke Chat AI
                                </v-btn>

                                <v-btn
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
                                    <v-icon left>mdi-pencil</v-icon>
                                    Ubah Data
                                </v-btn>
                            </v-alert>

                            <!-- Form input -->
                            <v-form
                                @submit.prevent="startChatSession"
                                v-model="formValid"
                                v-show="
                                    !canProceedToChat ||
                                    (form.nama === '' &&
                                        form.nopol === '' &&
                                        form.nomer_wa === '')
                                "
                            >
                                <v-row>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="form.nama"
                                            label="Nama Lengkap"
                                            prepend-inner-icon="mdi-account"
                                            variant="outlined"
                                            :error-messages="errors.nama"
                                            :rules="[
                                                (v) =>
                                                    !!v || 'Nama wajib diisi',
                                            ]"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="form.nopol"
                                            label="Nomor Polisi (contoh: AA 0000 ZZZ)"
                                            prepend-inner-icon="mdi-car-info"
                                            variant="outlined"
                                            :error-messages="errors.nopol"
                                            :rules="[
                                                (v) =>
                                                    !!v ||
                                                    'Nomor polisi wajib diisi',
                                            ]"
                                            @input="formatNopol"
                                            placeholder="AA 0000 ZZZ"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="form.nomer_wa"
                                            label="Nomor WhatsApp"
                                            prepend-inner-icon="mdi-whatsapp"
                                            variant="outlined"
                                            :error-messages="errors.nomer_wa"
                                            :rules="[
                                                (v) =>
                                                    !!v ||
                                                    'Nomor WhatsApp wajib diisi',
                                                (v) =>
                                                    /^[0-9+\-\s]+$/.test(v) ||
                                                    'Format nomor tidak valid',
                                            ]"
                                            placeholder="08xxxxxxxxxx"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" class="text-center">
                                        <v-btn
                                            type="submit"
                                            color="primary"
                                            size="x-large"
                                            :loading="loading"
                                            :disabled="!formValid"
                                            class="px-8"
                                        >
                                            <v-icon left>mdi-chat</v-icon>
                                            Mulai Chat AI
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </v-form>

                            <!-- Additional info -->
                            <v-divider class="my-6"></v-divider>
                            <div class="text-center">
                                <v-chip
                                    color="grey"
                                    variant="text"
                                    size="small"
                                    class="mb-2"
                                >
                                    <v-icon left size="16"
                                        >mdi-information</v-icon
                                    >
                                    Informasi
                                </v-chip>
                                <p class="text-sm text-grey-600">
                                    Data yang Anda masukkan akan digunakan untuk
                                    memberikan layanan yang lebih personal.<br />
                                    <strong
                                        >Data akan disimpan secara
                                        permanen</strong
                                    >
                                    dalam database kami untuk keperluan bank
                                    data dan peningkatan layanan.<br />
                                    Data juga disimpan dalam session untuk akses
                                    chat AI.
                                </p>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Success/Error Snackbar -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="snackbar.timeout"
            location="top"
        >
            <v-icon left>{{ snackbar.icon }}</v-icon>
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
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
