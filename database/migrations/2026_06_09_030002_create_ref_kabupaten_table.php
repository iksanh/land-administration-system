<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_kabupaten', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->string('provinsi_id', 10);
            $table->string('nama', 100);

            $table->foreign('provinsi_id')->references('id')->on('ref_provinsi')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_kabupaten');
    }
};
