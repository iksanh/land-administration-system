<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the land-use analysis fields to `tanah` (the existing `penggunaan_tanah`
 * column is reused as the current land use):
 *  - tgl_peta_analisis           : tanggal peta analisis
 *  - rencana_penggunaan_rtrw      : rencana penggunaan menurut RTRW
 *  - kesesuaian_penggunaan_tanah  : kesesuaian terhadap RTRW
 *  - penggunaan_tanah_sk          : penggunaan tanah sesuai SK
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            $table->date('tgl_peta_analisis')->nullable()->after('nib');
            $table->string('rencana_penggunaan_rtrw', 200)->nullable()->after('tgl_peta_analisis');
            $table->string('kesesuaian_penggunaan_tanah', 50)->nullable()->after('rencana_penggunaan_rtrw');
            $table->string('penggunaan_tanah_sk', 200)->nullable()->after('kesesuaian_penggunaan_tanah');
        });
    }

    public function down(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            $table->dropColumn([
                'tgl_peta_analisis',
                'rencana_penggunaan_rtrw',
                'kesesuaian_penggunaan_tanah',
                'penggunaan_tanah_sk',
            ]);
        });
    }
};
