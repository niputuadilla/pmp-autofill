<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_requests', function (Blueprint $t) {
            $t->unsignedInteger('dk_verified')->default(0);   // jumlah baris yang lolos verifikasi Dukcapil saat submit
            $t->timestamp('approved_at')->nullable();         // waktu Branch Operation Manager menyetujui
            $t->string('approved_by')->nullable();
            $t->text('reject_note')->nullable();
        });

        // request lama (status "Success") dianggap sudah disetujui
        DB::table('bulk_requests')->where('status', 'Success')->update([
            'status' => 'Disetujui BOM', 'approved_at' => DB::raw('created_at'), 'dk_verified' => DB::raw('jumlah'),
        ]);
    }

    public function down(): void
    {
        Schema::table('bulk_requests', function (Blueprint $t) {
            $t->dropColumn(['dk_verified', 'approved_at', 'approved_by', 'reject_note']);
        });
    }
};
