# Homepage Spacing Compactness & Payment Client Logo Grid

**Date:** 2026-08-24
**Type:** Frontend UI/UX Redesign & Optimization
**Scope:** `resources/js/Pages/Welcome.vue`

---

## 1. Overview

This update delivers two key UI/UX enhancements requested by the team:
1. **Pembayaran Digital (Payment Section) Overhaul**: Replaced the category tabs with a unified, high-end "Client / Partner Logo Grid" (max 6 logos per row, without text labels, without tabs), showcasing all 12 digital channels cleanly.
2. **Homepage Spacing & Typography Compactness Overhaul**: Comprehensive reduction in vertical section paddings, hero height, header margins, card paddings, and responsive breakpoints for a tighter, more executive government portal experience.

---

## 2. Details of Changes

### 2.1 Pembayaran Digital Section (Client / Partner Logo Grid)

- **Computed Property**: Added `allPaymentPlatforms` computed in script to flatten categories into a single list of 12 partner platforms (Tokopedia, Shopee, Alfamart, Indomaret, GoPay, LinkAja, iSaku, QRIS, Bank Jatim, Bukopin, BTN, Pos Indonesia).
- **Template Redesign**: Removed `VTabs`, `VWindow`, `VWindowItem`, and text labels below the logos.
- **Grid Layout**: Max 6 logos per row on desktop (`grid-template-columns: repeat(6, 1fr)`).
- **Logo Card Tiles**:
  - Clean tiles with subtle border (`1px solid rgba(0,0,0,0.08)`), rounded corners (`12px`), soft shadow.
  - Handles brand backgrounds seamlessly (`pl.bg || '#FFFFFF'`).
  - Smooth hover elevation and border accentuation (`rgba(192, 57, 43, 0.3)`).
- **Responsive Behavior**:
  - `>1024px`: 6 columns per row (2 rows of 6).
  - `=1024px`: 4 columns per row.
  - `=640px`: 3 columns per row (compact tile height 48px).
  - `=380px`: 2 columns per row.

---

### 2.2 Global Homepage Spacing & Compactness

- **Hero Slider**:
  - Desktop `min-height`: `72vh` (from 80vh/92vh), inner row `68vh`.
  - Content `padding-top`: `76px`.
  - Hero title: `2.35rem`, subtitle: `0.92rem`.
  - Clip height: `50px`.
  - Slider arrows: `42×42px`, dots: `10px`.
- **Global Section Paddings**:
  - Desktop: `52px 0` (was 68px/80px).
  - Tablet (=960px): `38px 0`.
  - Mobile (=600px): `30px 0`.
- **Section Headers**:
  - Margin bottom: `28px` (desktop), `18px` (mobile).
  - Badge: `padding: 4px 12px`, `font-size: 0.72rem`.
  - Title: `1.8rem` (desktop), `1.25rem` (mobile).
  - Desc: `font-size: 0.88rem`, `max-width: 540px`, `margin: 8px auto 0`.
- **Pemutihan (Tax Amnesty) Section**:
  - Gallery cards: `gap: 14px`, hint badge `font-size: 0.72rem`.
  - Consultation bar: `padding: 14px 20px`, `border-radius: 14px`.
- **Layanan Unggulan (Services) Section**:
  - Service cards: `padding: 18px 16px`, icon size `44×44px`, title `0.95rem`, desc `0.82rem`.
- **Jadwal & Lokasi (Schedules) Section**:
  - Header: `padding: 16px 20px`.
  - Location cards: `padding: 11px 13px`, text `0.82rem`.
  - Maps widget frame: `height: 300px` (desktop), `190px` (mobile).
- **SALMA AI Section**:
  - Title: `2.35rem`, subtitle `0.95rem`, desc `0.9rem`.
  - Mascot width: `205px` (desktop), `150px` (mobile).
  - Features: `padding: 8px 12px`, `font-size: 0.82rem`.
- **LAMPION Online Section**:
  - Card direct: `padding: 28px 30px` (desktop), `22px 18px` (mobile).
  - Title: `1.8rem`, subtitle `0.8rem`.
- **Footer**:
  - Padding: `38px 0 16px`.
  - Logo badges: `38×38px`.

---

## 3. Production Deployment Commands

No database migrations or seeders are needed.

```bash
npm run build
php artisan optimize:clear
php artisan optimize
```
