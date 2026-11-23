<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkItem extends Model
{
    protected $guarded = [];

    protected $casts = ['data' => 'array'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(BulkRequest::class, 'bulk_request_id');
    }
}
