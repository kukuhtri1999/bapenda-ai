# Comprehensive Documentation: Replacing SALMA Mascot to Static PNG & Enhancing Homepage AI Showcase Section

**Date & Time:** 2026-11-09 14:07:00 +07:00  
**Author:** AI Engineering & Frontend UI/UX Team  
**Target File:** `documentations/2026_11_09_14_07_changes_replacing-salma-mascot-and-enhancing-homepage-ai-section.md`  
**Scope:** Website-wide Asset Migration (`salma_new.png`), Homepage Feature Showcase Section Revamp (`Woman_explaining_documents_to_man_7d7037d4.jpg`), Label Removal, Dark Slate Navy & Glowing Crimson Background Design, CMS Database Seeder & Admin Interface Synchronisation, Verification & Build Compilation.

---

## 1. Executive Summary

The user requested two major visual and architectural improvements:
1. **Website-Wide Mascot Migration from GIF to Static PNG:** Replace all instances of the animated mascot (`salma2.gif`) with the new official mascot asset (`public/images/salma_new.png`). Completely decommission GIF/animated avatars in favor of crisp, modern, static PNG rendering.
2. **Homepage AI Showcase Section Enhancement:**
   - Replace the cartoon mascot on the right side of Section 4 (`#salma`) with the real customer service photograph (`public/images/Woman_explaining_documents_to_man_7d7037d4.jpg`).
   - Remove/hide the pill label `'Online — Siap Melayani'` beneath the image, showing exclusively the photograph on the right column.
   - Upgrade the background of this section to make it significantly more prominent, high-impact (*"mencolok"*), and visually commanding on the homepage to highlight the flagship SALMA AI Chat feature.

---

## 2. Detailed Technical Changes

### A. Website-Wide Asset Migration (`salma_new.png`)

1. **Chat Application (`resources/js/Pages/Chat/Index.vue`):**
   - **Header Bar Mascot (Line 30):** Updated `src` from `/images/salma2.gif` to `/images/salma_new.png`.
   - **Welcome Hero Mascot (Line 98):** Replaced legacy GIF with `/images/salma_new.png`. Updated styling in `.salma-mascot-img` to `object-fit: contain; padding: 4px;` for crisp, unclipped rendering.
   - **Assistant Message Bubbles (Line 193):** Updated assistant chat bubble avatar icon from `/images/salma2.gif` to `/images/salma_new.png`.
   - **Typing Indicator Bubble (Line 215):** Updated typing indicator mascot avatar to `/images/salma_new.png`.
   
2. **Legacy Path Safeguards (`public/images/`):**
   - Mirrored `salma_new.png` into `public/images/salma.png` and overwritten `public/images/salma2.gif` with the identical static PNG payload.
   - Guarantees that any cached browser links, external bookmarks, or legacy references will load the static non-animated PNG without delay or rendering artifacts.

---

### B. Homepage AI Showcase Section Revamp (`resources/js/Pages/Welcome.vue`)

1. **Showcase Image Replacement & Label Elimination:**
   - Substituted `:src="salmaMascotImage"` target to `/images/Woman_explaining_documents_to_man_7d7037d4.jpg`.
   - Enhanced `salmaMascotImage` computed property to automatically map any legacy `/images/salma2.gif` database entry or null value to `/images/Woman_explaining_documents_to_man_7d7037d4.jpg`.
   - Completely deleted the `.salma-visual__label` element containing `'Online — Siap Melayani'`, leaving only the clean photographic showcase image on the right column.

2. **High-Impact Background & UI/UX Elevation ("Mencolok"):**
   - **Modern Deep Slate Navy Background:**
     ```css
     .section-salma {
       padding: 68px 0;
       position: relative;
       overflow: hidden;
       background: linear-gradient(135deg, #0F172A 0%, #1A1215 45%, #111827 100%);
       border-top: 1px solid rgba(192, 57, 43, 0.25);
       border-bottom: 1px solid rgba(192, 57, 43, 0.25);
       box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
     }
     ```
   - **Dual Radial Ambient Glows:**
     - Left ambient orb (`.salma-ambient-glow-left`): `rgba(192, 57, 43, 0.28)` gradient beam for warm depth.
     - Right ambient orb (`.salma-ambient-glow-right`): `rgba(231, 76, 60, 0.22)` gradient beam framing the showcase image.
   - **Elevated Typography & Badges:**
     - Title (`.salma-title`): High-contrast `#FFFFFF` typography with `font-weight: 800; font-size: 2.6rem; letter-spacing: -0.02em;`.
     - Subtitle (`.salma-full-name`): Vibrant coral red `#FF6B6B`.
     - Description (`.salma-desc`): Soft slate `#CBD5E1` with relaxed `1.7` line-height for effortless readability.
     - Badge (`.salma-badge`): Frosted ruby glass with `background: rgba(192, 57, 43, 0.25); border: 1px solid rgba(255, 138, 128, 0.35); color: #FF8A80;`.
   - **Frosted Glass Feature Cards (`.salma-feature`):**
     - Frosted translucent tiles with `background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: #F1F5F9; backdrop-filter: blur(8px);`.
     - Interactive hover elevation: `background: rgba(192, 57, 43, 0.22); border-color: rgba(255, 107, 107, 0.45); transform: translateY(-2px);`.
   - **Glowing CTA Action Button (`.salma-cta-btn`):**
     - Radiant gradient `linear-gradient(135deg, #C0392B 0%, #E74C3C 100%)` with dramatic glow shadow `box-shadow: 0 10px 25px -4px rgba(192, 57, 43, 0.6)`.
   - **Photographic Showcase Framing (`.salma-showcase-img`):**
     - Rounded corners `border-radius: 20px`, deep elevation `box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.65)`, and an ultra-fine border ring `0 0 0 1px rgba(255, 255, 255, 0.16)`.
     - Micro-interaction: subtle scale up and enhanced glow on hover.

3. **Responsive Breakpoints:**
   - Desktop: Maximum photo width 520px, height 380px, 2-column feature grid.
   - Tablet (<=960px): Title scales to `2rem`, features adjust to single column, photo height capped at 320px.
   - Mobile (<=600px): Section padding adjusted to `42px 0`, photo height capped at `250px` with `14px` border radius for smooth mobile layout.

---

### C. Database & Admin CMS Synchronisation

1. **Database Update (`homepage_contents` Table):**
   - Updated key `salma_mascot_image` to `/images/Woman_explaining_documents_to_man_7d7037d4.jpg`.
   - Flushed cache key `homepage_contents_grouped` to ensure immediate propagation.
2. **Database Seeder (`database/seeders/HomepageContentSeeder.php`):**
   - Updated default value of `salma_mascot_image` to `/images/Woman_explaining_documents_to_man_7d7037d4.jpg`.
   - Updated label to *"Gambar Showcase SALMA AI Beranda"*.
3. **Admin CMS Component (`resources/js/Pages/Admin/Cms/Index.vue`):**
   - Updated section label to *"Foto Showcase Fitur SALMA AI Beranda"*.
   - Updated format guidance text to *"Format: JPG, PNG, atau WebP"*.
   - Updated preview container styling to `w-24 h-24 object-cover rounded-lg`.

---

## 3. Verification & Testing

1. **PHP Syntax Validation:**
   - Ran `php -l database/seeders/HomepageContentSeeder.php` $\rightarrow$ No syntax errors.
2. **Automated Unit Testing:**
   - Ran `php artisan test --filter=MultiTurnConversationalTest` $\rightarrow$ **5 passed (13 assertions)**, duration 22.65s, exit code 0.
3. **Frontend Production Compilation:**
   - Ran `npm run build` (both Client bundle and SSR bundle) $\rightarrow$ **Exit code 0, 0 errors**.
4. **Cache & Persistence:**
   - Cleared and verified `homepage_contents_grouped` in Laravel cache.

---

## 4. Summary of Files Changed

| File Path | Description of Changes |
|---|---|
| `public/images/salma.png` | Created static PNG copy of `salma_new.png`. |
| `public/images/salma2.gif` | Overwritten with static PNG payload of `salma_new.png` (decommissioning animated GIF). |
| `resources/js/Pages/Chat/Index.vue` | Replaced all 4 avatar/mascot references with `/images/salma_new.png`, optimized `object-fit: contain`. |
| `resources/js/Pages/Welcome.vue` | Replaced showcase image with `Woman_explaining_documents_to_man_7d7037d4.jpg`, deleted `'Online — Siap Melayani'`, transformed background to Dark Slate Navy & Crimson Glow. |
| `resources/js/Pages/Admin/Cms/Index.vue` | Updated admin CMS showcase preview, label, and format descriptions. |
| `database/seeders/HomepageContentSeeder.php` | Set default value for `salma_mascot_image` to the new showcase photograph. |
| `documentations/2026_11_09_14_07_changes_replacing-salma-mascot-and-enhancing-homepage-ai-section.md` | Created comprehensive documentation. |

