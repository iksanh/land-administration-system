<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah setingkat desa bisa berupa Desa atau Kelurahan. Dokumen resmi menulis
 * sebutannya apa adanya ("terletak di Kelurahan Tumbihe") dan pemimpinnya pun
 * berbeda (Kepala Desa vs Lurah) — lihat docs/BERITA ACARA PEMERIKSAAN LAPANG
 * OLEH.pdf. Default DESA agar data lama tidak berubah artinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ref_desa', function (Blueprint $table) {
            // jenis_desa_enum — DESA / KELURAHAN
            $table->string('jenis', 10)->default('DESA')->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('ref_desa', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
