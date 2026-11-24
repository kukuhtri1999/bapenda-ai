<script setup>
import { Link } from '@inertiajs/vue3';

const titleApp = import.meta.env.VITE_APP_NAME;
const logoUrl = '/images/logo-samsat-lamongan.png?v=20261124';
</script>

<template>
  <Link :href="'/'" class="flex items-center align-middle">
    <img :src="logoUrl || '/images/logo-samsat-lamongan.png'" alt="Logo Samsat Lamongan" width="72" height="72" class="object-contain" />
    <span class="text-h4 pl-3 pt-1 !font-bold !font-poppins font">{{
      titleApp
    }}</span>
  </Link>
</template>
