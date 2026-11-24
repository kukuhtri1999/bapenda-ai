<script setup>
const props = defineProps({
  showTitle: { type: Boolean, default: true },
});
const logoUrl = '/images/logo-samsat-lamongan.png?v=20261124';
const titleApp = import.meta.env.VITE_APP_NAME;
</script>

<template>
  <div class="flex items-center align-middle">
    <img :src="logoUrl || '/images/logo-samsat-lamongan.png'" alt="Logo Samsat Lamongan" width="48" height="48" class="object-contain flex-shrink-0" />
    <Transition name="fade-slide">
      <span
        v-if="showTitle"
        class="text-md pl-3 pt-2 !font-bold !font-poppins font whitespace-nowrap"
      >
        {{ titleApp }}
      </span>
    </Transition>
  </div>
</template>

<style scoped>
/* Add fade-slide transition to component to match layout transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
.fade-slide-enter-to,
.fade-slide-leave-from {
  opacity: 1;
  transform: translateX(0);
}
</style>
