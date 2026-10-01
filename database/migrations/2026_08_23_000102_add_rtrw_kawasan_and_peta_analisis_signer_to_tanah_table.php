<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi data Peta Analisis Penatagunaan Tanah agar Berita Acara Pemeriksaan
 * Lapang dapat dicetak utuh sesuai dokumen resmi:
 *
 *  - rtrw_kawasan            : kawasan menurut RTRW ("Kawasan Permukiman Perkotaan").
 *                              Berbeda dari `rencana_penggunaan_rtrw` ("Non Pertanian").
 *  - pejabat_peta_analisis   : pejabat penanda tangan peta analisis (Kepala Seksi
 *                              Penataan dan Pemberdayaan) — bisa berbeda dari anggota
 *                              panitia yang sedang menjabat.
 *  - nip_peta_analisis       : NIP pejabat tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            $table->string('rtrw_kawasan', 200)->nullable()->after('kesesuaian_penggunaan_tanah');
            $table->string('pejabat_peta_analisis', 200)->nullable()->after('rtrw_kawasan');
            $table->string('nip_peta_analisis', 30)->nullable()->after('pejabat_peta_analisis');
        });
    }

    public function down(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            $table->dropColumn(['rtrw_kawasan', 'pejabat_peta_analisis', 'nip_peta_analisis']);
        });
    }
};
