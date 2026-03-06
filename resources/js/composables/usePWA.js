/**
 * usePWA — Progressive Web App install & update composable
 *
 * Handles:
 *  - beforeinstallprompt: deferred install prompt for Android/Desktop Chrome
 *  - appinstalled: track post-install state
 *  - SW update detection via workbox-window
 *  - iOS detection (Safari does not fire beforeinstallprompt)
 *  - Standalone mode detection (already installed)
 */

import {
  ref, computed, onMounted, onUnmounted,
} from 'vue';

// Singleton state — shared across all component instances
const deferredPrompt = ref(null);
const isInstalled = ref(false);
const isIOS = ref(false);
const isIOSSafari = ref(false);
const showIOSGuide = ref(false);
const updateAvailable = ref(false);

let _registered = false;

function _detectEnvironment() {
  // Already running as installed PWA (standalone / minimal-ui / fullscreen)
  if (
    window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true
  ) {
    isInstalled.value = true;
  }

  // iOS detection
  const ua = window.navigator.userAgent.toLowerCase();
  isIOS.value = /iphone|ipad|ipod/.test(ua);
  isIOSSafari.value = isIOS.value
    && /safari/.test(ua)
    && !/crios/.test(ua) // not Chrome on iOS
    && !/fxios/.test(ua); // not Firefox on iOS
}

function _registerListeners() {
  if (_registered) return;
  _registered = true;

  // Android / Desktop Chrome: capture deferred prompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
  });

  // Track successful install
  window.addEventListener('appinstalled', () => {
    deferredPrompt.value = null;
    isInstalled.value = true;
    showIOSGuide.value = false;
  });

  // Track display-mode changes (e.g. user adds from browser menu)
  window
    .matchMedia('(display-mode: standalone)')
    .addEventListener('change', (e) => {
      if (e.matches) isInstalled.value = true;
    });
}

export function usePWA() {
  onMounted(() => {
    _detectEnvironment();
    _registerListeners();
  });

  /**
   * Whether showing the install UI makes sense:
   *  - Not already installed
   *  - Either: native prompt available, OR iOS Safari (manual guide flow)
   */
  const canInstall = computed(() => {
    if (isInstalled.value) return false;
    return !!deferredPrompt.value || isIOSSafari.value;
  });

  /**
   * Trigger the native install prompt (Android/Desktop).
   * For iOS, toggle the manual guide instead.
   * Returns: 'accepted' | 'dismissed' | 'ios-guide' | 'unavailable'
   */
  async function promptInstall() {
    if (isInstalled.value) return 'unavailable';

    if (deferredPrompt.value) {
      deferredPrompt.value.prompt();
      const { outcome } = await deferredPrompt.value.userChoice;
      deferredPrompt.value = null;
      if (outcome === 'accepted') isInstalled.value = true;
      return outcome; // 'accepted' | 'dismissed'
    }

    if (isIOSSafari.value) {
      showIOSGuide.value = !showIOSGuide.value;
      return 'ios-guide';
    }

    return 'unavailable';
  }

  function dismissIOSGuide() {
    showIOSGuide.value = false;
  }

  return {
    canInstall,
    isInstalled,
    isIOS,
    isIOSSafari,
    showIOSGuide,
    updateAvailable,
    promptInstall,
    dismissIOSGuide,
  };
}
