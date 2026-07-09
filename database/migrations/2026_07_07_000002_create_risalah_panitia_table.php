<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot anggota Panitia "A" yang menandatangani sebuah Risalah, lengkap dengan
 * urutan tampil/tanda tangan dan teks pendapat masing-masing anggota (bagian
 * "PENDAPAT ANGGOTA PANITIA" pada risalah). Master anggota tetap memakai tabel
 * `panitia_pemeriksa` yang sama dengan Berita Acara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risalah_panitia', function (Blueprint $table) {
            $table->uuid('risalah_id');
            $table->uuid('panitia_id');
            $table->integer('urutan')->default(0);
            $table->text('pendapat')->nullable();

            $table->primary(['risalah_id', 'panitia_id']);
            $table->foreign('risalah_id')->references('id')->on('risalah_panitia_a')->onDelete('cascade');
            $table->foreign('panitia_id')->references('id')->on('panitia_pemeriksa')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risalah_panitia');
    }
};
