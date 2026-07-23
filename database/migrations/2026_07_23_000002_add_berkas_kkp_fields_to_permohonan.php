<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas berkas pada aplikasi KKP — wajib diisi saat alur status naik
 * dari TERDAFTAR ke KONSEP_RPD_BA_SK_STAF (gerbang di
 * ManagePermohonan::advanceStatus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->string('nomor_berkas', 50)->nullable()->after('nomor_registrasi');
            $table->unsignedSmallInteger('tahun_berkas')->nullable()->after('nomor_berkas');
            $table->date('tanggal_daftar_kkp')->nullable()->after('tahun_berkas');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropColumn(['nomor_berkas', 'tahun_berkas', 'tanggal_daftar_kkp']);
        });
    }
};
