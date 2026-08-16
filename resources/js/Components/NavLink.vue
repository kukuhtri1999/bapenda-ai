<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  href: String,
  active: Boolean,
});

const classes = computed(() => (props.active
  ? 'inline-flex bg-red-50 items-center p-1 pl-2 border-r-4 border-solid border-primary text-sm font-semibold leading-5 text-red-700 focus:outline-none focus:border-red-700 transition duration-150 ease-in-out'
  : 'inline-flex items-center p-1 pl-2 border-transparent text-sm font-medium leading-5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out'));
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>
<style>
.border-primary {
  border-color: #C0392B !important;
}
</style>
