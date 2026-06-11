<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_desa', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('kecamatan_id', 10);
            $table->string('nama', 100);
            $table->string('nama_kepala_desa', 200)->nullable();

            $table->foreign('kecamatan_id')->references('id')->on('ref_kecamatan')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_desa');
    }
};
