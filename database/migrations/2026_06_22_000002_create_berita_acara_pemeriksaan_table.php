<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berita Acara Pemeriksaan Lapang (BAPL) oleh Panitia Pemeriksa Tanah A.
 * Satu permohonan = satu berita acara (permohonan_id unik). Sebagian besar data
 * teknis (luas, PBT, NIB, batas, penggunaan) diambil dari tabel `tanah`; tabel
 * ini hanya menyimpan field khusus berita acara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acara_pemeriksaan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permohonan_id')->unique();
            $table->string('nomor_ba', 100)->nullable();
            $table->date('tgl_pemeriksaan')->nullable();
            // Daftar poin riwayat penguasaan (array string), dibaca via cast 'array'.
            $table->json('riwayat_penguasaan')->nullable();
            $table->text('keadaan_tanah')->nullable();
            $table->text('catatan_keberatan')->nullable();
            $table->string('perda_rtrw', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('permohonan_id')->references('id')->on('permohonan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acara_pemeriksaan');
    }
};
