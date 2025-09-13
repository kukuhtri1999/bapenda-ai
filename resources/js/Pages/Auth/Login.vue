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
  <Head title="Masuk - SALMA AI" />
  <AuthLayout>
    <div class="login-card">
      <!-- Header -->
      <div class="login-header">
        <div class="app-brand">
          <h2 class="brand-text text-primary text-center">SALMA AI</h2>
        </div>
        <div class="welcome-text text-center">
          <h1 class="welcome-title">Selamat Datang Kembali!</h1>
        </div>
      </div>

      <!-- Success Status -->
      <div v-if="status" class="status-alert">
        <VIcon class="status-icon">mdi-check-circle</VIcon>
        {{ status }}
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="login-form">
        <!-- Email Field -->
        <div class="form-group">
          <VTextField
            v-model="form.email"
            type="email"
            variant="outlined"
            placeholder="admin@gmail.com"
            :error-messages="form.errors.email"
            :disabled="loading"
            required
            class="modern-input"
            hide-details="auto"
          ></VTextField>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <VTextField
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
            @click:append-inner="showPassword = !showPassword"
            variant="outlined"
            placeholder="Password"
            :error-messages="form.errors.password"
            :disabled="loading"
            required
            class="modern-input"
            hide-details="auto"
          ></VTextField>
        </div>

        <!-- Login Button -->
        <div class="form-group">
          <VBtn
            type="submit"
            color="primary"
            size="large"
            block
            :loading="loading"
            :disabled="!form.email || !form.password"
            class="login-btn"
          >
            Masuk Sekarang
          </VBtn>
        </div>

        <!-- Footer Links -->
        <!-- <div class="form-footer">
          <div class="footer-row">
            <span class="forgot-text">Lupa password</span>
            <a
              href="#"
              class="click-link"
              v-if="canResetPassword"
              @click.prevent="$inertia.visit(route('password.request'))"
            >
              Klik disini
            </a>
          </div>
        </div> -->
      </form>
    </div>
  </AuthLayout>
</template>

<style scoped>
/* Login Card */
.login-card {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

/* Header */
.login-header {
  text-align: right;
  margin-bottom: 2.5rem;
}

.app-brand {
  margin-bottom: 2rem;
}

.brand-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
  letter-spacing: -0.02em;
}

.welcome-text {
  text-align: right;
}

.welcome-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 1rem 0;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.welcome-subtitle {
  font-size: 0.95rem;
  color: #666;
  line-height: 1.5;
  margin: 0;
}

.create-link {
  color: #1a1a1a;
  text-decoration: underline;
  font-weight: 500;
  transition: color 0.2s ease;
}

.create-link:hover {
  color: #333;
  text-decoration: none;
}

/* Status Alert */
.status-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
  color: #0369a1;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.status-icon {
  color: #0369a1;
  font-size: 1.25rem;
}

/* Form */
.login-form {
  width: 100%;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

/* Modern Input Styling */
.modern-input :deep(.v-field) {
  border-radius: 12px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  box-shadow: none;
  transition: all 0.2s ease;
}

.modern-input :deep(.v-field:hover) {
  border-color: #d1d5db;
}

.modern-input :deep(.v-field--focused) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modern-input :deep(.v-field__input) {
  padding: 1rem 1.25rem;
  font-size: 1rem;
  min-height: 52px;
}

.modern-input :deep(.v-field__input::placeholder) {
  color: #9ca3af;
  opacity: 1;
}

.modern-input :deep(.v-field__append-inner) {
  padding-right: 1rem;
}

.modern-input :deep(.v-icon) {
  color: #6b7280;
  transition: color 0.2s ease;
}

.modern-input :deep(.v-field--focused .v-icon) {
  color: #3b82f6;
}

/* Login Button */
.login-btn {
  background: #1a1a1a !important;
  color: white !important;
  border-radius: 12px !important;
  font-weight: 600 !important;
  font-size: 1rem !important;
  text-transform: none !important;
  letter-spacing: 0 !important;
  height: 52px !important;
  box-shadow: none !important;
  transition: all 0.2s ease !important;
}

.login-btn:hover {
  background: #333 !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.login-btn:active {
  transform: translateY(0);
}

.login-btn.v-btn--disabled {
  background: #e5e7eb !important;
  color: #9ca3af !important;
  transform: none !important;
  box-shadow: none !important;
}

/* Google Button */
.google-btn {
  border: 1px solid #e5e7eb !important;
  background: white !important;
  color: #374151 !important;
  border-radius: 12px !important;
  font-weight: 500 !important;
  font-size: 1rem !important;
  text-transform: none !important;
  letter-spacing: 0 !important;
  height: 52px !important;
  box-shadow: none !important;
  transition: all 0.2s ease !important;
}

.google-btn:hover {
  border-color: #d1d5db !important;
  background: #f9fafb !important;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

.google-btn:active {
  transform: translateY(0);
}

.google-icon {
  width: 20px;
  height: 20px;
}

/* Footer */
.form-footer {
  margin-top: 2rem;
  text-align: center;
}

.footer-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.forgot-text {
  color: #6b7280;
}

.click-link {
  color: #1a1a1a;
  text-decoration: underline;
  font-weight: 500;
  transition: color 0.2s ease;
}

.click-link:hover {
  color: #333;
  text-decoration: none;
}

/* Responsive Design */
@media (max-width: 640px) {
  .login-header {
    text-align: center;
  }

  .welcome-text {
    text-align: center;
  }

  .welcome-title {
    font-size: 1.75rem;
  }

  .welcome-subtitle {
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .welcome-title {
    font-size: 1.5rem;
  }

  .brand-text {
    font-size: 1.25rem;
  }

  .form-group {
    margin-bottom: 1.25rem;
  }

  .modern-input :deep(.v-field__input) {
    padding: 0.875rem 1rem;
    min-height: 48px;
  }

  .login-btn,
  .google-btn {
    height: 48px !important;
    font-size: 0.9rem !important;
  }
}

/* Loading state */
.v-btn--loading {
  pointer-events: none;
}

.v-btn--loading .v-btn__content {
  opacity: 0.6;
}

/* Focus states for accessibility */
.modern-input :deep(.v-field--focused) {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

.login-btn:focus-visible,
.google-btn:focus-visible,
.create-link:focus-visible,
.click-link:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>
