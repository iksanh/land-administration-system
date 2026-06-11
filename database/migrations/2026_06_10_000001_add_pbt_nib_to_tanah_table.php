<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the surat-ukur / parcel-identity fields to `tanah`:
 *  - nomor_pbt   : Nomor Peta Bidang Tanah (surat ukur)
 *  - tanggal_pbt : tanggal terbit PBT
 *  - nib         : Nomor Identifikasi Bidang
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            $table->string('nomor_pbt', 100)->nullable()->after('penggunaan_tanah');
            $table->date('tanggal_pbt')->nullable()->after('nomor_pbt');
            $table->string('nib', 100)->nullable()->after('tanggal_pbt');
        });
    }

    public function down(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            $table->dropColumn(['nomor_pbt', 'tanggal_pbt', 'nib']);
        });
    }
};
