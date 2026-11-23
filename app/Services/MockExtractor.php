<?php

namespace App\Services;

/**
 * Pengganti ekstraktor untuk demo tanpa API key.
 * Jika nama file cocok dengan PDF dummy (DUMMY-001.pdf ...) isinya diambil dari database/sample/labels.json
 * (ground truth generator dummy) lengkap dengan simulasi APR yang tidak terbaca (apr_unreadable) dan APR yang salah
 * tulis (apr_nama). Nama file lain memakai salah satu record contoh.
 */
class MockExtractor implements ApExtractor
{
    public function extract(string $pdfBytes, string $filename): array
    {
        usleep(400000); // simulasi waktu baca

        $base = strtoupper(pathinfo($filename, PATHINFO_FILENAME));
        $labels = [];
        $path = database_path('sample/labels.json');
        if (is_file($path)) {
            foreach (json_decode(file_get_contents($path), true) ?: [] as $r) $labels[$r['id']] = $r;
        }
        if (!$labels) return ['raw' => [], 'uncertain' => []];

        if (!isset($labels[$base])) {
            $keys = array_keys($labels);
            $n = (int)preg_replace('/\D/', '', $base) ?: random_int(1, 9999);
            $base = $keys[$n % count($keys)];
        }
        return $this->fromLabel($labels[$base]);
    }

    private function fromLabel(array $r): array
    {
        [$rt, $rw] = array_pad(explode('/', $r['rt_rw']), 2, '');
        $raw = [
            'nama_lengkap' => $r['apr_nama'] ?? $r['nama'], 'tempat_lahir' => $r['tempat_lahir'],
            'tanggal_lahir' => $r['tgl_lahir'], 'nama_ibu_kandung' => $r['ibu_kandung'], 'jenis_kelamin' => $r['gender'],
            'nik' => $r['nik'], 'telepon_selular' => $r['hp'], 'email' => $r['email'],
            'status_perkawinan' => $r['status'], 'alamat_jalan' => $r['alamat'], 'rt' => $rt, 'rw' => $rw,
            'kota_kabupaten' => $r['kota'], 'kode_pos' => $r['apr_kodepos'] ?? $r['kodepos'], 'kelurahan' => $r['kel'] ?? '', 'kecamatan' => $r['kec'] ?? '', 'pekerjaan_sekarang' => $r['pekerjaan'],
            'nama_perusahaan' => $r['perusahaan'], 'tahun_mulai' => $r['tahun_mulai'],
            'penghasilan_per_bulan' => $r['penghasilan'],
        ];

        $map = ['nama' => ['nama_lengkap'], 'ttl' => ['tempat_lahir', 'tanggal_lahir'], 'nik' => ['nik'], 'ibu' => ['nama_ibu_kandung']];
        $uncertain = [];
        $unreadable = $r['apr_unreadable'] ?? [];
        // bisa berupa list (JSON) atau teks "a;b"
        foreach (is_array($unreadable) ? $unreadable : explode(';', (string)$unreadable) as $key) {
            foreach ($map[$key] ?? [] as $f) {
                $raw[$f] = '';
                $uncertain[] = $f;
            }
        }
        return ['raw' => $raw, 'uncertain' => $uncertain];
    }
}
