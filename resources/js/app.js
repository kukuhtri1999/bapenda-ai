import './bootstrap';
import '../css/app.css';

// ── PWA Service Worker registration ────────────────────────────────────────
// Uses workbox-generated SW from vite-plugin-pwa; registers on first load
// and auto-updates in the background via 'autoUpdate' strategy.
import { registerSW } from 'virtual:pwa-register';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createVuetify } from 'vuetify';
import Vue3Toastify, { toast } from 'vue3-toastify';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import 'vue3-toastify/dist/index.css';

// Vuetify
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';

if (typeof window !== 'undefined') {
  registerSW({
    immediate: true,
    onNeedRefresh() {
      // New content available — silent auto-update (skipWaiting + clientsClaim)
    },
    onOfflineReady() {
      console.info('[PWA] Offline ready.');
    },
    onRegistered(r) {
      // Periodically check for SW updates (every 60 min)
      r && setInterval(() => r.update(), 60 * 60 * 1000);
    },
  });
}

const vuetify = createVuetify({
  ssr: true,
  icons: {
    defaultSet: 'mdi', // This is already the default value - only for display purposes
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#C0392B', // Government Crimson Red Primary
          secondary: '#1B2838', // Government Navy
          accent: '#D32F2F', // Accent color
          error: '#FF5252', // Error color
          info: '#2196F3', // Info color
          success: '#27AE60', // Success color
          warning: '#F39C12', // Warning color
          greenlight: '#FEF2F2',
        },
      },
    },
  },
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(
    `./Pages/${name}.vue`,
    import.meta.glob('./Pages/**/*.vue'),
  ),
  setup({
    el, App, props, plugin,
  }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(vuetify)
      .use(Vue3Toastify, {
        autoClose: 3000,
        position: 'top-right',
        hideProgressBar: false,
        closeOnClick: true,
        pauseOnHover: true,
        draggable: true,
      })
      .provide('$toast', toast)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
