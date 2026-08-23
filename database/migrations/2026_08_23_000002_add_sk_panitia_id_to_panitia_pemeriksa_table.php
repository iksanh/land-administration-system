<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Anggota panitia kini dimiliki oleh sebuah SK. Baris lama (yang belum mengenal
 * konsep SK) dipindahkan ke satu "SK warisan" yang langsung diaktifkan, agar
 * Berita Acara & Risalah yang sudah ada tidak kehilangan penandatangannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panitia_pemeriksa', function (Blueprint $table) {
            $table->uuid('sk_panitia_id')->nullable()->after('id');
            $table->foreign('sk_panitia_id')->references('id')->on('sk_panitia')->onDelete('cascade');
        });

        if (DB::table('panitia_pemeriksa')->doesntExist()) {
            return;
        }

        $skId = (string) Str::uuid();
        DB::table('sk_panitia')->insert([
            'id' => $skId,
            'nomor' => 'SK Warisan (sebelum penataan SK)',
            'tanggal' => null,
            'tentang' => 'Susunan Panitia Pemeriksa Tanah "A" hasil migrasi data lama',
            'is_active' => true,
            'keterangan' => 'Dibuat otomatis saat migrasi. Silakan perbarui nomor & tanggal SK yang sebenarnya, atau buat SK baru lalu aktifkan.',
        ]);

        DB::table('panitia_pemeriksa')->whereNull('sk_panitia_id')->update(['sk_panitia_id' => $skId]);
    }

    public function down(): void
    {
        Schema::table('panitia_pemeriksa', function (Blueprint $table) {
            $table->dropForeign(['sk_panitia_id']);
            $table->dropColumn('sk_panitia_id');
        });
    }
};
