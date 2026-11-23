<?php

namespace App\Support;

/**
 * Definisi kolom & kode referensi, mengikuti BAOWNITEMPLATE.xlsx
 * (halaman 21-26 pada PDF referensi: sheet "BAO WNI PMP" + "PETUNJUK PENGISIAN").
 */
class Pmp
{
    /** key internal => header kolom di Excel */
    public const COLUMNS = [
        'nama'        => 'NAMA',
        'gelarsbl'    => 'GELARSBL',
        'gelarsdh'    => 'GELARSDH',
        'kelamin'     => 'KELAMIN',
        'tgl_lhr'     => 'TGL_LHR',
        'kota_lhr'    => 'KOTA_LHR',
        'no_ktp'      => 'NO_KTP',
        'kota_ktp'    => 'KOTA_KTP',
        'npwp'        => 'NPWP',
        'ibu_kandung' => 'IBU_KANDUNG',
        'sts_kawin'   => 'STS_KAWIN',
        'alamat1'     => 'ALAMAT1',
        'alamat2'     => 'ALAMAT2',
        'kodepos'     => 'KODEPOS',
        'tlp_rmh'     => 'TLP_RMH',
        'no_hp'       => 'NO_HP',
        'email'       => 'EMAIL',
        'pekerjaan'   => 'PEKERJAAN',
        'jabatan'     => 'JABATAN',
        'tgl_mulai'   => 'TGL_MULAI',
        'gaji'        => 'GAJI',
        'pen_lain'    => 'PEN_LAIN',
        'consent'     => 'CONSENT',
    ];

    /** Kolom yang tampil di tabel hasil autofill (Informasi Data Diri + Informasi Pekerjaan). CONSENT diisi sekali di "Informasi Lainnya". */
    public const TABLE_COLUMNS = [
        'nama', 'gelarsbl', 'gelarsdh', 'kelamin', 'tgl_lhr', 'kota_lhr', 'no_ktp', 'kota_ktp', 'npwp',
        'ibu_kandung', 'sts_kawin', 'alamat1', 'alamat2', 'kodepos', 'tlp_rmh', 'no_hp', 'email',
        'pekerjaan', 'jabatan', 'tgl_mulai', 'gaji', 'pen_lain',
    ];

    public const LABELS = [
        'nama' => 'Nama Lengkap', 'gelarsbl' => 'Gelar Depan', 'gelarsdh' => 'Gelar Belakang',
        'kelamin' => 'Jenis Kelamin', 'tgl_lhr' => 'Tanggal Lahir', 'kota_lhr' => 'Kota Lahir',
        'no_ktp' => 'No KTP', 'kota_ktp' => 'Kota KTP', 'npwp' => 'NPWP', 'ibu_kandung' => 'Nama Ibu Kandung',
        'sts_kawin' => 'Status Kawin', 'alamat1' => 'Alamat 1', 'alamat2' => 'Alamat 2', 'kodepos' => 'Kode Pos',
        'tlp_rmh' => 'Telp. Rumah', 'no_hp' => 'No. Handphone', 'email' => 'Email', 'pekerjaan' => 'Pekerjaan',
        'jabatan' => 'Jabatan', 'tgl_mulai' => 'Tgl Mulai', 'gaji' => 'Gaji', 'pen_lain' => 'Pendapatan Lain',
        'consent' => 'Consent',
    ];

    public const KELAMIN = ['M' => 'M - Male', 'F' => 'F - Female'];

    public const STS_KAWIN = ['A' => 'A - Kawin', 'B' => 'B - Belum Kawin', 'C' => 'C - Janda', 'D' => 'D - Duda'];

    public const PEKERJAAN = [
        'AKT' => 'Akuntan', 'DKTR' => 'Dokter', 'ENGR' => 'Engineer/Teknisi', 'GR' => 'Guru/Dosen',
        'IRT' => 'Ibu Rumah Tangga', 'KONS' => 'Konsultan', 'KOP' => 'Pegawai Koperasi', 'LAIN' => 'Lainnya',
        'MHS' => 'Mahasiswa/Pelajar', 'MIL' => 'Militer (AU, AD, AL, PM)', 'NTRS' => 'Notaris',
        'PBUM' => 'Pegawai BUMN/BUMD', 'PENS' => 'Pensiunan', 'PGCR' => 'Pengacara', 'PIF' => 'Pekerja Informal',
        'PKJSN' => 'Pekerja Seni', 'PKS' => 'Pekerja Sosial/LSM', 'PNG' => 'Pejabat Negara',
        'PNS' => 'Pegawai Negeri Sipil', 'POL' => 'Polisi', 'PRMDS' => 'Paramedis', 'PROF' => 'Profesional',
        'PSW' => 'Pegawai Swasta', 'PTN' => 'Petani/Nelayan', 'WRTWN' => 'Wartawan', 'WSW' => 'Wiraswasta',
        'YYS' => 'Pegawai Yayasan',
    ];

    /**
     * Daftar jabatan pada template baru terbaca sampai kode 24 (halaman 25 terpotong di kode 25+).
     * Lengkapi di sini jika daftar aslinya lebih panjang.
     */
    public const JABATAN = [
        '01' => 'Komisaris/Pemilik', '02' => 'DIRUT/CEO', '03' => 'Direktur', '04' => 'Wakil Direktur',
        '05' => 'GM/Kepala Biro', '06' => 'Manager/Kepala Bagian', '07' => 'Assistant Manager/Kepala Seksi',
        '08' => 'Staff', '09' => 'Pelaksana', '10' => 'Pegawai Dasar', '11' => 'Perwira Tinggi',
        '12' => 'Perwira Menengah', '13' => 'Perwira Pertama', '14' => 'Bintara', '15' => 'Tamtama',
        '16' => 'Pengurus Koperasi/Yayasan/LSM', '17' => 'Anggota Koperasi/Yayasan/LSM',
        '18' => 'Kepala Pemerintahan', '19' => 'Aparat Pemerintahan', '20' => 'Anggota Lembaga Tinggi Negara',
        '21' => 'Menteri', '22' => 'Dokter', '23' => 'Notaris/Pengacara', '24' => 'Guru/Dosen',
    ];

    public const CONSENT_CHANNELS = [
        'aplikasi_pesan' => 'Aplikasi pesan instan',
        'panggilan_suara' => 'Panggilan suara (voice mail)',
        'sms' => 'Pesan teks melalui Short Message Service (SMS)',
        'email' => 'Surat elektronik (e-mail)',
    ];

    public const NPWP_THRESHOLD = 4500000;

    /** Teks panduan per kolom (ringkasan sheet "PETUNJUK PENGISIAN"). */
    public const INSTRUCTIONS = [
        'nama' => "Maksimal 40 karakter.\nMandatory input.\nDiisi nama lengkap sesuai KTP tanpa gelar dan tanpa angka.\nKarakter khusus yang boleh: . , ' \" ( ) / - dan spasi.",
        'gelarsbl' => "Maksimal 15 karakter.\nOptional (dapat dikosongkan).\nGelar sebelum nama.",
        'gelarsdh' => "Maksimal 15 karakter.\nOptional (dapat dikosongkan).\nGelar setelah nama.",
        'kelamin' => "1 karakter.\nMandatory input.\nM : Male\nF : Female",
        'tgl_lhr' => "8 digit.\nMandatory input.\nFormat DDMMYYYY tanpa tanda baca \"-\", \"/\" dll.\nTidak boleh lebih dari hari ini.",
        'kota_lhr' => "Maksimal 30 karakter, minimal 3 karakter.\nMandatory input.\nDiisi kota lahir.",
        'no_ktp' => "16 digit angka.\nMandatory input.\nTidak diperkenankan: lebih/kurang dari 16 digit, 0 semua, 14 digit belakang 0 semua, diawali angka 0, kosong, atau mengandung karakter khusus.",
        'kota_ktp' => "Maksimal 30 karakter.\nMandatory input.\nDiisi kota diterbitkannya KTP.",
        'npwp' => "16 digit angka (sama dengan NIK).\nOptional (dapat dikosongkan).\nJika diisi harus 16 digit angka.",
        'ibu_kandung' => "Maksimal 25 karakter.\nMandatory input.\nDiisi nama ibu kandung.",
        'sts_kawin' => "Mandatory input.\nA : Kawin\nB : Belum Kawin\nC : Janda\nD : Duda",
        'alamat1' => "Maksimal 40 karakter.\nMandatory input.\nDiisi nama jalan dan nomor rumah.",
        'alamat2' => "Maksimal 40 karakter.\nMandatory input.\nDiisi RT/RW dengan format RT000 RW000.",
        'kodepos' => "Mandatory input.\nDiisi kode pos (5 digit).\nTidak boleh diisi 00000.",
        'tlp_rmh' => "Maksimal 12 digit, minimal 7 digit.\nConditional input.\nDapat dikosongkan jika nomor handphone telah terisi.\nTidak boleh 0000000 atau diisi \"tidak memiliki\".",
        'no_hp' => "Minimal 9 digit, maksimal 15 digit.\nConditional input.\nDapat dikosongkan jika nomor telp rumah telah terisi.\nAwali dengan kode negara 62 dan angka setelahnya tidak boleh 0.\nTidak boleh karakter khusus atau diisi \"tidak memiliki\".",
        'email' => "Maksimal 40 karakter.\nOptional.\nJika tidak memiliki email, dikosongkan.\nFormat email harus valid; tidak boleh tidak@memiliki.com.",
        'pekerjaan' => "Mandatory input.\n" . '__PEKERJAAN__',
        'jabatan' => "Mandatory input.\n2 digit.\n" . '__JABATAN__',
        'tgl_mulai' => "6 digit.\nMandatory input.\nDiisi tanggal mulai bekerja, format DDMMYY (tanpa tanda baca \"-\", \"/\" dll).",
        'gaji' => "Diisi jumlah penghasilan per bulan.\nMandatory input.\nTanpa karakter khusus.",
        'pen_lain' => "Diisi penghasilan tambahan.\nJika tidak ada maka diisi \"0\".\nTanpa karakter khusus.\nMandatory input.",
        'consent' => "4 karakter.\nMandatory input.\nDigit 1: aplikasi pesan instan, digit 2: panggilan suara, digit 3: SMS, digit 4: e-mail.\nY = bersedia, N = tidak bersedia (contoh: YNNY).",
    ];

    public static function instruction(string $key): string
    {
        $t = self::INSTRUCTIONS[$key] ?? '';
        if ($key === 'pekerjaan') {
            $lines = [];
            foreach (self::PEKERJAAN as $k => $v) {
                $lines[] = "$k : $v";
            }
            $t = str_replace('__PEKERJAAN__', implode("\n", $lines), $t);
        }
        if ($key === 'jabatan') {
            $lines = [];
            foreach (self::JABATAN as $k => $v) {
                $lines[] = "$k : $v";
            }
            $t = str_replace('__JABATAN__', implode("\n", $lines), $t);
        }
        return $t;
    }

    public static function consentString(array $flags): string
    {
        $s = '';
        foreach (array_keys(self::CONSENT_CHANNELS) as $k) {
            $s .= !empty($flags[$k]) ? 'Y' : 'N';
        }
        return $s;
    }
}
