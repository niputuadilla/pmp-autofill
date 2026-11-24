# PMP Autofill (prototype)

Prototype fitur **Autofill APR** untuk menu *Account Opening > Manual Bulk* pada Payroll Management Platform.

Alur:
1. **Add Request > Manual Bulk**.
2. Upload banyak PDF scan APR (satu PDF = satu nasabah, maks 5MB).
3. Sistem membaca tulisan tangan dan mengisi tabel **Informasi Data Diri + Informasi Pekerjaan**
   (kolom mengikuti `BAOWNITEMPLATE.xlsx`). Sel yang melanggar aturan sheet *Petunjuk Pengisian* ditandai merah,
   sel yang diragukan ditandai kuning, semuanya bisa diedit langsung.
   Hanya APR yang dipindai. Backend lalu **mencari data kependudukan berdasarkan NIK** (Dukcapil) untuk melengkapi dan memverifikasi:
   - sel yang kosong / tulisannya tidak terbaca -> **diisi dari Dukcapil** (sel kuning);
   - sel yang terisi tetapi **beda** dengan Dukcapil -> nilai APR dipertahankan, sel kuning + tombol **Pakai Dukcapil**;
   - NIK tidak ditemukan -> sel NIK merah (atur lewat `DUKCAPIL_REQUIRED`); NIK yang dikoreksi user langsung dicari ulang;
   - **Jabatan** diisi default Staff (`PMP_DEFAULT_JABATAN=08`) untuk semua baris karena APR sering tidak mengisinya.
4. **Informasi Mitra** dan **Informasi Lainnya (consent)** diisi **sekali** untuk semua nasabah.
5. **Submit**: request tercatat di list Account Opening dan bisa mengunduh `BAOWNITEMPLATE_<RequestID>.xlsx`
   (23 kolom, sudah memuat CONSENT, siap diimpor ke PMP).

Stack: Laravel 11, Blade, Alpine.js (sudah di `public/js`, tanpa build step), SQLite.

## Setup lokal (Laragon / terminal)

Butuh PHP 8.2+ (ext: zip, sqlite3/pdo_sqlite, mbstring, curl) dan Composer.

```bash
composer install
copy .env.example .env        # Linux/Mac: cp .env.example .env
php artisan key:generate
php artisan migrate           # jawab "yes" bila ditanya membuat database.sqlite
php artisan serve             # buka http://127.0.0.1:8000
```

Tidak perlu `npm install`.

## Mode ekstraksi (`.env`)

| Mode | Setting | Keterangan |
|---|---|---|
| Mock (default) | `PMP_EXTRACTOR=mock` | Tanpa API key. Isi diambil dari contoh (`database/sample/labels.json` bila nama file `DUMMY-001.pdf` dst, selain itu data acak), termasuk simulasi APR tidak terbaca / salah tulis supaya fallback Dukcapil bisa dicoba. |
| Claude | `PMP_EXTRACTOR=claude`<br>`ANTHROPIC_API_KEY=sk-ant-...`<br>`CLAUDE_MODEL=claude-sonnet-5-5` | PDF dikirim ke Claude API (vision) dan dibaca tulisan tangannya. |

Catatan data: mode `claude` mengirim isi PDF ke API eksternal. Pakai data dummy untuk prototype; untuk data nasabah
asli perlu persetujuan keamanan informasi dan opsi model yang berjalan di lingkungan bank.

Jika upload ditolak, naikkan `upload_max_filesize` dan `post_max_size` di `php.ini` (minimal 6M).

## Peta kode

| File | Fungsi |
|---|---|
| `app/Support/Pmp.php` | Definisi 23 kolom, kode PEKERJAAN/JABATAN/STS_KAWIN, teks Petunjuk Pengisian |
| `app/Services/RowValidator.php` | Semua aturan validasi + normalisasi (satu sumber, dipakai ekstraksi, edit sel, dan submit) |
| `app/Services/ApMapper.php` | Field mentah APR -> kolom template (tanggal, RT/RW, kode pekerjaan, status kawin, dst) + aturan gabung dengan Dukcapil (`mergeDukcapil`) |
| `app/Services/RowEnricher.php` | Satu pintu olah baris: lookup Dukcapil by NIK, gabung, validasi (dipakai saat ekstraksi, edit sel, dan submit) |
| `app/Services/Dukcapil/*` | `DukcapilClient` (interface), `FileDukcapilClient` (simulasi, `database/sample/dukcapil.json`), `HttpDukcapilClient` (kerangka REST, perlu disesuaikan) |
| `app/Services/ClaudeExtractor.php` | Prompt + panggilan Claude API (ubah `PROMPT` untuk menyetel hasil) |
| `app/Services/MockExtractor.php` | Ekstraktor palsu untuk demo |
| `app/Services/XlsxWriter.php` | Penulis `.xlsx` tanpa dependensi (kolom teks supaya angka 0 di depan tidak hilang) |
| `app/Http/Controllers/AccountOpeningController.php` | Route halaman, extract, validate-row, submit, download |
| `resources/views/account-opening/bulk.blade.php` | Halaman Manual Bulk + logika Alpine |
| `config/pmp.php` | Kode cabang, **data mitra fiktif**, daftar sektor industri |

## Hal yang perlu kamu sesuaikan

- **Daftar jabatan**: halaman referensi terpotong di kode 24, tambahkan kode lainnya di `Pmp::JABATAN`.
- **Mitra & sektor industri**: data di `config/pmp.php` fiktif.
- **TGL_MULAI**: APR hanya memuat tahun mulai kerja, jadi diisi `0101YY` dan ditandai kuning.
- **NPWP**: tidak wajib (NPWP sekarang = NIK). Jika diisi harus 16 digit.
- **NO_HP**: otomatis diubah ke awalan `62` (mis. `0813...` jadi `62813...`) sesuai aturan template.
- **Dukcapil**: default `DUKCAPIL_DRIVER=file` hanya simulasi dengan data sintetis. Untuk layanan sungguhan, sesuaikan `HttpDukcapilClient` (URL, autentikasi, nama field) atau buat implementasi `DukcapilClient` sendiri (mis. query DB internal). Aturan gabung ada di `ApMapper::mergeDukcapil`; ubah di sana bila ingin Dukcapil selalu menang saat beda.
- Menu selain Account Opening hanya tampilan; halaman Manual Satuan hanya replika visual.

## Verifikasi Branch Operation Manager & kode pos otomatis (2026-11-23)

Alur: Upload APR -> cek/koreksi tabel -> Submit -> request berstatus **Menunggu BOM** -> BOM menyetujui/menolak di halaman *Verifikasi Request* -> setelah disetujui Req ID tampil dan Excel BAOWNITEMPLATE bisa diunduh (nomor rekening diterbitkan di proses berikutnya, belum ada di prototype ini).

- Nama cabang: `PMP_BRANCH_NAME` di `.env`. Setelah update jalankan `php artisan migrate`.
- Kode pos: `app/Services/PostalCodeLookup.php` membaca `database/sample/kodepos.json` (`kelurahan, kecamatan, kota, kode_pos`). File itu hanya data sintetis untuk dummy; untuk dipakai nyata ganti dengan data referensi resmi (mis. Pos Indonesia/Kemendagri) dengan format yang sama.

## NIK tidak terbaca (2026-11-24)
Jika NIK di APR tidak terbaca/tidak 16 digit, tabel hanya mengisi **nama** dan data khas APR (kontak, pekerjaan, jabatan, penghasilan). Kolom yang bisa dilengkapi Dukcapil (`pmp.hold_without_nik` di `config/pmp.php`: kelamin, tgl lahir, kota lahir, kota KTP, ibu kandung, status kawin, alamat, RT/RW, kode pos) dikosongkan dengan keterangan "Menunggu NIK". Setelah user mengisi NIK, kolom itu terisi dari Dukcapil. Ubah daftar kolomnya di config bila perlu.
