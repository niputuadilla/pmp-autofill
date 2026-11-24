<?php

namespace App\Services;

use App\Services\Dukcapil\DukcapilClient;
use Throwable;

/**
 * Satu pintu untuk "olah satu baris": lookup Dukcapil berdasarkan NIK, lengkapi kolom kosong,
 * verifikasi kolom terisi, lalu validasi aturan template.
 * Dipakai saat ekstraksi PDF dan setiap kali user mengedit sel (termasuk saat NIK dikoreksi).
 */
class RowEnricher
{
    public function __construct(private DukcapilClient $dukcapil, private PostalCodeLookup $kodepos) {}

    /**
     * @param array<string,mixed> $row nilai kolom (boleh memuat '_warnings')
     * @param array<int,string> $uncertainCols kolom yang tulisannya diragukan pembaca APR
     * @return array{values:array,errors:array,warnings:array,suggest:array,dk:string}
     *   dk: found | notfound | error | skip (NIK belum 16 digit)
     */
    public function process(array $row, array $uncertainCols = []): array
    {
        $warn = $row['_warnings'] ?? [];
        $suggest = [];
        $dk = 'skip';
        $dkMsg = '';
        $addr = (array)($row['_addr'] ?? []); // kelurahan/kecamatan/kota dari APR (tidak ada di kolom template)

        $nik = preg_replace('/\D/', '', (string)($row['no_ktp'] ?? ''));
        if (strlen($nik) === 16) {
            try {
                $rec = $this->dukcapil->find($nik);
                if ($rec) {
                    $dk = 'found';
                    // alamat Dukcapil dianggap lebih akurat daripada tulisan di APR
                    $addr = array_filter([
                        'kelurahan' => $rec['kelurahan'] ?? '', 'kecamatan' => $rec['kecamatan'] ?? '', 'kota' => $rec['kota'] ?? '',
                    ]) + $addr;
                    $suggest = ApMapper::mergeDukcapil($row, ApMapper::dukcapilToColumns($rec), $uncertainCols, $warn);
                } else {
                    $dk = 'notfound';
                    $dkMsg = 'NIK tidak ditemukan di Dukcapil';
                }
            } catch (Throwable $e) {
                $dk = 'error';
                $dkMsg = 'Dukcapil tidak bisa dihubungi: ' . $e->getMessage();
            }
        }

        $nikOk = strlen($nik) === 16;
        if (array_filter($addr)) $this->fillKodepos($row, $addr, $suggest, $warn);

        $row['_warnings'] = $warn;
        $v = RowValidator::validate($row);

        // NIK belum ada: kolom yang menunggu Dukcapil tidak ditandai merah, cukup "Menunggu NIK"; kesalahan NIK tetap merah
        if (!$nikOk) {
            foreach (ApMapper::holdColumns() as $c) {
                if (trim((string)($v['values'][$c] ?? '')) === '' && isset($v['errors'][$c])) {
                    unset($v['errors'][$c]);
                    $v['warnings'][$c] = 'Menunggu NIK, akan diisi dari Dukcapil';
                }
            }
            if (isset($v['errors']['no_ktp']) && $nik === '') $v['errors']['no_ktp'] = 'NIK tidak terbaca. Isi NIK agar data dilengkapi dari Dukcapil';
        }

        if ($dkMsg !== '') {
            $required = !function_exists('config') || config('pmp.dukcapil.required', true);
            if ($required && $dk === 'notfound') {
                $v['errors']['no_ktp'] = $v['errors']['no_ktp'] ?? $dkMsg;
            } else {
                $v['warnings']['no_ktp'] = $dkMsg;
            }
        }

        return ['values' => $v['values'], 'errors' => $v['errors'], 'warnings' => $v['warnings'], 'suggest' => $suggest, 'dk' => $dk];
    }

    /**
     * Kode pos kosong -> cari dari kelurahan/kecamatan/kota. Terisi tapi beda dengan hasil pencarian -> beri saran.
     */
    private function fillKodepos(array &$row, array $addr, array &$suggest, array &$warn): void
    {
        $found = $this->kodepos->find((string)($addr['kelurahan'] ?? ''), (string)($addr['kecamatan'] ?? ''), (string)($addr['kota'] ?? ''));
        $cur = preg_replace('/\D/', '', (string)($row['kodepos'] ?? ''));

        if ($cur === '' || $cur === '00000') {
            if ($found) {
                $row['kodepos'] = $found;
                $warn['kodepos'] = 'Dicari otomatis dari alamat (kel. ' . trim((string)($addr['kelurahan'] ?? '')) . ', kec. ' . trim((string)($addr['kecamatan'] ?? '')) . ')';
            } else {
                $warn['kodepos'] = 'Kode pos kosong dan tidak ketemu dari alamat, isi manual';
            }
        } elseif ($found && $found !== $cur) {
            $warn['kodepos'] = 'Beda dengan hasil cari alamat: ' . $found;
            $suggest['kodepos'] = $found;
        }
    }
}
