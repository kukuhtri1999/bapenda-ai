<template>
    <v-app>
        <v-container
            fluid
            class="fill-height"
            style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            "
        >
            <v-row justify="center" align="center" class="fill-height">
                <v-col cols="12" sm="8" md="6" lg="4">
                    <v-card class="mx-auto" elevation="12">
                        <v-card-title class="text-center pa-6">
                            <v-icon size="48" color="primary" class="mb-4"
                                >mdi-image-edit</v-icon
                            >
                            <h2 class="text-h4 font-weight-bold">
                                Photo Editor
                            </h2>
                            <p
                                class="text-subtitle-1 text-medium-emphasis mt-2"
                            >
                                Enter password to access the photo editing tool
                            </p>
                        </v-card-title>

                        <v-card-text class="pa-6">
                            <v-form @submit.prevent="submitPassword">
                                <v-text-field
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    label="Password"
                                    placeholder="Enter access password"
                                    variant="outlined"
                                    :append-inner-icon="
                                        showPassword ? 'mdi-eye' : 'mdi-eye-off'
                                    "
                                    @click:append-inner="
                                        showPassword = !showPassword
                                    "
                                    :error-messages="errorMessage"
                                    :loading="loading"
                                    class="mb-4"
                                    autofocus
                                ></v-text-field>

                                <v-btn
                                    type="submit"
                                    color="primary"
                                    size="large"
                                    block
                                    :loading="loading"
                                    :disabled="!password"
                                >
                                    Access Photo Editor
                                </v-btn>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </v-app>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const password = ref("");
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref("");

const submitPassword = () => {
    if (!password.value) return;

    loading.value = true;
    errorMessage.value = "";

    // Submit password to access the photo editor
    router.get(
        "/edit-foto",
        { password: password.value },
        {
            onSuccess: () => {
                loading.value = false;
            },
            onError: (errors) => {
                loading.value = false;
                errorMessage.value = "Invalid password. Please try again.";
            },
        },
    );
};
</script>

<style scoped>
.fill-height {
    min-height: 100vh;
}
</style>
