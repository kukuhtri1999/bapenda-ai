# Dokumentasi Perubahan: AI Beta Switcher & Overhaul Tema Merah Crimson Pemerintah

**Tanggal & Waktu:** 16 Agustus 2026, 20:35 WIB  
**Modul/Area Terkait:**
- CMS Admin (`/admin/cms` - Tab SALMA AI)
- Customer Service AI Chat (`/customer-service` & `Chat/Index.vue`)
- Form Identitas Wajib Pajak (`/wajib-pajak` & `WajibPajak/Form.vue`)
- Beranda Publik (`/` & `Welcome.vue`)
- Admin Panel Global (`/admin/*`, `Dashboard.vue`, `UserManagement`, `ChatHistory`, Vuetify & Tailwind Themes)

---

## 1. Ringkasan Eksekutif

Dalam pembaruan ini, seluruh sistem visual dan fungsional telah diselaraskan dengan identitas resmi KB Samsat Lamongan Bapenda Jawa Timur:
1. **CMS SALMA Tab AI Status Switcher (Beta / Stable Mode)**: Menambahkan switcher toggle dinamis di Admin CMS tab SALMA AI untuk mengontrol apakah sistem SALMA berstatus "Versi Beta" atau "Stable/Produksi". Jika berstatus Beta, badge `BETA` otomatis tampil di seluruh antarmuka obrolan, form wajib pajak, dan showcase beranda.
2. **Customer Service AI Chat Color Revamp**: Mengganti seluruh palet ungu (`#6C33A0`, `#9333EA`, `#7C3AED`, dll.) pada halaman `/customer-service` menjadi **Government Crimson Red (`#C0392B` / `#D32F2F`)**.
3. **Global Admin Panel Theme Unification**: Mengubah warna primer admin pada Vuetify theme, Tailwind configuration, Active Nav Links, floating helper buttons, intro tours, dan dashboard cards menjadi Crimson Red & Government Navy (`#1B2838`).

---

## 2. Rincian Perubahan Teknis

### A. Database & CMS Backend
1. **`database/seeders/HomepageContentSeeder.php`**:
   - Menambahkan key konfigurasi CMS baru:
     - `section`: `'salma'`
     - `key`: `'salma_is_beta'`
     - `value`: `'true'` (boolean)
     - `type`: `'boolean'`
     - `label`: `'Status AI Versi Beta'`
     - `description`: `'Tampilkan badge BETA pada fitur AI Chat dan identitas wajib pajak'`
2. **`app/Http/Controllers/Admin/CmsController.php`**:
   - Menambahkan filter parsing boolean pada method `update()` agar nilai `true`/`false` tersimpan dan diperbarui secara tepat di database.
3. **`routes/web.php` & `app/Http/Controllers/WajibPajakController.php`**:
   - Melewatkan data `cms` (`HomepageContent::getAllGrouped()`) ke route `/customer-service` dan `/wajib-pajak` sehingga status `salma_is_beta` dapat diakses secara instan oleh komponen Vue.

---

### B. Admin CMS SALMA Tab (`resources/js/Pages/Admin/Cms/Index.vue`)
- Menambahkan card **Status Versi AI (Beta Mode)** dengan gaya iOS/Vuexy switch:
  - Indikator badge dinamis: `BETA AKTIF` (merah) vs `STABLE / PRODUKSI` (hijau emerald).
  - Penjelasan status untuk mempermudah administrator non-teknis.
  - Terintegrasi dengan penyimpanan batch CMS dan invalidasi cache otomatis.

---

### C. Antarmuka Customer Service AI Chat (`resources/js/Pages/Chat/Index.vue`)
- **App Bar Header**:
  - Background: `linear-gradient(135deg, #C0392B 0%, #D32F2F 50%, #B03022 100%)`.
  - Box Shadow: `0 4px 16px rgba(192, 57, 43, 0.35)`.
  - Menampilkan badge pill `BETA` di samping judul `SALMA AI — Asisten Samsat Lamongan`.
- **Chat Bubbles & Actions**:
  - User Message Bubbles: `linear-gradient(135deg, #C0392B, #D32F2F)`.
  - Tombol Mulai Chat & Kirim Pesan: `linear-gradient(135deg, #C0392B, #D32F2F)` dengan bayangan merah serasi.
  - Indikator titik animasi sedang mengetik: `#C0392B`.
  - Scrollbar thumb: gradient merah `#C0392B` ke `#D32F2F`.
  - Markdown links pada respons asisten: `#C0392B` dengan font-weight 600.
  - Tombol inline "⛔ Akhiri Chat": border & text `#C0392B` dengan background `#fef2f2`.
- **Dialog Evaluasi / Timeout Otomatis (Feedback Popup)**:
  - Header popup: `linear-gradient(135deg, #C0392B 0%, #D32F2F 100%)`.
  - Label penilaian kepuasan dinamis berwarna merah `#C0392B`.
  - Tombol submit "Kirim Feedback": gradient merah `#C0392B`.

---

### D. Form Wajib Pajak & Beranda Publik
1. **`resources/js/Pages/WajibPajak/Form.vue`**:
   - Menambahkan badge `BETA` pada header card judul `Layanan Chat SALMA AI`.
   - Tombol submit `Mulai Chat AI` dan ikon badge bernuansa Crimson Red `#C0392B`.
2. **`resources/js/Pages/Welcome.vue`**:
   - Menambahkan badge `BETA` pada section SALMA AI Showcase jika `salma_is_beta` bernilai true.

---

### E. Penyelarasan Tema Global Admin Panel
1. **`tailwind.config.js`**:
   - `primary`: `#C0392B`
   - `secondary`: `#1B2838`
   - `greenlight`: `#FEF2F2`
2. **`resources/js/app.js` (Vuetify Theme)**:
   - `primary`: `#C0392B`
   - `secondary`: `#1B2838`
   - `accent`: `#D32F2F`
3. **`resources/js/Components/NavLink.vue`**:
   - Active state border & highlight: `border-primary` (`#C0392B`), `bg-red-50`, `text-red-700`.
4. **`resources/js/Components/TourButton.vue` & `resources/css/app.css`**:
   - Floating Action Button tour: `linear-gradient(135deg, #C0392B 0%, #D32F2F 100%)`.
   - Intro.js progress bar & action button: gradient `#C0392B`.
5. **`resources/js/Components/PwaInstallButton.vue` & `resources/views/app.blade.php`**:
   - PWA meta `theme-color`: `#C0392B`.
   - PWA install CTA button: gradient `#C0392B` ke `#D32F2F`.
6. **`resources/js/Pages/Dashboard.vue`**:
   - Hero banner: gradient Navy `#1B2838` ke Crimson Red `#C0392B`.
   - Metrik dan Quick Actions menggunakan aksen Crimson Red.
7. **`resources/js/Pages/UserManagement/Index.vue` & `Admin/ChatHistory/Index.vue`**:
   - Seluruh tombol utama, tag role, focus ring input, dan dialog detail telah diselaraskan ke tema merah `#C0392B`.

---

## 3. Hasil Pengujian & Verifikasi

- [x] **Vite Compilation (`npm run build`)**: Lulus tanpa error.
- [x] **Identitas Wajib Pajak (`/wajib-pajak`)**: Form input nama, nopol, nomor WA, badge BETA, dan navigasi berhasil diverifikasi.
- [x] **Customer Service AI Chat (`/customer-service`)**: App bar merah, user bubbles merah, respons AI akurat, dan tombol aksi berfungsi normal.
- [x] **CMS SALMA AI Tab (`/admin/cms`)**: Switcher Status Versi AI (Beta Mode) responsif dan terhubung dengan database.
- [x] **Admin Dashboard (`/admin/` & `/dashboard`)**: Banner selamat datang Navy-Merah dan navigasi sidebar aktif dengan aksen merah presisi.
