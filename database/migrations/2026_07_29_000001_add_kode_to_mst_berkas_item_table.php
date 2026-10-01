<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_berkas_item', function (Blueprint $table) {
            // Kode singkat berkas (mis. SPPF, KTP, KK) — dipakai untuk mencocokkan
            // nama file saat unggah massal di Pemeriksaan Berkas.
            $table->string('kode', 20)->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('mst_berkas_item', function (Blueprint $table) {
            $table->dropColumn('kode');
        });
    }
};
