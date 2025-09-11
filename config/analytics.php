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
    'batch_size' => env('ANALYTICS_BATCH_SIZE', 50),
    'rebatch_size' => env('ANALYTICS_REBATCH_SIZE', 60),
    // How many top topics to produce strategies for
    'top_count' => env('ANALYTICS_TOP_COUNT', 3),
    // Snippet lengths for fast vs full processing
    'fast_snippet_length' => env('ANALYTICS_FAST_SNIPPET_LENGTH', 280),
    'full_snippet_length' => env('ANALYTICS_FULL_SNIPPET_LENGTH', 900),
    // Target total words for top-topic strategies (approximate across all top topics)
    'strategy_word_goal' => env('ANALYTICS_STRATEGY_WORD_GOAL', 800),
    // Target words for each per-topic long insight
    'per_topic_word_goal' => env('ANALYTICS_PER_TOPIC_WORD_GOAL', 800),
    // Enable lightweight fast processing heuristics by default for large datasets
    'fast_mode' => env('ANALYTICS_FAST_MODE', true),
    // Classification cache across runs (hash-based). If table missing, it will be ignored.
    'enable_classification_cache' => env('ANALYTICS_ENABLE_CLASS_CACHE', true),
    'classification_cache_ttl_days' => env('ANALYTICS_CLASS_CACHE_TTL_DAYS', 90),
];
