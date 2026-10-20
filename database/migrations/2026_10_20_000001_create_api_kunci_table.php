<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci API sinkron (dipakai aplikasi BA Risalah / SIPANTAS) yang dikelola
 * admin lewat /kunci-api. Kunci asli hanya ditampilkan sekali saat dibuat;
 * yang disimpan cuma hash SHA-256-nya (`kunci_hash`) + awalan pendek
 * (`awalan`) supaya admin bisa mengenali kunci mana yang mana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_kunci', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 100);
            $table->char('kunci_hash', 64)->unique();
            $table->string('awalan', 12);
            $table->boolean('is_active')->default(true);
            $table->timestamp('terakhir_dipakai_at')->nullable();
            $table->uuid('dibuat_oleh')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('dibuat_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_kunci');
    }
};
