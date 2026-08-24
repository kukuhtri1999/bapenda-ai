# 2026-08-24 11:00 — SALMA AI Chat UI/UX: Floating Fixed Bottom Bar & Artifact Fixes

## 1. Overview
Overhauled the SALMA AI Chat page (`resources/js/Pages/Chat/Index.vue`) to deliver a modern, high-grade chat interface matching industry standard conversational UX (like Claude / ChatGPT / WhatsApp Web). Addressed the visual outline glitch in the chat input, made the input bar floating and always fixed at the bottom across all screen sizes, and resolved overlap issues with the interactive guide Tour button.

---

## 2. Changes Implemented

### A. Chat Input Artifact Fix (`resources/js/Pages/Chat/Index.vue`)
- **Problem:** When `VTextarea` used `variant="outlined"` alongside custom `.v-field` borders, Vuetify 3's internal `.v-field__outline__end` rendered an inner curved arc `)` on the right side of the textarea.
- **Fix:** 
  - Changed `VTextarea` to `variant="plain"` and contained it within a sleek, custom `.chat-input-pill` card with `border-radius: 26px`, soft border (`#CBD5E1`), and focus ring (`#C0392B`).
  - Completely hid `.v-field__outline` (`display: none !important;`) and stripped native browser/webkit scrollbar thumbs (`scrollbar-width: none; -ms-overflow-style: none; textarea::-webkit-scrollbar { display: none; }`).
  - Added smooth auto-grow support (up to 4 rows) without any clipping or layout shift.

### B. Always-Fixed Floating Bottom Bar Layout
- **Fixed Floating Positioning:**
  - Implemented `.chat-floating-bar-wrap` with `position: fixed; bottom: 0; left: 0; right: 0; z-index: 35; pointer-events: none;` and centered inner container `.chat-floating-bar-inner` (`pointer-events: auto; max-width: 920px; backdrop-filter: blur(12px); background: linear-gradient(180deg, rgba(248,250,252,0) 0%, rgba(255,255,255,0.94) 28%, #FFFFFF 100%);`).
  - Ensures the input bar remains comfortably fixed and floating at the bottom on all devices (mobile, tablet, desktop).
- **Messages Scroll Padding:**
  - Configured `.chat-scroll-area` with `padding-bottom: 115px` on desktop and `110px` on mobile so messages, typing indicators, timestamps, and quick suggestions are never obscured behind the floating bar.
- **Dynamic Send Button:**
  - Replaced bulky button with a modern 40×40px circular action button with government red gradient (`#C0392B` → `#D32F2F`) on input, scale micro-interaction (`hover: scale(1.06)`, `active: scale(0.96)`), and loading spinner.

### C. TourButton Placement Conflict Resolution
- **Problem:** `TourButton.vue` (floating `?` button) was fixed at `bottom: 20px; right: 14px;`, which directly collided with the Send button on mobile and tablet.
- **Fix:**
  - Wrapped `TourButton` in `.chat-tour-anchor` with responsive positioning: `bottom: 84px; right: 20px; z-index: 45;` on desktop and `bottom: 78px; right: 12px;` on mobile.
  - Added a dedicated Help icon in `VAppBar` (`icon="mdi-help-circle-outline"`) to allow initiating the tour directly from the header toolbar.

### D. Compact Header & Typography Polish
- **Full-Width Header Container:** Removed competing `VSpacer` element, allowing `.chat-header-content` to occupy `flex: 1 1 auto; min-width: 0;` across the entire space between the back button and the action buttons.
- **Single-Line Brand Layout:** Added `white-space: nowrap;` and `flex-shrink: 0;` to `chat-title-brand` and `chat-beta-badge` to prevent **"SALMA AI"** and **"BETA"** from ever breaking into multiple lines on mobile screens.
- **Compact Header (`density="compact"`):** Configured `VAppBar` height to a slim, modern 48px with sleek padding.
- **Decreased SALMA Font & Layout:** Tuned `chat-title-brand` font size to a clean `0.92rem` (14.7px) with `font-weight: 700`.
- **Compact Badges & Action Buttons:**
  - Micro BETA badge (`8px` font size, `8px` border radius).
  - Compact circular header action buttons (`32×32px`) for Back, Help (Tour), and New Chat with `ms-auto` right alignment.
  - Subtitle with clean online status dot (`● Online`) or user info (`Nama (Nopol)`) with smooth `text-truncate`.
- **Avatar & Bubble Styling:** Standardized assistant avatar to a crisp 32×32px circular mascot gif, and improved bubble typography, spacing, and image preview styling.

---

## 3. Verification & Build Status
- Ran `npm run build` with Vite SSR bundling:
  - Client build: **✓ built in 10.84s**
  - SSR build: **✓ built in 3.99s**
  - Exit code: **0** (no compilation or syntax errors).
