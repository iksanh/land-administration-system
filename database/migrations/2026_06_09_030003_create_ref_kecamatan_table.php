<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_kecamatan', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('kabupaten_id', 10);
            $table->string('nama', 100);

            $table->foreign('kabupaten_id')->references('id')->on('ref_kabupaten')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_kecamatan');
    }
};
