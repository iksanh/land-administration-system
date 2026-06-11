<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanah', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pemohon_id')->nullable();
            $table->string('desa_id', 10)->nullable();
            $table->decimal('luas', 12, 2)->nullable();
            $table->decimal('luas_surat', 12, 2)->nullable();
            $table->string('penggunaan_tanah', 200)->nullable();
            $table->text('batas_utara')->nullable();
            $table->text('batas_timur')->nullable();
            $table->text('batas_selatan')->nullable();
            $table->text('batas_barat')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pemohon_id')->references('id')->on('pemohon')->onDelete('restrict');
            $table->foreign('desa_id')->references('id')->on('ref_desa')->onDelete('restrict');
        });

        // CHECK constraints from the source model.
        DB::statement('ALTER TABLE tanah ADD CONSTRAINT check_luas_positif CHECK (luas > 0)');
        DB::statement('ALTER TABLE tanah ADD CONSTRAINT check_luas_surat_positif CHECK (luas_surat > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tanah');
    }
};
