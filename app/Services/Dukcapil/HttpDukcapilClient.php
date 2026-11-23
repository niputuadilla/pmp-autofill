<?php

namespace App\Services\Dukcapil;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * KERANGKA untuk layanan Dukcapil sungguhan lewat REST. Detail endpoint, autentikasi, dan nama field
 * berbeda per penyedia/bank: sesuaikan URL, header, dan pemetaan di bawah dengan dokumentasi layanan yang
 * dipakai. Belum diuji terhadap layanan nyata.
 *
 * .env: DUKCAPIL_DRIVER=http, DUKCAPIL_URL=https://.../nik, DUKCAPIL_TOKEN=...
 */
class HttpDukcapilClient implements DukcapilClient
{
    public function find(string $nik): ?array
    {
        $url = config('pmp.dukcapil.http.url');
        if (!$url) {
            throw new RuntimeException('DUKCAPIL_URL belum diisi di .env.');
        }

        $res = Http::withToken((string)config('pmp.dukcapil.http.token'))
            ->acceptJson()
            ->timeout((int)config('pmp.dukcapil.http.timeout', 10))
            ->get($url, ['nik' => $nik]);

        if ($res->status() === 404) return null;
        if (!$res->successful()) {
            throw new RuntimeException('Layanan Dukcapil error HTTP ' . $res->status());
        }

        $d = $res->json('data') ?? $res->json();
        if (!is_array($d) || empty($d)) return null;

        // SESUAIKAN pemetaan field di sini dengan respons layanan yang dipakai.
        return [
            'nik' => $nik,
            'nama' => (string)($d['nama'] ?? $d['nama_lengkap'] ?? ''),
            'tempat_lahir' => (string)($d['tempat_lahir'] ?? ''),
            'tanggal_lahir' => (string)($d['tanggal_lahir'] ?? ''),
            'jenis_kelamin' => (string)($d['jenis_kelamin'] ?? ''),
            'nama_ibu' => (string)($d['nama_ibu'] ?? $d['nama_ibu_kandung'] ?? ''),
            'alamat' => (string)($d['alamat'] ?? ''),
            'rt' => (string)($d['rt'] ?? ''),
            'rw' => (string)($d['rw'] ?? ''),
            'kelurahan' => (string)($d['kelurahan'] ?? ''),
            'kecamatan' => (string)($d['kecamatan'] ?? ''),
            'kota' => (string)($d['kota'] ?? $d['kabupaten'] ?? ''),
            'kode_pos' => (string)($d['kode_pos'] ?? ''),
            'status_kawin' => (string)($d['status_kawin'] ?? $d['status_perkawinan'] ?? ''),
            'pekerjaan' => (string)($d['pekerjaan'] ?? ''),
        ];
    }
}
