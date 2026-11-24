<?php

return [
    // claude | mock
    'extractor' => env('PMP_EXTRACTOR', 'mock'),

    // Jabatan default untuk SEMUA baris (APR sering tidak mengisi jabatan). 08 = Staff.
    'default_jabatan' => env('PMP_DEFAULT_JABATAN', '08'),

    // Lookup data kependudukan berdasarkan NIK. driver: file (simulasi, data sintetis) | http (kerangka REST)
    'dukcapil' => [
        'driver' => env('DUKCAPIL_DRIVER', 'file'),
        'required' => env('DUKCAPIL_REQUIRED', true), // true: NIK tidak ditemukan = error merah; false: hanya kuning
        'http' => [
            'url' => env('DUKCAPIL_URL'),
            'token' => env('DUKCAPIL_TOKEN'),
            'timeout' => env('DUKCAPIL_TIMEOUT', 10),
        ],
    ],

    // Kolom yang DIKOSONGKAN saat NIK tidak terbaca di APR dan baru diisi dari Dukcapil setelah user mengisi NIK.
    // Kolom yang tidak ada di Dukcapil (nama dari APR, kontak, pekerjaan/jabatan/penghasilan) tetap dari APR.
    'hold_without_nik' => ['kelamin', 'tgl_lhr', 'kota_lhr', 'kota_ktp', 'ibu_kandung', 'sts_kawin', 'alamat1', 'alamat2', 'kodepos'],

    'branch_code' => env('PMP_BRANCH_CODE', '12703'),
    'branch_name' => env('PMP_BRANCH_NAME', 'Cabang Contoh (isi di .env)'),
    'nationality' => 'WNI', // template BAOWNITEMPLATE khusus WNI
    'bom_name' => env('PMP_BOM_NAME', 'Branch Operation Manager (simulasi)'),

    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-sonnet-5-5'),
        'endpoint' => env('CLAUDE_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
        'timeout' => env('CLAUDE_TIMEOUT', 180),
    ],

    // Data mitra FIKTIF untuk prototype. Ganti/tambah sesuai kebutuhan.
    'mitras' => [
        ['id' => '1270120', 'name' => 'PT Contoh Maju Bersama', 'pipeline_id' => 'PL-2026-0001', 'kode_produk' => 'TPY01', 'biaya_tabungan' => '0', 'status_kerjasama' => 'Aktif', 'status_benefit' => 'Benefit Payroll Reguler', 'sektor' => 'Perdagangan'],
        ['id' => '1270605', 'name' => 'PT Nusantara Fiktif Abadi', 'pipeline_id' => 'PL-2026-0002', 'kode_produk' => 'TPY02', 'biaya_tabungan' => '5000', 'status_kerjasama' => 'Aktif', 'status_benefit' => 'Benefit Payroll Premium', 'sektor' => 'Manufaktur'],
        ['id' => '1270733', 'name' => 'CV Dummy Sejahtera', 'pipeline_id' => 'PL-2026-0003', 'kode_produk' => 'TPY01', 'biaya_tabungan' => '0', 'status_kerjasama' => 'Aktif', 'status_benefit' => 'Benefit Payroll Reguler', 'sektor' => 'Jasa Konsultasi'],
    ],

    'sektor_industri' => ['Perdagangan', 'Manufaktur', 'Jasa Konsultasi', 'Teknologi Informasi', 'Konstruksi', 'Logistik', 'Makanan & Minuman', 'Energi', 'Tekstil', 'Kesehatan', 'Pendidikan'],
];
