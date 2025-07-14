<script setup>
import { ref, onMounted } from "vue";
import { router, Head } from "@inertiajs/vue3";

// Helper function to get CSRF token
const getCsrfToken = () => {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    return csrfTokenElement ? csrfTokenElement.getAttribute("content") : "";
};

const form = ref({
    nama: "",
    nopol: "",
    lima_digit_terakhir_no_rangka: "",
    nomer_wa: "",
});

const loading = ref(false);
const errors = ref({});
const showCaptchaDialog = ref(false);
const showResultDialog = ref(false);
const captchaImage = ref("");
const captchaAnswer = ref("");
const sessionData = ref(null);
const pkbResult = ref(null);
const recaptchaResponse = ref("");

// Load Google reCAPTCHA
onMounted(() => {
    const script = document.createElement("script");
    script.src =
        "https://www.google.com/recaptcha/api.js?render=6LcyLYIrAAAAAPs9t49OHlsCyKcqraxdmYHYVM2I";
    script.onload = () => {
        console.log("reCAPTCHA script loaded successfully");
    };
    script.onerror = () => {
        console.warn("Failed to load reCAPTCHA script");
    };
    document.head.appendChild(script);
});

const executeRecaptcha = () => {
    return new Promise((resolve, reject) => {
        if (typeof grecaptcha === "undefined") {
            console.warn("reCAPTCHA not loaded, proceeding without token");
            resolve("");
            return;
        }

        grecaptcha.ready(() => {
            grecaptcha
                .execute("6LcyLYIrAAAAAPs9t49OHlsCyKcqraxdmYHYVM2I", {
                    action: "submit",
                })
                .then((token) => {
                    resolve(token);
                })
                .catch((error) => {
                    console.warn("reCAPTCHA error:", error);
                    resolve(""); // Still proceed even if reCAPTCHA fails
                });
        });
    });
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

const submitForm = async () => {
    try {
        loading.value = true;
        errors.value = {};

        // Execute reCAPTCHA
        const token = await executeRecaptcha();

        // Get CSRF token
        const csrfToken = getCsrfToken();

        // Start monitoring for captcha file
        startCaptchaMonitoring();

        const response = await fetch("/api/pkb/check", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                ...form.value,
                "g-recaptcha-response": token,
            }),
        });

        const data = await response.json();

        if (data.success) {
            // Show success message and result
            showResultDialog.value = true;
            pkbResult.value = data;
            console.log("PKB check completed:", data);
        } else {
            if (data.errors) {
                errors.value = data.errors;
            } else {
                console.error("PKB check failed:", data.message);
                alert("Error: " + data.message);
            }
        }
    } catch (error) {
        console.error("Network error:", error);
        alert("Terjadi kesalahan jaringan. Silakan coba lagi.");
    } finally {
        loading.value = false;
    }
};

// Monitor for captcha file and auto-open dialog
const startCaptchaMonitoring = () => {
    let checkInterval;
    let checkCount = 0;
    const maxChecks = 15; // Check for 15 seconds (every 1 second)

    checkInterval = setInterval(async () => {
        checkCount++;

        try {
            // Check if captcha_ask.jpg exists
            const response = await fetch(
                "/storage/captcha_ask.jpg?" + Date.now(),
                {
                    method: "HEAD",
                },
            );

            if (response.ok) {
                // Captcha file found! Open dialog
                captchaImage.value = "/storage/captcha_ask.jpg?" + Date.now();
                showCaptchaDialog.value = true;
                clearInterval(checkInterval);

                // Auto-close dialog after 20 seconds
                setTimeout(() => {
                    if (showCaptchaDialog.value) {
                        showCaptchaDialog.value = false;
                        // Refresh page
                        window.location.reload();
                    }
                }, 20000);

                console.log("Captcha dialog opened automatically");
                return;
            }
        } catch (error) {
            // File doesn't exist yet, continue checking
        }

        // Stop checking after max attempts
        if (checkCount >= maxChecks) {
            clearInterval(checkInterval);
            console.log("Captcha monitoring stopped - timeout");
        }
    }, 1000); // Check every 1 second
};

// Save captcha answer to database
const saveCaptchaAnswer = async () => {
    if (!captchaAnswer.value.trim()) {
        alert("Please enter captcha answer");
        return;
    }

    try {
        const csrfToken = getCsrfToken();

        const response = await fetch("/api/pkb/save-captcha-answer", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                captcha_answer: captchaAnswer.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            console.log("Captcha answer saved:", captchaAnswer.value);
            showCaptchaDialog.value = false;
            captchaAnswer.value = "";
            alert(
                "Captcha answer submitted successfully! Please wait for results...",
            );
        } else {
            console.error("Failed to save captcha answer:", data.message);
            alert("Error: " + data.message);
        }
    } catch (error) {
        console.error("Network error:", error);
        alert("Terjadi kesalahan jaringan. Silakan coba lagi.");
    }
};

const submitCaptcha = async () => {
    try {
        loading.value = true;

        // Get CSRF token
        const csrfToken = getCsrfToken();

        const response = await fetch("/api/pkb/submit-captcha", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                wajib_pajak_id: sessionData.value.wajib_pajak_id,
                captcha_answer: captchaAnswer.value,
                session_id: sessionData.value.session_id,
            }),
        });

        const result = await response.json();

        if (result.success) {
            showCaptchaDialog.value = false;
            pkbResult.value = result;
            showResultDialog.value = true;
        } else {
            alert(result.message || "Captcha tidak valid");
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Terjadi kesalahan sistem");
    } finally {
        loading.value = false;
    }
};

const confirmData = async (isCorrect) => {
    try {
        loading.value = true;

        // Get CSRF token
        const csrfToken = getCsrfToken();

        const response = await fetch("/api/pkb/confirm", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                data_pkb_id: pkbResult.value.data_pkb.id,
                is_correct: isCorrect,
            }),
        });

        const result = await response.json();

        if (result.success) {
            showResultDialog.value = false;

            if (isCorrect) {
                // Store context and redirect to chat
                localStorage.setItem("pkb_context", result.chat_context);
                router.visit("/customer-service");
            } else {
                alert("Data telah dihapus");
                resetForm();
            }
        } else {
            alert(result.message || "Terjadi kesalahan");
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Terjadi kesalahan sistem");
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = {
        nama: "",
        nopol: "",
        lima_digit_terakhir_no_rangka: "",
        nomer_wa: "",
    };
    errors.value = {};
    captchaAnswer.value = "";
    sessionData.value = null;
    pkbResult.value = null;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(amount);
};

const onImageError = (event) => {
    console.error("Failed to load captcha image:", captchaImage.value);
    alert("Gagal memuat gambar captcha. Silakan coba lagi.");
};

const onImageLoad = (event) => {
    console.log("Captcha image loaded successfully:", captchaImage.value);
};
</script>

<template>
    <v-app>
        <Head title="Cek PKB Kendaraan" />

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
                                        >mdi-car</v-icon
                                    >
                                </v-avatar>
                                <h1
                                    class="text-h4 font-weight-bold text-primary mb-2"
                                >
                                    Cek PKB Kendaraan
                                </h1>
                                <p class="text-grey-600">
                                    Masukkan data kendaraan untuk mengecek pajak
                                    PKB
                                </p>
                            </div>

                            <v-form @submit.prevent="submitForm">
                                <v-row>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="form.nama"
                                            label="Nama Pemilik"
                                            prepend-inner-icon="mdi-account"
                                            variant="outlined"
                                            :error-messages="errors.nama"
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
                                            @input="formatNopol"
                                            placeholder="AA 0000 ZZZ"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="
                                                form.lima_digit_terakhir_no_rangka
                                            "
                                            label="5 Digit Terakhir Nomor Rangka"
                                            prepend-inner-icon="mdi-numeric"
                                            variant="outlined"
                                            :error-messages="
                                                errors.lima_digit_terakhir_no_rangka
                                            "
                                            maxlength="5"
                                            placeholder="12345"
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
                                            class="px-8"
                                        >
                                            <v-icon left>mdi-magnify</v-icon>
                                            Cek PKB
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </v-form>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Captcha Dialog -->
        <v-dialog v-model="showCaptchaDialog" max-width="500" persistent>
            <v-card class="pa-6">
                <v-card-title class="text-center">
                    <v-icon color="warning" size="48" class="mb-2"
                        >mdi-shield-check</v-icon
                    >
                    <div class="text-h5">Verifikasi Captcha</div>
                </v-card-title>

                <v-card-text class="text-center">
                    <p class="mb-4">
                        Silakan masukkan kode captcha di bawah ini:
                    </p>

                    <div class="captcha-container mb-4">
                        <div v-if="captchaImage">
                            <img
                                :src="captchaImage"
                                alt="Captcha"
                                class="captcha-image"
                                style="
                                    max-width: 100%;
                                    border: 1px solid #ddd;
                                    border-radius: 8px;
                                "
                                @error="onImageError"
                                @load="onImageLoad"
                            />
                        </div>
                        <div v-else>
                            <v-alert type="error" variant="tonal">
                                Gambar captcha tidak dapat dimuat
                            </v-alert>
                        </div>
                    </div>

                    <v-text-field
                        v-model="captchaAnswer"
                        label="Masukkan Captcha"
                        variant="outlined"
                        prepend-inner-icon="mdi-shield-check"
                        required
                    ></v-text-field>

                    <v-alert type="warning" variant="tonal" class="mb-4">
                        <small
                            >Anda memiliki waktu 20 detik untuk mengisi
                            captcha</small
                        >
                    </v-alert>
                </v-card-text>

                <v-card-actions class="justify-center">
                    <v-btn
                        color="primary"
                        :loading="loading"
                        @click="saveCaptchaAnswer"
                        size="large"
                    >
                        <v-icon left>mdi-check</v-icon>
                        Submit
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Result Dialog -->
        <v-dialog v-model="showResultDialog" max-width="800" persistent>
            <v-card v-if="pkbResult">
                <v-card-title class="text-center bg-primary pa-6">
                    <v-icon color="white" size="48" class="mb-2"
                        >mdi-check-circle</v-icon
                    >
                    <div class="text-h5 text-white">Hasil Pengecekan PKB</div>
                </v-card-title>

                <v-card-text class="pa-6">
                    <v-row>
                        <!-- Info Kendaraan -->
                        <v-col cols="12" md="6">
                            <h3
                                class="text-h6 font-weight-bold mb-4 text-primary"
                            >
                                <v-icon left color="primary">mdi-car</v-icon>
                                Informasi Kendaraan
                            </h3>
                            <v-table class="info-table">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Nama Pemilik
                                        </td>
                                        <td>
                                            {{ pkbResult.wajib_pajak.nama }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Nomor Polisi
                                        </td>
                                        <td
                                            class="font-weight-bold text-primary"
                                        >
                                            {{ pkbResult.data_pkb.nopol }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">Merk</td>
                                        <td>{{ pkbResult.data_pkb.merk }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Model
                                        </td>
                                        <td>{{ pkbResult.data_pkb.model }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">Type</td>
                                        <td>{{ pkbResult.data_pkb.type }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Tahun
                                        </td>
                                        <td>{{ pkbResult.data_pkb.tahun }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Warna
                                        </td>
                                        <td>{{ pkbResult.data_pkb.warna }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Masa Pajak
                                        </td>
                                        <td>
                                            {{
                                                pkbResult.data_pkb
                                                    .tanggal_masa_pajak
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-col>

                        <!-- Info Pajak -->
                        <v-col cols="12" md="6">
                            <h3
                                class="text-h6 font-weight-bold mb-4 text-primary"
                            >
                                <v-icon left color="primary"
                                    >mdi-currency-usd</v-icon
                                >
                                Rincian Pajak
                            </h3>
                            <v-table class="info-table">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-medium">PKB</td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb.pkb,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Opsen PKB
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb
                                                        .opsen_pkb,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            pkbResult.data_pkb.pkb_progresif > 0
                                        "
                                    >
                                        <td class="font-weight-medium">
                                            PKB Progresif
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb
                                                        .pkb_progresif,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            pkbResult.data_pkb.opsen_pkb_prog >
                                            0
                                        "
                                    >
                                        <td class="font-weight-medium">
                                            Opsen PKB Progresif
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb
                                                        .opsen_pkb_prog,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            SWDKLLJ
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb.swdkllj,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Parkir Berlangganan
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb
                                                        .parkir_berlangganan,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-medium">
                                            Pengesahan STNK
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb
                                                        .pengesahan_stnk,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr class="bg-primary text-white">
                                        <td class="font-weight-bold">TOTAL</td>
                                        <td class="font-weight-bold">
                                            {{
                                                formatCurrency(
                                                    pkbResult.data_pkb.total,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-col>

                        <!-- Tambahan Biaya jika ada -->
                        <v-col
                            v-if="
                                pkbResult.data_pkb.tambahan_biaya &&
                                pkbResult.data_pkb.tambahan_biaya.length > 0
                            "
                            cols="12"
                        >
                            <h3
                                class="text-h6 font-weight-bold mb-4 text-primary"
                            >
                                <v-icon left color="primary"
                                    >mdi-plus-circle</v-icon
                                >
                                Tambahan Biaya
                            </h3>
                            <v-table class="info-table">
                                <tbody>
                                    <tr
                                        v-for="biaya in pkbResult.data_pkb
                                            .tambahan_biaya"
                                        :key="biaya.id"
                                    >
                                        <td class="font-weight-medium">
                                            {{ biaya.label_biaya }}
                                        </td>
                                        <td>
                                            {{
                                                formatCurrency(
                                                    biaya.harga_biaya,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-col>
                    </v-row>

                    <v-alert type="info" variant="tonal" class="mt-6">
                        <v-icon left>mdi-information</v-icon>
                        <strong>Konfirmasi Data:</strong> Apakah informasi di
                        atas sudah benar?
                    </v-alert>
                </v-card-text>

                <v-card-actions class="justify-center pa-6">
                    <v-btn
                        color="success"
                        size="large"
                        class="mx-2"
                        :loading="loading"
                        @click="confirmData(true)"
                    >
                        <v-icon left>mdi-check</v-icon>
                        Ya, Benar
                    </v-btn>
                    <v-btn
                        color="error"
                        size="large"
                        class="mx-2"
                        :loading="loading"
                        @click="confirmData(false)"
                    >
                        <v-icon left>mdi-close</v-icon>
                        Tidak, Salah
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<style scoped>
.bg-gradient {
    background: linear-gradient(135deg, #e9a5f1 0%, #c68efd 50%, #8f87f1 100%);
    min-height: 100vh;
}

.form-card {
    border-radius: 24px !important;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95) !important;
}

.info-table {
    background: transparent;
}

.info-table tbody tr:nth-child(even) {
    background-color: rgba(0, 0, 0, 0.02);
}

.info-table td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.captcha-container {
    display: flex;
    justify-content: center;
    align-items: center;
}

.captcha-image {
    max-height: 100px;
}

.v-btn {
    border-radius: 12px !important;
    text-transform: none;
    font-weight: 600;
}

.v-text-field {
    margin-bottom: 8px;
}

.v-dialog .v-card {
    border-radius: 16px !important;
}
</style>
