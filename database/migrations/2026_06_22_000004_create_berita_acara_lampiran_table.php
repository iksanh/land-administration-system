<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampiran dokumentasi (foto) pemeriksaan lapang untuk sebuah Berita Acara.
 * File disimpan di disk `public` (storage/app/public/berita-acara).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acara_lampiran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('berita_acara_id');
            $table->string('path', 255);
            $table->string('keterangan', 255)->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('berita_acara_id')->references('id')->on('berita_acara_pemeriksaan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acara_lampiran');
    }
};
