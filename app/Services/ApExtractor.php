<?php

namespace App\Services;

interface ApExtractor
{
    /**
     * Baca satu PDF scan APR (Aplikasi Pembukaan Rekening) milik satu nasabah.
     *
     * @return array{raw: array<string,mixed>, uncertain: array<int,string>}
     *   raw       = field mentah (key lihat ClaudeExtractor::PROMPT)
     *   uncertain = daftar key raw yang diragukan oleh pembaca
     */
    public function extract(string $pdfBytes, string $filename): array;
}
