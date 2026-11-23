<?php

namespace App\Services\Dukcapil;

/**
 * Simulasi database Dukcapil untuk prototype: membaca database/sample/dukcapil.json (data SINTETIS, dikunci per NIK).
 * Ganti dengan implementasi sungguhan (query DB / REST) lewat DUKCAPIL_DRIVER di .env.
 */
class FileDukcapilClient implements DukcapilClient
{
    /** @var array<string,array<string,string>>|null */
    private static ?array $index = null;

    public function find(string $nik): ?array
    {
        if (self::$index === null) {
            $path = database_path('sample/dukcapil.json');
            self::$index = [];
            if (is_file($path)) {
                foreach (json_decode(file_get_contents($path), true) ?: [] as $r) {
                    self::$index[(string)$r['nik']] = $r;
                }
            }
        }
        return self::$index[$nik] ?? null;
    }
}
