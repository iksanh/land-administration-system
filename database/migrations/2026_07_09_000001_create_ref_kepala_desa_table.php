<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar kepala desa per desa (banyak per desa; ada yang aktif / non-aktif).
 * Kepala desa AKTIF otomatis ikut sebagai penandatangan pada Berita Acara &
 * Risalah untuk permohonan yang tanahnya berada di desa bersangkutan.
 * Kolom lama ref_desa.nama_kepala_desa tetap dipertahankan (kompatibilitas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_kepala_desa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('desa_id', 10);
            $table->string('nama', 150);
            $table->string('nip', 30)->nullable();
            $table->string('jabatan', 100)->default('Kepala Desa');
            $table->string('periode', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('desa_id')->references('id')->on('ref_desa')->onDelete('cascade');
            $table->index(['desa_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_kepala_desa');
    }
};
