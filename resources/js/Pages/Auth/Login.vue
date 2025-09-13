<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const showPassword = ref(false);
const loading = ref(false);

const submit = () => {
  loading.value = true;
  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => {
        form.reset('password');
        loading.value = false;
      },
    });
};
</script>

<template>
  <Head title="Sign In - SALMA AI" />
  <AuthLayout>
    <VCard class="pa-8 elevation-12 rounded-xl" max-width="400" width="100%">
      <!-- Header -->
      <div class="text-center mb-8">
        <VAvatar size="80" class="mb-4">
          <VIcon size="40" color="primary">mdi-shield-account</VIcon>
        </VAvatar>
        <h1 class="text-h4 font-weight-bold text-primary mb-2">Welcome Back</h1>
        <p class="text-body-1 text-medium-emphasis">
          Sign in to access SALMA AI Dashboard
        </p>
      </div>

      <!-- Success Status -->
      <VAlert v-if="status" type="success" class="mb-6" variant="tonal">
        {{ status }}
      </VAlert>

      <!-- Login Form -->
      <form @submit.prevent="submit">
        <VContainer class="pa-0">
          <!-- Email Field -->
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="Email Address"
                type="email"
                prepend-inner-icon="mdi-email"
                variant="outlined"
                :error-messages="form.errors.email"
                :disabled="loading"
                required
                class="mb-3"
              ></VTextField>
            </VCol>
          </VRow>

          <!-- Password Field -->
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append-inner="showPassword = !showPassword"
                variant="outlined"
                :error-messages="form.errors.password"
                :disabled="loading"
                required
                class="mb-3"
              ></VTextField>
            </VCol>
          </VRow>

          <!-- Remember Me & Forgot Password -->
          <VRow class="align-center">
            <VCol cols="6">
              <VCheckbox
                v-model="form.remember"
                label="Remember me"
                :disabled="loading"
                density="compact"
                hide-details
              ></VCheckbox>
            </VCol>
            <VCol cols="6" class="text-right">
              <VBtn
                v-if="canResetPassword"
                variant="text"
                size="small"
                color="primary"
                :disabled="loading"
                @click="$inertia.visit(route('password.request'))"
              >
                Forgot Password?
              </VBtn>
            </VCol>
          </VRow>

          <!-- Login Button -->
          <VRow class="mt-4">
            <VCol cols="12">
              <VBtn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="loading"
                :disabled="!form.email || !form.password"
                class="text-none font-weight-medium"
              >
                <VIcon left>mdi-login</VIcon>
                Sign In
              </VBtn>
            </VCol>
          </VRow>
        </VContainer>
      </form>

      <!-- Footer -->
      <div class="text-center mt-6">
        <VDivider class="mb-4"></VDivider>
        <p class="text-body-2 text-medium-emphasis">
          SALMA AI - Samsat Lamongan Modern Assistant
        </p>
      </div>
    </VCard>
  </AuthLayout>
</template>
