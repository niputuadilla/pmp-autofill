<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_requests', function (Blueprint $t) {
            $t->id();
            $t->string('request_id')->unique();
            $t->string('group_code');
            $t->string('branch');
            $t->string('mitra_id');
            $t->json('mitra');           // snapshot Informasi Mitra
            $t->string('consent', 4);    // contoh: YNNY
            $t->unsignedInteger('jumlah');
            $t->string('mekanisme')->default('Manual Bulk (Autofill APR)');
            $t->string('status')->default('Success');
            $t->timestamps();
        });

        Schema::create('bulk_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bulk_request_id')->constrained('bulk_requests')->cascadeOnDelete();
            $t->json('data');            // nilai kolom BAOWNITEMPLATE
            $t->string('original_name')->nullable();
            $t->string('file_path')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_items');
        Schema::dropIfExists('bulk_requests');
    }
};
