<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat Keputusan pembentukan Panitia Pemeriksa Tanah "A". Satu SK memayungi
 * satu susunan anggota (lihat `panitia_pemeriksa.sk_panitia_id`); hanya satu SK
 * boleh `is_active` pada satu waktu — SK aktif inilah yang otomatis dipakai
 * saat menyusun Berita Acara & Risalah, sehingga nomor SK dan daftar anggota
 * yang tercetak selalu berasal dari sumber yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sk_panitia', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor', 150);
            $table->date('tanggal')->nullable();
            $table->text('tentang')->nullable();
            $table->date('berlaku_mulai')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sk_panitia');
    }
};
