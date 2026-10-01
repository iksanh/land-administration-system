<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // File lampiran hasil unggah untuk pemeriksaan berkas. Satu berkas bisa
        // punya banyak file, maka tabel terpisah (1:banyak). File diikat ke
        // (permohonan_id, berkas_item_id) — BUKAN ke baris pemeriksaan_berkas —
        // agar mengembalikan status ke PENDING (yang menghapus baris pemeriksaan)
        // tidak ikut menghapus file yang sudah diunggah.
        Schema::create('pemeriksaan_berkas_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permohonan_id');
            $table->uuid('berkas_item_id');
            $table->text('file_path');
            $table->string('nama_asli');
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->string('mime', 100)->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['permohonan_id', 'berkas_item_id'], 'idx_pemeriksaan_file_permohonan_berkas');
            $table->foreign('permohonan_id')->references('id')->on('permohonan')->onDelete('cascade');
            $table->foreign('berkas_item_id')->references('id')->on('mst_berkas_item')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_berkas_file');
    }
};
