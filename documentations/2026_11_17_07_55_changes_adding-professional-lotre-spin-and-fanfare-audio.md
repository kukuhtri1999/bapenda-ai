# Dokumentasi Perubahan: Penambahan Efek Suara Lotre Profesional (Spinning & Fanfare)

**Tanggal & Waktu:** 17 November 2026, 07:55 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `resources/js/composables/useLotreAudio.js` (Baru)
- `resources/js/Pages/Lotre/Index.vue` (Diperbarui)

---

## 1. Latar Belakang & Tujuan
Sebelumnya, halaman undian `/lotre-undian` (`resources/js/Pages/Lotre/Index.vue`) tidak memiliki efek suara saat mesin undian berputar dan hanya menggunakan satu nada *beep* sederhana berdurasi 0,2 detik saat pemenang muncul.

User menginginkan pengalaman audio selayaknya undian profesional yang meriah:
1. **Saat Undian Berputar**: Suara mekanis roda/mesin undian yang berderap (`drtrdrtdrtdrtdtrdtrdtrttrtdrt`) dan melambat secara alami mengikuti kecepatan animasi hingga berhenti.
2. **Saat Undian Berhenti (Pemenang Terpilih)**: Suara kemenangan yang megah, heboh, dan meriah (*grand fanfare + bells + applause*).

---

## 2. Arsitektur & Rincian Teknis

### A. Web Audio API Sound Engine (`resources/js/composables/useLotreAudio.js`)
Dibuat tanpa ketergantungan file MP3 eksternal (0ms latency, aman offline, 100% SSR-safe) menggunakan **Web Audio API**:
1. **Master Dynamics Compressor**:
   - Mencegah distorsi/clipping saat banyak layer suara (fanfare + lonceng + tepuk tangan) dimainkan bersamaan.
2. **`playWheelTick(volumeScale)` — Suara Putaran (`drtrdrtdrtdrt...`)**:
   - Kombinasi oscillator *triangle* (pitch drop cepat `980Hz → 210Hz` dalam 22ms) dan *bandpass-filtered white noise* (`2400Hz`) untuk menghasilkan suara ketukan *peg* roda undian yang renyah.
   - Dilengkapi *pitch jitter* (±7%) agar suara ketukan terdengar organik seperti roda fisik asli.
3. **`playWinnerFanfare()` — Suara Kemenangan Meriah**:
   - **Layer 1 (Impact Drum / Timpani)**: Hentakan bass rendah (`150Hz → 46Hz`) di awal dan saat puncak chord.
   - **Layer 2 (Brass/Synth Fanfare Melody)**: Arpeggio naik `C5 → E5 → G5 → C6 → G5` diikuti *Grand Sustained Major Chord* (`C5 + E5 + G5 + C6`) menggunakan oscillator *detuned sawtooth + triangle* melalui filter low-pass.
   - **Layer 3 (Jackpot Bell Cascade)**: Deretan denting lonceng kristal nada tinggi (`C6, E6, G6, C7, E7`) beruntun yang memberi kesan berkilau/jackpot.
   - **Layer 4 (Crowd Applause & Celebration Swell)**: Filtered pink/white noise dengan modulasi LFO yang mensimulasikan riuh tepuk tangan dan sorak meriah selama ±2,3 detik.
4. **Kontrol Mute & Autoplay Compliance**:
   - `ensureAudioContext()` otomatis melakukan inisialisasi/resume `AudioContext` tepat saat user mengklik tombol `PUTAR!`.
   - State `isMuted` disimpan persisten di `localStorage` (`lotre_sound_muted`).

### B. Sinkronisasi Frame-Accurate (`resources/js/Pages/Lotre/Index.vue`)
- Menambahkan `startSpinAudioTracker()` menggunakan `requestAnimationFrame`:
  - Membaca posisi aktual `translateY` elemen gulungan secara real-time melalui `window.getComputedStyle` & `DOMMatrixReadOnly`.
  - Setiap kali baris peserta melewati batas ketinggian item (`ITEM_H = 48px`), `playWheelTick()` dipicu (dengan batas minimal jeda `32ms` agar putaran super cepat awal tetap renyah dan jelas, lalu melambat menjadi `tik... tik... tak!` seiring perlambatan kurva `cubic-bezier(.2,.9,.3,1)`).
- Mengganti `playWinnerSound()` lama dengan `playWinnerFanfare()` saat pemenang diumumkan.
- Menambahkan tombol toggle **🔊 Suara Aktif / 🔇 Suara Mati** di samping tombol Reset.
- Membersihkan animasi/tracker suara dengan aman pada `resetAll()` dan `onUnmounted()`.
