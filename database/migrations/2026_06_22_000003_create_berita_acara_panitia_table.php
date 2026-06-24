<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot: anggota panitia yang menandatangani sebuah Berita Acara, beserta
 * urutan tampil/tanda tangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acara_panitia', function (Blueprint $table) {
            $table->uuid('berita_acara_id');
            $table->uuid('panitia_id');
            $table->integer('urutan')->default(0);

            $table->primary(['berita_acara_id', 'panitia_id']);
            $table->foreign('berita_acara_id')->references('id')->on('berita_acara_pemeriksaan')->onDelete('cascade');
            $table->foreign('panitia_id')->references('id')->on('panitia_pemeriksa')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acara_panitia');
    }
};
