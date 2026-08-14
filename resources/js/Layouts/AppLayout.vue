<script setup>
import { ref, watchEffect, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';

defineProps({ title: String });

const drawer = ref(true);
const rail = ref(true);
const isHovered = ref(false);
const titleApp = import.meta.env.VITE_APP_NAME;
const logoUrl = import.meta.env.VITE_APP_LOGO;

const sidebarExpandedWidth = 260;
const sidebarRailWidth = 80;

const sidebarWidth = computed(() => {
  if (!drawer.value) return 0;
  if (rail.value) return isHovered.value ? sidebarExpandedWidth : sidebarRailWidth;
  return sidebarExpandedWidth;
});

const navLinks = ref([
  {
    icon: 'mdi-home-city',
    title: 'Dashboard',
    value: 'dashboard',
    href: route('dashboard'),
  },
  {
    icon: 'mdi-chart-areaspline',
    title: 'AI Chat Analytics',
    value: 'admin.analytics',
    href: route('admin.analytics'),
  },
  {
    icon: 'mdi-history',
    title: 'AI Chat History',
    value: 'admin.chat-history.index',
    href: route('admin.chat-history.index'),
  },
  {
    icon: 'mdi-star-box',
    title: 'Feedback Management',
    value: 'admin.feedback.index',
    href: route('admin.feedback.index'),
  },
  {
    icon: 'mdi-account-multiple',
    title: 'Wajib Pajak',
    value: 'admin.wajib-pajak.index',
    href: route('admin.wajib-pajak.index'),
  },
  {
    icon: 'mdi-account-cog',
    title: 'User Management',
    value: 'users.index',
    href: route('users.index'),
  },
  {
    icon: 'mdi-book-open-variant',
    title: 'Knowledge Base',
    value: 'knowledge-base.index',
    href: route('knowledge-base.index'),
  },
  {
    icon: 'mdi-lightbulb-alert-outline',
    title: 'AI Knowledge Gaps',
    value: 'admin.knowledge-gaps.index',
    href: route('admin.knowledge-gaps.index'),
  },
  {
    icon: 'mdi-shield-check-outline',
    title: 'RAG Evaluation Suite',
    value: 'admin.rag-evaluation.index',
    href: route('admin.rag-evaluation.index'),
  },
  {
    icon: 'mdi-gift',
    title: 'Lotre - Pengaturan',
    value: 'admin.lotre.settings',
    href: route('admin.lotre.settings'),
  },
  {
    icon: 'mdi-file-upload',
    title: 'Lotre - Import Data',
    value: 'admin.lotre.import',
    href: route('admin.lotre.import'),
  },
]);

watchEffect(() => {
  const isMobile = window.innerWidth <= 640;
  drawer.value = !isMobile;
  rail.value = true; // collapsed by default; expands on hover
});

const toggleDrawer = () => {
  drawer.value = !drawer.value;
};

const logout = () => {
  router.post(route('logout'));
};
</script>

<template>
  <div class="min-h-screen" style="overflow-x: hidden">
    <Head :title="title" />
    <VLayout class="app-layout">
      <!-- Sidebar -->
      <VNavigationDrawer
        v-model="drawer"
        :rail="rail"
        width="260"
        rail-width="80"
        class="drawer shadow-sm"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        :style="{
          width: sidebarWidth + 'px',
          position: 'fixed',
          left: 0,
          top: 0,
          height: '100vh',
        }"
      >
        <div class="px-3 py-3 flex items-center">
          <Link
            :href="route('dashboard')"
            class="flex items-center no-underline"
          >
            <ApplicationMark
              :showTitle="!rail || isHovered"
              class="h-10 w-auto"
            />
          </Link>
        </div>
        <VDivider></VDivider>
        <VList density="compact" nav class="py-2">
          <NavLink
            v-for="item in navLinks"
            :key="item.value"
            :href="item.href"
            class="w-full"
            :active="route().current(item.value)"
          >
            <VListItem
              :prepend-icon="item.icon"
              :title="item.title"
              :value="item.value"
              class="w-full"
            />
          </NavLink>
        </VList>
      </VNavigationDrawer>

      <!-- Main -->
      <VMain
        class="bg-gray-100 transition-all duration-200 pl-0"
        :style="{
          marginLeft: sidebarWidth + 'px',
          width: 'calc(100% - ' + sidebarWidth + 'px)',
          transition: 'margin-left .2s ease, width .2s ease',
          paddingTop: '72px',
        }"
      >
        <!-- Top Header Bar (fixed, full-width) -->
        <div
          class="top-header fixed z-40 bg-white/95 backdrop-blur border-b border-gray-100"
          :style="{ left: '0px', right: '0px', top: '0px' }"
        >
          <div
            class="max-w-full mx-auto px-4 sm:px-6 lg:px-16 h-16 flex items-center justify-between"
          >
            <div class="flex items-center gap-2">
              <VBtn
                class="sm:!hidden"
                icon
                variant="text"
                @click.stop="toggleDrawer"
              >
                <VIcon>mdi-menu</VIcon>
              </VBtn>
            </div>

            <div class="flex items-center gap-2">
              <VBtn icon variant="text"><VIcon>mdi-bell-outline</VIcon></VBtn>
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button
                    class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition"
                  >
                    <img
                      class="h-8 w-8 rounded-full object-cover"
                      :src="$page.props.auth.user.profile_photo_url"
                      :alt="$page.props.auth.user.name"
                    />
                  </button>
                </template>
                <template #content>
                  <div class="block px-4 py-2 text-xs text-gray-400">
                    Account
                  </div>
                  <DropdownLink :href="route('profile.show')"
                    >Profile</DropdownLink
                  >
                  <div class="border-t border-gray-200" />
                  <form @submit.prevent="logout">
                    <DropdownLink as="button">Log Out</DropdownLink>
                  </form>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>

        <!-- Page Content Container -->
        <div class="w-full px-4 sm:px-6 lg:px-8 py-6 min-h-[calc(100vh-72px)]">
          <VCard class="pa-8 w-full rounded-xl" variant="flat" elevation="0">
            <slot />
          </VCard>
        </div>
      </VMain>
    </VLayout>
  </div>
</template>

<style scoped>
.drawer {
  transition: width 0.25s ease;
}
.app-layout :deep(.v-navigation-drawer) {
  border-right: 1px solid #eef0f2;
}

/* Smooth fade-slide transition for app name */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(-10px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}

.fade-slide-enter-to,
.fade-slide-leave-from {
  opacity: 1;
  transform: translateX(0);
}

/* Header floating & spacing */
.top-header {
  top: 0;
  z-index: 60;
}

.app-layout .flex.items-center.align-middle img {
  border-radius: 6px;
}
/* deep of v-list-item__spacer */
.app-layout :deep(.v-list-item__spacer) {
  width: 16px !important;
}
</style>
