# UI/UX Compactness Overhaul — All Pages 10-15% Size Reduction

**Date:** 2026-08-24
**Type:** Frontend UI/UX Improvement
**Scope:** Welcome.vue, FloatingChat.vue, PwaInstallButton.vue, WajibPajak/Form.vue

---

## 1. Overview

This change applies a systematic 10-15% size reduction across all major UI elements in the homepage, Wajib Pajak registration form, and AI Chat floating widget.

### Key Objectives:
- Reduce section paddings, typography sizes, and element dimensions by ~12%
- Simplify "Pasang Aplikasi" button to icon-only on mobile (<=640px)
- Reduce floating AI chat widget dimensions
- Fix lingering purple color remnants in FloatingChat animations/scrollbar
- Update all responsive breakpoints consistently

---

## 2. Files Modified

### 2.1 PwaInstallButton.vue

#### Changes:
| Element | Before | After |
|---------|--------|-------|
| .pwa-pill on mobile (<=640px) | shows icon + text + badge | icon only |
| .pwa-pill-text on mobile | visible | display:none |
| .pwa-pill-badge on mobile | visible | display:none |
| .pwa-pill padding on mobile | 8px 14px 8px 8px | 8px |
| Pulse animation color | purple rgba(108,51,160) | red rgba(192,57,43) |

---

### 2.2 FloatingChat.vue

#### Changes:
| Element | Before | After |
|---------|--------|-------|
| Chat button size | 64x64px | 56x56px |
| Chat button icon size | 32 | 26 |
| Widget width | 400px | 360px |
| Widget height | 600px | 540px |
| Messages container height | 460px | 405px |
| Minimized widget size | 80x70px | 70x62px |
| Pulse/hover/gradient colors | purple | red #C0392B |

---

### 2.3 WajibPajak/Form.vue

#### Changes:
| Element | Before | After |
|---------|--------|-------|
| .wp-topbar padding | 14px 20px | 10px 20px |
| .wp-content-wrap padding | 32px 16px | 24px 16px |
| .wp-card-container max-width | 480px | 460px |
| .wp-card padding | 32px 28px | 26px 22px |
| .wp-card border-radius | 24px | 22px |
| .wp-card__header margin-bottom | 24px | 20px |
| .wp-card__avatar size | 58x58px | 50x50px |
| .wp-card__title font-size | 1.45rem | 1.28rem |
| .wp-btn-primary padding | 13px 20px | 11px 18px |
| .wp-btn-primary font-size | .95rem | .9rem |
| Mobile card padding | 24px 20px | 20px 16px |
| Mobile title font-size | 1.25rem | 1.1rem |

---

### 2.4 Welcome.vue (Homepage)

#### Navbar:
- .gov-navbar__inner padding: 16px 20px -> 12px 20px

#### Hero Section:
- min-height: 92vh -> 80vh
- Hero row min-height: 85vh -> 74vh
- padding-top: 100px -> 88px
- Title font-size: 3.2rem -> 2.8rem
- Subtitle font-size: 1.15rem -> 1.05rem
- Diagonal clip height: 80px -> 70px

#### Section Headers (global):
- margin-bottom: 48px -> 40px
- badge font-size: .78rem -> .75rem
- title font-size: 2.2rem -> 1.95rem
- desc font-size: 1rem -> .95rem

#### Section Paddings — all changed: 80px 0 -> 68px 0
(Pemutihan, Layanan, Jadwal, Salma, Payment, Kontak — except Lampion: 70px->60px)

#### Service Cards:
- padding: 28px 24px -> 22px 20px
- icon size: 56x56px -> 48x48px
- title font-size: 1.1rem -> 1rem

#### SALMA AI:
- salma-title: 3rem -> 2.65rem
- salma-mascot width: 280px -> 245px
- salma-glow size: 320px -> 280px

#### Payment:
- pay-logo__img height: 80px -> 70px

#### Footer:
- gov-footer padding: 56px 0 24px -> 46px 0 20px

#### Responsive Breakpoints Updated:
- <=960px: hero 80vh->70vh, title 2.2->2rem, section headers 1.8->1.65rem
- <=600px: hero 75vh->65vh, title 1.8->1.6rem, section padding 48->42px, section header margin 32->26px

---

## 3. Deployment

No migrations required. Frontend only.

```bash
npm run build
php artisan optimize:clear
php artisan optimize
```
