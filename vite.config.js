import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vuetify from 'vite-plugin-vuetify';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.js',
      ssr: 'resources/js/ssr.js',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
        compilerOptions: {
          isCustomElement: (tag) => false,
          whitespace: 'preserve',
        },
      },
    }),
    vuetify(),
    VitePWA({
      // Strategy: auto-update service worker in background
      registerType: 'autoUpdate',
      // We register manually via virtual:pwa-register in app.js
      injectRegister: false,
      // SW + manifest must live at the site root for scope '/'
      outDir: 'public',
      filename: 'sw.js',
      // Dev PWA enabled for local testing
      devOptions: {
        enabled: false,
      },
      workbox: {
        // No precache list (avoid path complexity with Laravel build dir)
        globPatterns: [],
        cleanupOutdatedCaches: true,
        skipWaiting: true,
        clientsClaim: true,
        // IMPORTANT: must be null for Laravel — there is no static index.html
        // Without this, workbox would intercept ALL navigations with a broken fallback
        navigateFallback: null,
        // Runtime caching strategies
        runtimeCaching: [
          {
            // Inertia page navigations — NetworkFirst (5s timeout, then cache)
            urlPattern: ({ request }) => request.mode === 'navigate',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'pages-cache',
              networkTimeoutSeconds: 5,
              expiration: { maxEntries: 50, maxAgeSeconds: 86400 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            // Vite-built JS/CSS — StaleWhileRevalidate (instant load, update in BG)
            urlPattern: /\/build\/.+\.(js|css)$/,
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'assets-cache',
              expiration: { maxEntries: 60, maxAgeSeconds: 604800 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            // API / Inertia JSON responses — always NetworkOnly
            urlPattern: /\/api\//,
            handler: 'NetworkOnly',
          },
          {
            // Static images — CacheFirst (long TTL)
            urlPattern:
              /\/(?:icons|images)\/.+\.(png|jpg|jpeg|svg|gif|webp|ico)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'images-cache',
              expiration: { maxEntries: 100, maxAgeSeconds: 604800 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            // Google/Bunny Fonts — CacheFirst (long TTL)
            urlPattern:
              /^https:\/\/fonts\.(bunny|googleapis|gstatic)\.net\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'fonts-cache',
              expiration: { maxEntries: 20, maxAgeSeconds: 31536000 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
        ],
        // Don't cache admin/auth routes
        navigateFallbackDenylist: [
          /^\/admin/,
          /^\/api/,
          /^\/login/,
          /^\/register/,
        ],
      },
      manifest: {
        name: 'SALMA AI — Samsat Lamongan',
        short_name: 'SALMA AI',
        description:
          'Asisten digital resmi Bapenda Samsat Lamongan — informasi pajak kendaraan, jadwal layanan, dan prosedur samsat kapan saja.',
        theme_color: '#6C33A0',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait-primary',
        scope: '/',
        start_url: '/',
        lang: 'id',
        categories: ['government', 'productivity'],
        icons: [
          { src: '/icons/icon-72x72.png', sizes: '72x72', type: 'image/png' },
          { src: '/icons/icon-96x96.png', sizes: '96x96', type: 'image/png' },
          {
            src: '/icons/icon-128x128.png',
            sizes: '128x128',
            type: 'image/png',
          },
          {
            src: '/icons/icon-144x144.png',
            sizes: '144x144',
            type: 'image/png',
          },
          {
            src: '/icons/icon-152x152.png',
            sizes: '152x152',
            type: 'image/png',
          },
          {
            src: '/icons/icon-192x192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: '/icons/icon-384x384.png',
            sizes: '384x384',
            type: 'image/png',
          },
          {
            src: '/icons/icon-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/icons/icon-192x192-maskable.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'maskable',
          },
          {
            src: '/icons/icon-512x512-maskable.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
        shortcuts: [
          {
            name: 'Tanya SALMA',
            short_name: 'Chat',
            description: 'Langsung chat dengan SALMA AI',
            url: '/?open=chat',
            icons: [{ src: '/icons/icon-96x96.png', sizes: '96x96' }],
          },
        ],
      },
    }),
  ],
  build: {
    rollupOptions: {
      onwarn(warning, warn) {
        if (warning.code === 'UNUSED_EXTERNAL_IMPORT') return;
        warn(warning);
      },
    },
  },
});
