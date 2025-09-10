<?php

return [
  // Predefined high-level categories for chat analytics classification
  // Keep keys short and machine-friendly, labels can be mapped in UI if needed
  'categories' => [
    'tanya_pelayanan_pajak',
    'tanya_samsat_keliling',
    'samsat_keliling_malam',
    'tanya_cara_bayar_pajak',
    'tanya_syarat_bayar_pajak',
    'cek_tagihan_pajak',
    'denda_keterlambatan',
    'informasi_stnk',
    'informasi_bpkb',
    'balik_nama_mutasi',
    'pembayaran_online',
    'e_samsat_aplikasi',
    'lokasi_jam_operasional',
    'syarat_pengurusan',
    'biaya_tarif',
    'jadwal_pelayanan',
    'verifikasi_dokumen',
    'komplain_pelayanan',
    'informasi_pendaftaran',
    'layanan_bantuan_rumah',
    'permintaan_sosialisasi',
    'pertanyaan_umum',
    'informasi_pembayaran_bank',
    'panduan_online',
    'lain_lain', // fallback bucket
  ],
  // Processing controls
  'batch_size' => env('ANALYTICS_BATCH_SIZE', 20),
  'rebatch_size' => env('ANALYTICS_REBATCH_SIZE', 30),
];
