# 📋 Changelog: Polishing Homepage Slider, Maps Widget, and Footer

**Date**: 2026-08-16 09:08 WIB  
**File Modified**: `resources/js/Pages/Welcome.vue`  
**Status**: ✅ All 5 User Feedback Items Completed & Verified

---

## Executive Summary

Based on user review and design feedback, the `Welcome.vue` homepage has been refined with 5 key improvements:
1. **Hero Slider Overhaul**: Hero text headline, subtitle, and CTA buttons are now permanently fixed/static in the foreground while only background images cycle with smooth crossfade transitions.
2. **Layanan Unggulan Cards Polish**:
   - Replaced "Cek PKB Online" with **"Mutasi Masuk / Keluar"**.
   - Removed "Selengkapnya ->" links across all 6 cards.
   - Removed direct redirect to AI Chat on card click, turning them into clean informational presentation cards.
3. **Statistics Section Removal**: Completely removed the counter section (350+ Layanan, 6+ Lokasi, 24/7 AI, 66+ KB Articles) for a cleaner visual hierarchy.
4. **Interactive Google Maps Widget on Schedules**:
   - Added dynamic Google Maps iframe box below each schedule tab (**Samsat Keliling Pagi**, **Payment Point Menetap**, and **BELOK WANGI**).
   - Clicking any location card highlights the card as active (`.loc-card--active`, `.pp-card--active`, `.belok-card--active`) and immediately centers the map view on that specific address/location.
   - Includes a direct "Petunjuk Arah Maps" / "Buka di Google Maps" external link button.
5. **Footer Government Logo Cluster**:
   - Replaced the single squished logo in the footer with a clean 4-logo cluster (Bapenda Jatim, Pemprov Jatim, Polda Jatim / Polri, Jasa Raharja).
   - Encased in individual translucent frosted badges (`.footer-logo-badge`) with crisp borders and hover effects.

---

## Detailed Modifications

### 1. Hero Slider Component
- **Behavior**:
  - `heroBackgrounds` array contains the background photo URLs.
  - Foreground text (`.hero-slider__text`) is no longer inside dynamic `<TransitionGroup>` tied to `activeSlide`.
  - Static Headline: **Layanan Pajak Kendaraan Modern**
  - Static Subtitle: **Bayar pajak kendaraan bermotor dari mana saja, kapan saja melalui berbagai kanal digital dan layanan resmi Samsat Lamongan.**
  - Primary CTA: **Cara Bayar** (smooth scrolls to `#pembayaran`).
  - Secondary CTA: **Hubungi Kami** (smooth scrolls to `#kontak`).
  - Auto-slides every 5 seconds; arrow and dot indicators remain functional.

### 2. Layanan Unggulan Cards
- Updated card list:
  1. **Pajak Tahunan** (`mdi-car-side`) — Pembayaran Pajak Kendaraan Bermotor (PKB) tahunan dengan mudah dan cepat tanpa antri lama.
  2. **STNK 5 Tahunan** (`mdi-card-account-details-outline`) — Perpanjangan masa berlaku STNK dan penggantian plat nomor kendaraan (TNKB) 5 tahunan.
  3. **Balik Nama (BBNKB)** (`mdi-swap-horizontal-bold`) — Proses Bea Balik Nama Kendaraan Bermotor antar pemilik pertama ke pemilik berikutnya.
  4. **Mutasi Masuk / Keluar** (`mdi-file-document-swap-outline`) — Proses administrasi perpindahan berkas kendaraan bermotor antar wilayah kabupaten atau provinsi.
  5. **Samsat Keliling** (`mdi-bus-clock`) — Layanan pembayaran pajak tahunan bergerak yang hadir di berbagai kecamatan di Lamongan.
  6. **BELOK WANGI** (`mdi-weather-night`) — Beda Lokasi Wayah Bengi — Layanan Samsat Keliling Malam setiap pukul 18.00–20.00 WIB.
- Removed links and action triggers; enhanced hover translateY(-4px) and subtle shadow.

### 3. Google Maps Integration across Schedules
- **Reactive State**:
  - `selectedKelilingLoc = ref(0)`
  - `selectedMenetapLoc = ref(0)`
  - `selectedBelokLoc = ref(0)`
- **Dynamic Queries**:
  - `currentKelilingMapQuery`: dynamically resolves active day and location index with `, Lamongan, Jawa Timur` fallback.
  - `currentMenetapMapQuery`: dynamically resolves payment point name and address.
  - `currentBelokMapQuery`: dynamically resolves night schedule location.
- **UI/UX**:
  - Interactive selection on click with visual feedback (badge + active border).
  - Responsive map container (360px desktop, 280px tablet, 240px mobile).
  - Clean night theme styling for BELOK WANGI map header.

### 4. Footer Branding Upgrade
- Logo cluster:
  - Bapenda Jatim (`/images/logo-bapenda-jatim.png`)
  - Pemprov Jawa Timur (`/images/logo-jatim.png`)
  - Polda Jatim (`/images/Lambang_Polda_Jatim.png`)
  - Jasa Raharja (`/images/jasa-raharja.png`)
- Styling:
  - Translucent badge wrapper (`rgba(255,255,255,0.08)`) with 10px radius and soft hover animation.
  - Clear institution titles and copyright notice.

---

## Verification & Build Results

| Step | Verification | Status |
|------|--------------|--------|
| `npm run build` | Vite asset compilation & SSR bundle | ✅ 0 errors (exit code 0) |
| Hero Slider Test | Background rotates every 5s; text and buttons remain static | ✅ Verified |
| Layanan Cards | "Mutasi Masuk / Keluar" present, no "Selengkapnya" links, not clickable to chat | ✅ Verified |
| Stats Counter | Section completely removed from DOM | ✅ Verified |
| Google Maps Widget | Interactive selection on Keliling, Payment Point, and Belok Wangi tabs | ✅ Verified |
| Footer Logo Cluster | 4 official logos display in clean badges with crisp text | ✅ Verified |

---

## Browser Recording Artifact
- `verify_homepage_polish_1786845456824.webp`
