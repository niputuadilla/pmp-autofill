<?php

namespace App\Services;

/**
 * Mencari kode pos dari kelurahan / kecamatan / kota.
 * Sumber: file JSON referensi (list of {kelurahan, kecamatan, kota, kode_pos}).
 * Urutan pencocokan: kelurahan+kecamatan+kota -> kelurahan+kota -> kecamatan+kota (hanya bila semua
 * baris di kecamatan itu punya kode pos yang sama, supaya tidak menebak).
 * Ganti file referensi dengan data resmi (mis. Pos Indonesia / Kemendagri) untuk pemakaian nyata.
 */
class PostalCodeLookup
{
    /** @var array<int,array{kel:string,kec:string,kota:string,pos:string}>|null */
    private ?array $rows = null;

    public function __construct(private ?string $file = null) {}

    public function find(string $kelurahan, string $kecamatan, string $kota): ?string
    {
        $kel = self::norm($kelurahan);
        $kec = self::norm($kecamatan);
        $kot = self::norm($kota);
        if ($kot === '' || ($kel === '' && $kec === '')) return null;

        $rows = $this->load();

        foreach ([[true, true], [true, false]] as [$useKec, $_]) {
            if ($kel === '') break;
            $hit = array_unique(array_column(array_filter($rows, fn($r) =>
                $r['kota'] === $kot && $r['kel'] === $kel && (!$useKec || $kec === '' || $r['kec'] === $kec)), 'pos'));
            if (count($hit) === 1) return reset($hit);
        }

        if ($kec !== '') {
            $hit = array_unique(array_column(array_filter($rows, fn($r) => $r['kota'] === $kot && $r['kec'] === $kec), 'pos'));
            if (count($hit) === 1) return reset($hit);
        }
        return null;
    }

    /** Huruf besar, tanpa tanda baca, tanpa awalan KOTA/KAB/KABUPATEN/KEC/KEL/DESA. */
    public static function norm(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        $s = preg_replace('/[^A-Z0-9 ]/', ' ', $s);
        $s = preg_replace('/^(KOTA ADM|KOTA|KABUPATEN|KAB|KECAMATAN|KEC|KELURAHAN|KEL|DESA) /', '', trim($s));
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private function load(): array
    {
        if ($this->rows !== null) return $this->rows;
        $file = $this->file ?: (function_exists('database_path') ? database_path('sample/kodepos.json') : '');
        $this->rows = [];
        if ($file && is_file($file)) {
            foreach ((array)json_decode((string)file_get_contents($file), true) as $r) {
                $pos = preg_replace('/\D/', '', (string)($r['kode_pos'] ?? ''));
                if (strlen($pos) !== 5) continue;
                $this->rows[] = [
                    'kel' => self::norm((string)($r['kelurahan'] ?? '')),
                    'kec' => self::norm((string)($r['kecamatan'] ?? '')),
                    'kota' => self::norm((string)($r['kota'] ?? '')),
                    'pos' => $pos,
                ];
            }
        }
        return $this->rows;
    }
}
