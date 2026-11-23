<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Membaca PDF scan APR (tulisan tangan) dengan Claude (vision) lewat Messages API.
 * PDF dikirim apa adanya sebagai blok "document" base64.
 */
class ClaudeExtractor implements ApExtractor
{
    public const PROMPT = <<<'TXT'
Dokumen terlampir adalah hasil scan "Aplikasi Pembukaan Rekening Produk Dana Perorangan" (APR) yang diisi TULISAN TANGAN oleh SATU nasabah. Bagian "Diisi oleh Bank" (cabang, tanggal, CIF) dan kotak bank di bawah dokumen BUKAN data nasabah; abaikan. Jika ada halaman lain yang bukan APR (mis. lampiran atau cetakan lain), abaikan.

Baca isi form dan kembalikan HANYA satu objek JSON (tanpa teks lain, tanpa markdown) dengan key persis berikut. Gunakan string kosong "" bila kosong atau tidak terbaca. JANGAN menebak dan JANGAN memperbaiki data yang kelihatannya salah; salin apa adanya yang tertulis.
Jika tulisan dicoret, diblok, atau tidak terbaca, kosongkan field itu dan masukkan key-nya ke "uncertain_fields" (sistem akan melengkapinya dari database kependudukan berdasarkan NIK).

{
  "nama_lengkap": "nama lengkap sesuai kartu identitas, tanpa gelar",
  "gelar_depan": "",
  "gelar_belakang": "",
  "tempat_lahir": "kota saja",
  "tanggal_lahir": "format YYYY-MM-DD (ubah dari bulan Indonesia, mis. 20 Februari 2003 -> 2003-02-20)",
  "nama_ibu_kandung": "",
  "jenis_kelamin": "Pria atau Wanita (kotak yang dicentang)",
  "kewarganegaraan": "WNI atau WNA",
  "nik": "digit NIK/Paspor persis seperti tertulis, hanya angka",
  "npwp": "hanya angka",
  "telepon_selular": "hanya angka",
  "telepon_rumah": "telepon kantor/rumah jika ada, hanya angka",
  "email": "",
  "status_perkawinan": "Lajang, Menikah, atau Duda/Janda (kotak yang dicentang)",
  "alamat_jalan": "nama jalan/lingkungan/blok dan nomor dari 'Alamat sesuai ID' (tanpa RT/RW, kelurahan, kecamatan, kota)",
  "rt": "angka RT dari alamat sesuai ID",
  "rw": "angka RW dari alamat sesuai ID",
  "kelurahan": "",
  "kecamatan": "",
  "kota_kabupaten": "kota/kabupaten alamat sesuai ID",
  "kode_pos": "kode pos alamat sesuai ID; kosongkan jika tidak diisi (jangan menebak dari alamat)",
  "pendidikan_terakhir": "",
  "pekerjaan_sekarang": "teks pilihan yang dicentang pada Data Pekerjaan (mis. Swasta, Wiraswasta, ASN/PNS, BUMN/BUMD, TNI/POLRI, Pelajar/Mahasiswa, Ibu Rumah Tangga, Pejabat Negara, Lainnya)",
  "nama_perusahaan": "",
  "bidang_usaha": "",
  "alamat_kantor": "",
  "tahun_mulai": "tahun mulai bekerja/usaha, 4 digit",
  "sumber_penghasilan": "Gaji atau Lainnya",
  "penghasilan_per_bulan": "angka penghasilan per bulan dalam Rupiah, hanya digit; kosong bila tidak ada",
  "penghasilan_lain": "",
  "uncertain_fields": ["daftar key APR di atas yang tulisannya sulit dibaca / kamu ragu"]
}

TXT;

    /** key mentah -> key kolom (untuk menandai sel yang diragukan) */
    private const RAW_TO_COL = [
        'nama_lengkap' => 'nama', 'gelar_depan' => 'gelarsbl', 'gelar_belakang' => 'gelarsdh',
        'tempat_lahir' => 'kota_lhr', 'tanggal_lahir' => 'tgl_lhr', 'nama_ibu_kandung' => 'ibu_kandung',
        'jenis_kelamin' => 'kelamin', 'nik' => 'no_ktp', 'npwp' => 'npwp', 'telepon_selular' => 'no_hp',
        'telepon_rumah' => 'tlp_rmh', 'email' => 'email', 'status_perkawinan' => 'sts_kawin',
        'alamat_jalan' => 'alamat1', 'rt' => 'alamat2', 'rw' => 'alamat2', 'kode_pos' => 'kodepos',
        'kota_kabupaten' => 'kota_ktp', 'pekerjaan_sekarang' => 'pekerjaan', 'jabatan' => 'jabatan',
        'tahun_mulai' => 'tgl_mulai', 'penghasilan_per_bulan' => 'gaji', 'penghasilan_lain' => 'pen_lain',
    ];

    public static function rawToColumn(string $rawKey): ?string
    {
        return self::RAW_TO_COL[$rawKey] ?? null;
    }

    public function extract(string $pdfBytes, string $filename): array
    {
        $key = config('pmp.claude.api_key');
        if (!$key) {
            throw new RuntimeException('ANTHROPIC_API_KEY belum diisi di .env (atau set PMP_EXTRACTOR=mock).');
        }

        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ])->timeout((int)config('pmp.claude.timeout', 180))->post(config('pmp.claude.endpoint'), [
            'model' => config('pmp.claude.model'),
            'max_tokens' => 2000,
            'temperature' => 0,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($pdfBytes)]],
                    ['type' => 'text', 'text' => self::PROMPT],
                ],
            ]],
        ]);

        if (!$res->successful()) {
            $msg = $res->json('error.message') ?? $res->body();
            throw new RuntimeException('Claude API ' . $res->status() . ': ' . $msg);
        }

        $text = '';
        foreach ((array)$res->json('content') as $block) {
            if (($block['type'] ?? '') === 'text') $text .= $block['text'];
        }

        $raw = self::parseJson($text);
        $uncertain = array_values(array_filter((array)($raw['uncertain_fields'] ?? []), 'is_string'));
        unset($raw['uncertain_fields']);

        return ['raw' => $raw, 'uncertain' => $uncertain];
    }

    public static function parseJson(string $text): array
    {
        $text = trim($text);
        $a = strpos($text, '{');
        $b = strrpos($text, '}');
        if ($a === false || $b === false || $b < $a) {
            throw new RuntimeException('Jawaban model bukan JSON: ' . mb_substr($text, 0, 200));
        }
        $data = json_decode(substr($text, $a, $b - $a + 1), true);
        if (!is_array($data)) {
            throw new RuntimeException('JSON dari model tidak valid: ' . json_last_error_msg());
        }
        return $data;
    }
}
