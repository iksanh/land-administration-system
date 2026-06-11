<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeriksaan_berkas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permohonan_id')->nullable();
            $table->uuid('berkas_item_id')->nullable();
            $table->uuid('petugas_id')->nullable();
            // pemeriksaan_status_enum — stored as string; enforced by enum cast + validation.
            $table->string('status', 20)->default('PENDING');
            // Array of {id, teks, is_custom}.
            $table->json('catatan')->nullable();
            $table->text('file_path')->nullable();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['permohonan_id', 'berkas_item_id'], 'uq_permohonan_berkas');
            $table->foreign('permohonan_id')->references('id')->on('permohonan')->onDelete('cascade');
            $table->foreign('berkas_item_id')->references('id')->on('mst_berkas_item')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_berkas');
    }
};
