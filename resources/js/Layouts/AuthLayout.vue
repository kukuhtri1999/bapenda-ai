<script setup>
import { ref, onMounted } from 'vue';

const getImageUrl = (imageName) => `/storage/images/${imageName}`;
const logoUrl = import.meta.env.VITE_APP_LOGO;

// Animation states
const isLoaded = ref(false);
const floatingElements = ref([]);

onMounted(() => {
  isLoaded.value = true;

  // Initialize floating elements for background animation
  for (let i = 0; i < 6; i++) {
    floatingElements.value.push({
      id: i,
      x: Math.random() * 100,
      y: Math.random() * 100,
      size: Math.random() * 60 + 20,
      delay: Math.random() * 2,
      duration: Math.random() * 20 + 15,
    });
  }
});
</script>

<template>
  <div class="auth-container">
    <VRow class="ma-0 min-h-screen">
      <!-- Left Side - Modern Branding -->
      <VCol
        cols="0"
        sm="6"
        md="7"
        lg="8"
        class="hidden sm:flex align-center justify-center pa-0 position-relative auth-left-panel"
      >
        <!-- Animated Background -->
        <div class="auth-background">
          <!-- Floating Elements -->
          <div
            v-for="element in floatingElements"
            :key="element.id"
            class="floating-element"
            :style="{
              left: element.x + '%',
              top: element.y + '%',
              width: element.size + 'px',
              height: element.size + 'px',
              animationDelay: element.delay + 's',
              animationDuration: element.duration + 's',
            }"
          ></div>

          <!-- Gradient Overlay -->
          <div class="gradient-overlay"></div>
        </div>

        <!-- Content -->
        <div class="auth-content" :class="{ 'content-loaded': isLoaded }">
          <div class="flex justify-center mb-10 w-fit place-self-center">
            <VImg
              :src="logoUrl"
              alt="Logo"
              contain
              :height="40"
              width="40"
              aspect-ratio="1"
              class="me-3"
            />
            <VImg
              src="/images/logo-jatim.png"
              alt="Logo Jatim"
              contain
              :height="40"
              width="40"
              aspect-ratio="1"
              class="me-2"
            />
            <VImg
              src="/images/Lambang_Polda_Jatim.png"
              alt="Logo Polda Jatim"
              contain
              height="40"
              width="40"
              aspect-ratio="1"
              class="me-2"
            />
            <VImg
              src="/images/jasa-raharja.png"
              alt="Jasa Raharja"
              contain
              height="40"
              width="40"
              aspect-ratio="1"
              class="me-3"
            />
          </div>
          <!-- Brand Logo -->
          <div class="brand-logo">
            <div class="logo-icon">
              <VIcon size="80" color="white" class="logo-pulse"
                >mdi-robot-excited</VIcon
              >
            </div>
          </div>

          <!-- Main Heading -->
          <div class="brand-text">
            <h1 class="brand-title">
              Halo <span class="wave-hand">👋</span><br />
              <span class="brand-name">SALMA AI!</span>
            </h1>

            <div class="brand-subtitle">
              Lewati tugas manual yang berulang-ulang.<br />
              Dapatkan produktivitas tinggi melalui otomatisasi<br />
              dan hemat banyak waktu!
            </div>
          </div>

          <!-- Feature Pills -->
          <div class="feature-pills">
            <div class="feature-pill" style="animation-delay: 0.2s">
              <VIcon size="20" class="mr-2">mdi-robot</VIcon>
              AI Assistant 24/7
            </div>
            <div class="feature-pill" style="animation-delay: 0.4s">
              <VIcon size="20" class="mr-2">mdi-chart-areaspline</VIcon>
              Real-time Analytics
            </div>
          </div>

          <!-- Footer -->
          <div class="auth-footer">© 2025 SALMA AI. All rights reserved.</div>
        </div>
      </VCol>

      <!-- Right Side - Auth Form -->
      <VCol cols="12" sm="6" md="5" lg="4" class="auth-right-panel">
        <div class="auth-form-container" :class="{ 'form-loaded': isLoaded }">
          <slot />
        </div>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
/* Main Container */
.auth-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
}

/* Left Panel */
.auth-left-panel {
  position: relative;
  overflow: hidden;
}

.auth-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 60%, #f093fb 100%);
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    45deg,
    rgba(102, 126, 234, 0.9) 0%,
    rgba(118, 75, 162, 0.8) 100%
  );
}

/* Floating Elements Animation */
.floating-element {
  position: absolute;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  animation: float infinite ease-in-out;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
    opacity: 0.7;
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
    opacity: 1;
  }
}

/* Content */
.auth-content {
  position: relative;
  z-index: 10;
  text-align: center;
  color: white;
  padding: 2rem;
  max-width: 600px;
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.8s ease-out;
}

.content-loaded {
  opacity: 1;
  transform: translateY(0);
}

/* Brand Logo */
.brand-logo {
  margin-bottom: 2rem;
}

.logo-icon {
  display: inline-block;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 50%;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  animation: logoGlow 3s ease-in-out infinite;
}

.logo-pulse {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes logoGlow {
  0%,
  100% {
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
  }
  50% {
    box-shadow: 0 0 40px rgba(255, 255, 255, 0.6);
  }
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

/* Brand Text */
.brand-text {
  margin-bottom: 3rem;
}

.brand-title {
  font-size: 3.5rem;
  font-weight: 300;
  line-height: 1.2;
  margin-bottom: 1.5rem;
  animation: slideInUp 0.8s ease-out 0.3s both;
}

.brand-name {
  font-weight: 700;
  background: linear-gradient(45deg, #ffffff, #f0f9ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.wave-hand {
  display: inline-block;
  animation: wave 2s ease-in-out infinite;
}

@keyframes wave {
  0%,
  100% {
    transform: rotate(0deg);
  }
  25% {
    transform: rotate(20deg);
  }
  75% {
    transform: rotate(-10deg);
  }
}

.brand-subtitle {
  font-size: 1.1rem;
  font-weight: 300;
  line-height: 1.6;
  opacity: 0.9;
  animation: slideInUp 0.8s ease-out 0.5s both;
}

/* Feature Pills */
.feature-pills {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 3rem;
  align-items: center;
}

.feature-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 50px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 0.9rem;
  font-weight: 500;
  opacity: 0;
  transform: translateX(-30px);
  animation: slideInLeft 0.6s ease-out forwards;
  transition: all 0.3s ease;
}

.feature-pill:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

@keyframes slideInLeft {
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Footer */
.auth-footer {
  font-size: 0.85rem;
  opacity: 0.7;
  animation: fadeIn 0.8s ease-out 1s both;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 0.7;
  }
}

/* Right Panel */
.auth-right-panel {
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
}

.auth-right-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(
    circle at top right,
    rgba(102, 126, 234, 0.05) 0%,
    transparent 50%
  );
  pointer-events: none;
}

.auth-form-container {
  width: 100%;
  max-width: 400px;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.8s ease-out 0.2s;
}

.form-loaded {
  opacity: 1;
  transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 768px) {
  .brand-title {
    font-size: 2.5rem;
  }

  .brand-subtitle {
    font-size: 1rem;
  }

  .feature-pills {
    gap: 0.75rem;
  }

  .feature-pill {
    padding: 0.6rem 1.2rem;
    font-size: 0.85rem;
  }

  .auth-content {
    padding: 1.5rem;
  }
}

@media (max-width: 600px) {
  .auth-right-panel {
    padding: 1.5rem;
  }

  .auth-form-container {
    max-width: 100%;
  }
}

/* Legacy styles for compatibility */
.absolute {
  position: absolute;
}

.inset-0 {
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.z-10 {
  z-index: 10;
}

.max-w-md {
  max-width: 28rem;
}

.max-w-sm {
  max-width: 24rem;
}

.leading-relaxed {
  line-height: 1.625;
}
</style>
