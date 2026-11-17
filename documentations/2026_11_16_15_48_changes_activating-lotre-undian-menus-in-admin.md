# Dokumentasi Perubahan: Pengaktifan Menu Lotre Undian di Admin Sidebar

**Tanggal & Waktu:** 16 November 2026, 15:48 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `resources/js/Layouts/AppLayout.vue`

---

## 1. Latar Belakang & Masalah
Sebelumnya, fitur Lotre Undian (terdiri dari pengelolaan data peserta via Excel, pemilihan/pengundian hadiah dengan mode acak murni, custom/pemenang tertentu, atau per kecamatan, serta visual pengundian interaktif) telah diimplementasikan secara komprehensif pada backend, database, dan frontend. Namun, menu navigasi ke halaman:
1. **Lotre - Pengaturan** (`route('admin.lotre.settings')`)
2. **Lotre - Import Data** (`route('admin.lotre.import')`)

Sempat di-comment out (dinonaktifkan sementara) pada navigasi drawer admin di [resources/js/Layouts/AppLayout.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Layouts/AppLayout.vue#L87-L98), sehingga user admin tidak dapat melihat maupun mengakses menu tersebut secara langsung dari sidebar.

---

## 2. Rincian Perubahan
Pada file `resources/js/Layouts/AppLayout.vue`:
Uncomment array item navigasi `navLinks`:
```javascript
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
```

---

## 3. Verifikasi & Fitur yang Tersedia
Dengan diaktifkannya kembali menu navigasi tersebut, admin dapat mengakses:
- **Lotre - Pengaturan (`/admin/lotre/settings`)**:
  - Konfigurasi mode pengundian (Acak Murni, Pemenang Tertentu / Settingan, dan Satu Pemenang Per Kecamatan).
  - Pengaturan jumlah kuota pemenang dan batas maksimal pemenang per kecamatan.
  - Pencarian NOP/Nama wajib pajak untuk penetapan pemenang khusus.
  - Reset daftar pemenang yang ditentukan sebelumnya.
- **Lotre - Import Data (`/admin/lotre/import`)**:
  - Download template Excel peserta lotre.
  - Upload & preview file spreadsheet data peserta.
  - Sinkronisasi data ke tabel `peserta_lotre`.
  - Tombol kosongkan/reset data peserta.
- **Halaman Undian Publik/Live Display (`/lotre-undian`)**:
  - Layar animasi undian doorprize interaktif.
