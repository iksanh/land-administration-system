<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master anggota Panitia Pemeriksa Tanah A (dipakai ulang antar Berita Acara
 * Pemeriksaan Lapang). `peran` disimpan sebagai string, di-enforce oleh enum cast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panitia_pemeriksa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 150);
            $table->string('nip', 30)->nullable();
            $table->string('jabatan', 200)->nullable();
            // peran_panitia_enum — KETUA / ANGGOTA / SEKRETARIS / KEPALA_DESA
            $table->string('peran', 20)->default('ANGGOTA');
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panitia_pemeriksa');
    }
};
