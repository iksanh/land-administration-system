<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_provinsi', function (Blueprint $table) {
            $table->string('id', 2)->primary();
            $table->string('nama', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_provinsi');
    }
};
