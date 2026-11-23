<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkRequest extends Model
{
    protected $guarded = [];

    public const WAITING = 'Menunggu BOM';
    public const APPROVED = 'Disetujui BOM';
    public const REJECTED = 'Ditolak BOM';

    protected $casts = ['mitra' => 'array', 'approved_at' => 'datetime'];

    public function isWaiting(): bool { return $this->status === self::WAITING; }
    public function isApproved(): bool { return $this->status === self::APPROVED; }

    /** Request ID baru "terbit" setelah disetujui BOM; sebelumnya ditampilkan "-". */
    public function displayRequestId(): string
    {
        return $this->isApproved() ? $this->request_id : '-';
    }

    /** Status verifikasi data nasabah terhadap Dukcapil (dilakukan otomatis saat submit). */
    public function dataStatus(): string
    {
        return $this->dk_verified >= $this->jumlah ? 'Terverifikasi' : "Terverifikasi {$this->dk_verified}/{$this->jumlah}";
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkItem::class);
    }
}
