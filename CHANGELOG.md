## 2026-11-23
- **Verifikasi Branch Operation Manager**: Submit tidak langsung menerbitkan request. Status awal `Menunggu BOM`; BOM membuka halaman Verifikasi Request (Req ID `-`, group code, mitra id, nama perusahaan, kode & nama cabang, warga negara, jumlah data, status) beserta tabel data, lalu **Verifikasi & Setujui** atau **Tolak** (alasan wajib). Req ID baru tampil dan Excel baru bisa diunduh setelah disetujui. Perlu `php artisan migrate`.
- Tidak ada verifikasi Dukcapil ulang di tahap BOM (sudah dilakukan saat upload dan Submit).
- **Kode pos otomatis**: kode pos kosong dicari dari kelurahan/kecamatan/kota (`PostalCodeLookup`, referensi `database/sample/kodepos.json`). Terisi tapi beda dengan hasil cari: kuning + tombol saran.
- Dummy: ~12% APR (24 dari 200) sengaja tanpa kode pos; `dukcapil.json` tanpa kode pos (e-KTP memang tidak memuatnya).

# Release Notes

## [Unreleased](https://github.com/laravel/laravel/compare/v11.0.1...11.x)

## [v11.0.1](https://github.com/laravel/laravel/compare/v11.0.0...v11.0.1) - 2024-03-12

* [11.x] Fixes SQLite driver missing by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/laravel/pull/6361

## [v11.0.0 (2023-02-17)](https://github.com/laravel/laravel/compare/v10.3.2...v11.0.0)

Laravel 11 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
