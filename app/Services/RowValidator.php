<?php

namespace App\Services;

use App\Support\Pmp;

/**
 * Normalisasi + validasi satu baris nasabah sesuai sheet "PETUNJUK PENGISIAN".
 * Satu-satunya sumber aturan: dipakai saat ekstraksi, saat user mengedit sel, dan saat submit.
 */
class RowValidator
{
    /**
     * @param array<string,mixed> $in nilai per kolom (key = Pmp::COLUMNS)
     * @return array{values: array<string,string>, errors: array<string,string>, warnings: array<string,string>}
     */
    public static function validate(array $in): array
    {
        $v = [];
        foreach (Pmp::TABLE_COLUMNS as $k) {
            $v[$k] = trim((string)($in[$k] ?? ''));
        }
        $err = [];
        $warn = $in['_warnings'] ?? [];

        $len = fn(string $s) => mb_strlen($s);
        $req = function (string $k) use (&$v, &$err) {
            if ($v[$k] === '') {
                $err[$k] = 'Wajib diisi';
                return false;
            }
            return true;
        };

        // NAMA
        $v['nama'] = mb_strtoupper(preg_replace('/\s+/', ' ', $v['nama']));
        if ($req('nama')) {
            if ($len($v['nama']) > 40) $err['nama'] = 'Maksimal 40 karakter (' . $len($v['nama']) . ')';
            elseif (!preg_match('/^[A-Z .,\'"()\/\-]+$/u', $v['nama'])) $err['nama'] = 'Tidak boleh angka / karakter khusus selain . , \' " ( ) / -';
        }

        // GELAR
        foreach (['gelarsbl', 'gelarsdh'] as $k) {
            if ($len($v[$k]) > 15) $err[$k] = 'Maksimal 15 karakter';
        }

        // KELAMIN
        $v['kelamin'] = mb_strtoupper($v['kelamin']);
        if ($v['kelamin'] === 'PRIA' || $v['kelamin'] === 'L') $v['kelamin'] = 'M';
        if ($v['kelamin'] === 'WANITA' || $v['kelamin'] === 'P') $v['kelamin'] = 'F';
        if ($req('kelamin') && !isset(Pmp::KELAMIN[$v['kelamin']])) $err['kelamin'] = 'Harus M atau F';

        // TGL_LHR DDMMYYYY
        $v['tgl_lhr'] = preg_replace('/\D/', '', $v['tgl_lhr']);
        if ($req('tgl_lhr')) {
            if (!preg_match('/^\d{8}$/', $v['tgl_lhr'])) {
                $err['tgl_lhr'] = 'Format DDMMYYYY (8 digit)';
            } else {
                $d = (int)substr($v['tgl_lhr'], 0, 2);
                $m = (int)substr($v['tgl_lhr'], 2, 2);
                $y = (int)substr($v['tgl_lhr'], 4, 4);
                if (!checkdate($m, $d, $y)) $err['tgl_lhr'] = 'Tanggal tidak valid';
                elseif (mktime(0, 0, 0, $m, $d, $y) > time()) $err['tgl_lhr'] = 'Tidak boleh lebih dari hari ini';
            }
        }

        // KOTA
        $v['kota_lhr'] = mb_strtoupper($v['kota_lhr']);
        if ($req('kota_lhr')) {
            if ($len($v['kota_lhr']) < 3) $err['kota_lhr'] = 'Minimal 3 karakter';
            elseif ($len($v['kota_lhr']) > 30) $err['kota_lhr'] = 'Maksimal 30 karakter';
        }
        $v['kota_ktp'] = mb_strtoupper($v['kota_ktp']);
        if ($req('kota_ktp') && $len($v['kota_ktp']) > 30) $err['kota_ktp'] = 'Maksimal 30 karakter';

        // NO_KTP
        $raw = $v['no_ktp'];
        $v['no_ktp'] = preg_replace('/[\s.\-]/', '', $raw);
        if ($req('no_ktp')) {
            $n = $v['no_ktp'];
            if (!preg_match('/^\d+$/', $n)) $err['no_ktp'] = 'Hanya angka, tanpa karakter khusus';
            elseif (strlen($n) !== 16) $err['no_ktp'] = 'Harus 16 digit (terbaca ' . strlen($n) . ')';
            elseif ($n[0] === '0') $err['no_ktp'] = 'Tidak boleh diawali angka 0';
            elseif (preg_match('/^0+$/', $n) || preg_match('/^0+$/', substr($n, 2))) $err['no_ktp'] = 'Tidak boleh 0 semua / 14 digit belakang 0';
        }

        // GAJI & PEN_LAIN
        $v['gaji'] = preg_replace('/\D/', '', preg_replace('/(,|\.)\d{1,2}$/', '', $v['gaji']));
        if ($req('gaji') && (int)$v['gaji'] <= 0) $err['gaji'] = 'Harus lebih dari 0';
        $v['pen_lain'] = preg_replace('/\D/', '', $v['pen_lain']);
        if ($v['pen_lain'] === '') $v['pen_lain'] = '0';

        // NPWP: tidak wajib (sekarang NPWP = NIK). Jika diisi, harus 16 digit.
        $v['npwp'] = preg_replace('/\D/', '', $v['npwp']);
        if ($v['npwp'] !== '' && strlen($v['npwp']) !== 16) {
            $err['npwp'] = 'Jika diisi harus 16 digit (terbaca ' . strlen($v['npwp']) . ')';
        }

        // IBU
        $v['ibu_kandung'] = mb_strtoupper($v['ibu_kandung']);
        if ($req('ibu_kandung') && $len($v['ibu_kandung']) > 25) $err['ibu_kandung'] = 'Maksimal 25 karakter (' . $len($v['ibu_kandung']) . ')';

        // STS_KAWIN
        $v['sts_kawin'] = mb_strtoupper($v['sts_kawin']);
        if ($req('sts_kawin') && !isset(Pmp::STS_KAWIN[$v['sts_kawin']])) $err['sts_kawin'] = 'Harus A, B, C, atau D';

        // ALAMAT
        if ($req('alamat1') && $len($v['alamat1']) > 40) $err['alamat1'] = 'Maksimal 40 karakter (' . $len($v['alamat1']) . ')';
        $v['alamat2'] = mb_strtoupper($v['alamat2']);
        if ($req('alamat2')) {
            if (!preg_match('/^RT\d{3} RW\d{3}$/', $v['alamat2'])) $err['alamat2'] = 'Format RT000 RW000';
        }
        $v['kodepos'] = preg_replace('/\D/', '', $v['kodepos']);
        if ($req('kodepos')) {
            if (!preg_match('/^\d{5}$/', $v['kodepos']) || $v['kodepos'] === '00000') $err['kodepos'] = '5 digit dan bukan 00000';
        }

        // TLP_RMH & NO_HP (conditional: salah satu wajib)
        $v['tlp_rmh'] = preg_replace('/\D/', '', $v['tlp_rmh']);
        $hp = preg_replace('/\D/', '', $v['no_hp']);
        if ($hp !== '') {
            if ($hp[0] === '0') $hp = '62' . substr($hp, 1);
            elseif ($hp[0] === '8') $hp = '62' . $hp;
        }
        $v['no_hp'] = $hp;
        if ($v['tlp_rmh'] === '' && $v['no_hp'] === '') {
            $err['no_hp'] = 'No. HP atau Telp. Rumah wajib salah satu';
        }
        if ($v['no_hp'] !== '') {
            if (!preg_match('/^62[1-9]\d{6,12}$/', $v['no_hp'])) $err['no_hp'] = 'Awali 62, 9-15 digit, angka setelah 62 bukan 0';
            elseif (strlen($v['no_hp']) < 9 || strlen($v['no_hp']) > 15) $err['no_hp'] = 'Harus 9-15 digit';
        }
        if ($v['tlp_rmh'] !== '') {
            if (strlen($v['tlp_rmh']) < 7 || strlen($v['tlp_rmh']) > 12 || preg_match('/^0+$/', $v['tlp_rmh'])) $err['tlp_rmh'] = '7-12 digit, bukan 0 semua';
        }

        // EMAIL
        $v['email'] = mb_strtolower($v['email']);
        if ($v['email'] !== '') {
            if ($len($v['email']) > 40) $err['email'] = 'Maksimal 40 karakter';
            elseif ($v['email'] === 'tidak@memiliki.com' || !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Format email tidak valid';
        }

        // PEKERJAAN & JABATAN
        $v['pekerjaan'] = mb_strtoupper($v['pekerjaan']);
        if ($req('pekerjaan') && !isset(Pmp::PEKERJAAN[$v['pekerjaan']])) $err['pekerjaan'] = 'Kode pekerjaan tidak dikenal';
        if ($req('jabatan')) {
            $v['jabatan'] = str_pad(preg_replace('/\D/', '', $v['jabatan']), 2, '0', STR_PAD_LEFT);
            if (!isset(Pmp::JABATAN[$v['jabatan']])) $err['jabatan'] = 'Kode jabatan tidak dikenal';
        }

        // TGL_MULAI DDMMYY
        $v['tgl_mulai'] = preg_replace('/\D/', '', $v['tgl_mulai']);
        if ($req('tgl_mulai')) {
            if (!preg_match('/^\d{6}$/', $v['tgl_mulai'])) {
                $err['tgl_mulai'] = 'Format DDMMYY (6 digit)';
            } elseif (!checkdate((int)substr($v['tgl_mulai'], 2, 2), (int)substr($v['tgl_mulai'], 0, 2), 2000 + (int)substr($v['tgl_mulai'], 4, 2))) {
                $err['tgl_mulai'] = 'Tanggal tidak valid';
            }
        }

        foreach ($err as $k => $_) unset($warn[$k]);

        return ['values' => $v, 'errors' => $err, 'warnings' => $warn];
    }
}
