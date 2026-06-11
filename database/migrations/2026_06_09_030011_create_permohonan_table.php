<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_registrasi', 50)->unique();
            $table->uuid('pemohon_id')->nullable();
            $table->uuid('tanah_id')->nullable();
            $table->uuid('layanan_id')->nullable();
            // permohonan_status_enum — stored as string; enforced by enum cast + validation.
            $table->string('status', 30)->default('DRAFT');
            $table->date('tgl_pendaftaran')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('pemohon_id')->references('id')->on('pemohon')->onDelete('restrict');
            $table->foreign('tanah_id')->references('id')->on('tanah')->onDelete('restrict');
            $table->foreign('layanan_id')->references('id')->on('mst_layanan')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan');
    }
};
