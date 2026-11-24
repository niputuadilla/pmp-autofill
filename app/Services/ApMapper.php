<?php

namespace App\Services;

/**
 * Memetakan hasil baca APR (field mentah dari ekstraktor) ke kolom BAOWNITEMPLATE,
 * dan menggabungkannya dengan data Dukcapil hasil lookup berdasarkan NIK.
 */
class ApMapper
{
    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7,
        'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'jun' => 6, 'jul' => 7, 'agu' => 8, 'ags' => 8,
        'sep' => 9, 'okt' => 10, 'nov' => 11, 'des' => 12,
    ];

    /**
     * @param array<string,mixed> $raw field mentah dari APR
     * @return array<string,mixed> nilai kolom + '_warnings'
     */
    public static function toRow(array $raw): array
    {
        $g = fn(string $k) => trim((string)($raw[$k] ?? ''));
        $warn = [];
        $row = [];

        $row['nama'] = $g('nama_lengkap');
        $row['gelarsbl'] = $g('gelar_depan');
        $row['gelarsdh'] = $g('gelar_belakang');
        $row['kelamin'] = self::genderCode($g('jenis_kelamin'));
        $row['tgl_lhr'] = self::dateDdmmyyyy($g('tanggal_lahir'));
        $row['kota_lhr'] = $g('tempat_lahir');
        $row['no_ktp'] = $g('nik');
        $row['kota_ktp'] = $g('kota_kabupaten') ?: $g('kota_ktp');
        $row['npwp'] = $g('npwp');
        $row['ibu_kandung'] = $g('nama_ibu_kandung');

        $row['sts_kawin'] = self::statusCode($g('status_perkawinan'), $row['kelamin']);
        if (preg_match('/duda/i', $g('status_perkawinan')) && preg_match('/janda/i', $g('status_perkawinan'))) {
            $warn['sts_kawin'] = 'Dipilih dari jenis kelamin (kotak Duda/Janda), cek ulang';
        }

        $row['alamat1'] = $g('alamat_jalan');
        $row['alamat2'] = self::rtRw($g('rt'), $g('rw'));
        $row['kodepos'] = $g('kode_pos');
        // tidak ada kolom di template, hanya dipakai untuk mencari kode pos
        $row['_addr'] = ['kelurahan' => $g('kelurahan'), 'kecamatan' => $g('kecamatan'), 'kota' => $g('kota_kabupaten')];

        $row['tlp_rmh'] = $g('telepon_rumah');
        $row['no_hp'] = $g('telepon_selular');
        $row['email'] = $g('email');

        $row['pekerjaan'] = self::mapPekerjaan($g('pekerjaan_sekarang'));
        if ($row['pekerjaan'] === 'LAIN' && $g('pekerjaan_sekarang') !== '') {
            $warn['pekerjaan'] = 'Pilihan di form tidak dikenali, diset LAIN';
        }

        // Jabatan tidak dibaca dari APR (sering kosong): semua baris diisi default (Staff). Bisa diubah per baris.
        $row['jabatan'] = self::defaultJabatan();

        // form APR hanya memuat tahun mulai bekerja
        $tm = preg_replace('/\D/', '', $g('tahun_mulai'));
        if (strlen($tm) === 4) {
            $row['tgl_mulai'] = '0101' . substr($tm, 2, 2);
            $warn['tgl_mulai'] = 'Form hanya memuat tahun; tanggal/bulan diasumsikan 01/01';
        } elseif (strlen($tm) === 8) {
            $row['tgl_mulai'] = substr($tm, 0, 4) . substr($tm, 6, 2);
        } else {
            $row['tgl_mulai'] = $tm;
        }

        $row['gaji'] = $g('penghasilan_per_bulan');
        $row['pen_lain'] = $g('penghasilan_lain') ?: '0';

        $row['_warnings'] = $warn;
        return $row;
    }

    /**
     * NIK tidak terbaca/tidak lengkap: kosongkan kolom yang bisa dilengkapi Dukcapil (config pmp.hold_without_nik)
     * sampai user mengisi NIK. Nama, kontak, dan kolom pekerjaan tetap dari APR.
     *
     * @return bool true bila ada kolom yang ditahan
     */
    public static function holdWithoutNik(array &$row): bool
    {
        if (strlen(preg_replace('/\D/', '', (string)($row['no_ktp'] ?? ''))) === 16) return false;
        foreach (self::holdColumns() as $c) $row[$c] = '';
        $row['_addr'] = [];
        return true;
    }

    /** @return string[] */
    public static function holdColumns(): array
    {
        $def = ['kelamin', 'tgl_lhr', 'kota_lhr', 'kota_ktp', 'ibu_kandung', 'sts_kawin', 'alamat1', 'alamat2', 'kodepos'];
        return function_exists('config') ? (array)config('pmp.hold_without_nik', $def) : $def;
    }

    public static function defaultJabatan(): string
    {
        return function_exists('config') ? (string)config('pmp.default_jabatan', '08') : '08';
    }

    /**
     * Ubah record Dukcapil (format DukcapilClient) menjadi nilai per kolom template.
     *
     * @param array<string,mixed> $dk
     * @return array<string,string>
     */
    public static function dukcapilToColumns(array $dk): array
    {
        $g = fn(string $k) => trim((string)($dk[$k] ?? ''));
        $kelamin = self::genderCode($g('jenis_kelamin'));
        return array_filter([
            'no_ktp' => preg_replace('/\D/', '', $g('nik')),
            'nama' => mb_strtoupper($g('nama')),
            'kelamin' => $kelamin,
            'tgl_lhr' => self::dateDdmmyyyy($g('tanggal_lahir')),
            'kota_lhr' => mb_strtoupper($g('tempat_lahir')),
            'ibu_kandung' => mb_strtoupper($g('nama_ibu')),
            'sts_kawin' => self::statusCode($g('status_kawin'), $kelamin),
            'alamat1' => $g('alamat'),
            'alamat2' => self::rtRw($g('rt'), $g('rw')),
            'kota_ktp' => $g('kota'),
            'kodepos' => preg_replace('/\D/', '', $g('kode_pos')),
        ], fn($v) => $v !== '');
    }

    /**
     * Gabungkan data Dukcapil ke baris hasil baca APR.
     *  - sel kosong / tidak terbaca / NIK bukan 16 digit -> diisi dari Dukcapil (kuning)
     *  - sel terisi tetapi beda dengan Dukcapil          -> nilai APR dipertahankan, kuning + saran
     *
     * @param array<string,mixed>  $row nilai kolom (diubah di tempat)
     * @param array<string,string> $dkCols hasil dukcapilToColumns()
     * @param array<int,string>    $uncertainCols kolom yang tulisannya diragukan pembaca
     * @param array<string,string> $warn warnings (diubah di tempat)
     * @return array<string,string> saran nilai per kolom (untuk tombol "Pakai Dukcapil")
     */
    public static function mergeDukcapil(array &$row, array $dkCols, array $uncertainCols, array &$warn): array
    {
        $norm = fn(string $s) => preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($s));
        $suggest = [];

        foreach ($dkCols as $col => $val) {
            if ($col === 'no_ktp') continue; // NIK adalah kunci lookup
            $cur = trim((string)($row[$col] ?? ''));

            if ($cur === '' || in_array($col, $uncertainCols, true)) {
                $row[$col] = $val;
                $warn[$col] = 'Diisi dari Dukcapil';
            } elseif ($norm($cur) !== $norm($val)) {
                $warn[$col] = 'Beda dengan Dukcapil: ' . $val;
                $suggest[$col] = $val;
            }
        }
        return $suggest;
    }

    private static function rtRw(string $rt, string $rw): string
    {
        $rt = preg_replace('/\D/', '', $rt);
        $rw = preg_replace('/\D/', '', $rw);
        return ($rt !== '' && $rw !== '') ? sprintf('RT%03d RW%03d', (int)$rt, (int)$rw) : '';
    }

    private static function genderCode(string $s): string
    {
        $s = mb_strtolower(trim($s));
        if ($s === '') return '';
        if (str_contains($s, 'perempuan') || str_contains($s, 'wanita') || str_contains($s, 'female') || $s === 'f' || $s === 'p') return 'F';
        if (str_contains($s, 'laki') || str_contains($s, 'pria') || str_contains($s, 'male') || $s === 'm' || $s === 'l') return 'M';
        return '';
    }

    /** A Kawin, B Belum Kawin, C Janda, D Duda. Kotak "Duda/Janda" ditentukan dari jenis kelamin. */
    private static function statusCode(string $s, string $kelamin): string
    {
        $s = mb_strtolower($s);
        if ($s === '') return '';
        if (str_contains($s, 'belum') || str_contains($s, 'lajang') || str_contains($s, 'single')) return 'B';
        $janda = str_contains($s, 'janda') || str_contains($s, 'cerai mati') || str_contains($s, 'cerai hidup');
        $duda = str_contains($s, 'duda');
        if ($janda && $duda) return $kelamin === 'F' ? 'C' : 'D';
        if ($janda) return 'C';
        if ($duda) return 'D';
        if (str_contains($s, 'menikah') || str_contains($s, 'kawin') || str_contains($s, 'married')) return 'A';
        return '';
    }

    private static function dateDdmmyyyy(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) {
            return sprintf('%02d%02d%04d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{1,2})[\/\-. ](\d{1,2})[\/\-. ](\d{4})$/', $s, $m)) {
            return sprintf('%02d%02d%04d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $s, $m) && isset(self::MONTHS[mb_strtolower($m[2])])) {
            return sprintf('%02d%02d%04d', $m[1], self::MONTHS[mb_strtolower($m[2])], $m[3]);
        }
        return $s;
    }

    private static function mapPekerjaan(string $s): string
    {
        $s = mb_strtolower($s);
        if ($s === '') return '';
        $map = [
            'wiraswasta' => 'WSW', 'entrepreneur' => 'WSW', 'swasta' => 'PSW', 'private' => 'PSW',
            'asn' => 'PNS', 'pns' => 'PNS', 'civil servant' => 'PNS', 'bumn' => 'PBUM', 'bumd' => 'PBUM',
            'polri' => 'POL', 'polisi' => 'POL', 'tni' => 'MIL', 'army' => 'MIL',
            'pelajar' => 'MHS', 'mahasiswa' => 'MHS', 'student' => 'MHS', 'rumah tangga' => 'IRT', 'housewife' => 'IRT',
            'pejabat negara' => 'PNG', 'pensiun' => 'PENS', 'dokter' => 'DKTR', 'guru' => 'GR', 'dosen' => 'GR',
        ];
        // urutan penting: "wiraswasta" sebelum "swasta"
        foreach ($map as $needle => $code) {
            if (str_contains($s, $needle)) return $code;
        }
        return 'LAIN';
    }
}
