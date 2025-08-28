<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { ref } from "vue";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);
const loading = ref(false);

const submit = () => {
    loading.value = true;
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => {
            form.reset("password");
            loading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Sign In - Bapenda AI" />
    <AuthLayout>
        <v-card
            class="pa-8 elevation-12 rounded-xl"
            max-width="400"
            width="100%"
        >
            <!-- Header -->
            <div class="text-center mb-8">
                <v-avatar size="80" class="mb-4">
                    <v-icon size="40" color="primary"
                        >mdi-shield-account</v-icon
                    >
                </v-avatar>
                <h1 class="text-h4 font-weight-bold text-primary mb-2">
                    Welcome Back
                </h1>
                <p class="text-body-1 text-medium-emphasis">
                    Sign in to access Bapenda AI Dashboard
                </p>
            </div>

            <!-- Success Status -->
            <v-alert v-if="status" type="success" class="mb-6" variant="tonal">
                {{ status }}
            </v-alert>

            <!-- Login Form -->
            <form @submit.prevent="submit">
                <v-container class="pa-0">
                    <!-- Email Field -->
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                v-model="form.email"
                                label="Email Address"
                                type="email"
                                prepend-inner-icon="mdi-email"
                                variant="outlined"
                                :error-messages="form.errors.email"
                                :disabled="loading"
                                required
                                class="mb-3"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <!-- Password Field -->
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                v-model="form.password"
                                label="Password"
                                :type="showPassword ? 'text' : 'password'"
                                prepend-inner-icon="mdi-lock"
                                :append-inner-icon="
                                    showPassword ? 'mdi-eye' : 'mdi-eye-off'
                                "
                                @click:append-inner="
                                    showPassword = !showPassword
                                "
                                variant="outlined"
                                :error-messages="form.errors.password"
                                :disabled="loading"
                                required
                                class="mb-3"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <!-- Remember Me & Forgot Password -->
                    <v-row class="align-center">
                        <v-col cols="6">
                            <v-checkbox
                                v-model="form.remember"
                                label="Remember me"
                                :disabled="loading"
                                density="compact"
                                hide-details
                            ></v-checkbox>
                        </v-col>
                        <v-col cols="6" class="text-right">
                            <v-btn
                                v-if="canResetPassword"
                                variant="text"
                                size="small"
                                color="primary"
                                :disabled="loading"
                                @click="
                                    $inertia.visit(route('password.request'))
                                "
                            >
                                Forgot Password?
                            </v-btn>
                        </v-col>
                    </v-row>

                    <!-- Login Button -->
                    <v-row class="mt-4">
                        <v-col cols="12">
                            <v-btn
                                type="submit"
                                color="primary"
                                size="large"
                                block
                                :loading="loading"
                                :disabled="!form.email || !form.password"
                                class="text-none font-weight-medium"
                            >
                                <v-icon left>mdi-login</v-icon>
                                Sign In
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-container>
            </form>

            <!-- Footer -->
            <div class="text-center mt-6">
                <v-divider class="mb-4"></v-divider>
                <p class="text-body-2 text-medium-emphasis">
                    Bapenda AI - Intelligent Tax Management System
                </p>
            </div>
        </v-card>
    </AuthLayout>
</template>
