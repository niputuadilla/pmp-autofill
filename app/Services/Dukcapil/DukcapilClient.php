<?php

namespace App\Services\Dukcapil;

interface DukcapilClient
{
    /**
     * Cari data kependudukan berdasarkan NIK (16 digit).
     *
     * @return array<string,string>|null null bila NIK tidak ditemukan. Kunci yang dipakai aplikasi:
     *   nik, nama, tempat_lahir, tanggal_lahir (YYYY-MM-DD), jenis_kelamin (LAKI-LAKI/PEREMPUAN atau L/P),
     *   nama_ibu, alamat (jalan + nomor), rt, rw, kelurahan, kecamatan, kota, kode_pos, status_kawin, pekerjaan
     *
     * @throws \RuntimeException bila layanan Dukcapil tidak bisa dihubungi (beda dengan "tidak ditemukan")
     */
    public function find(string $nik): ?array;
}
