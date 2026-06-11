<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_berkas_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 255);
            $table->boolean('is_mandatory')->default(true);
            $table->text('catatan')->nullable();
            $table->uuid('parent_id')->nullable();
        });

        // Self-referential parent → cascade delete of sub-berkas. Added after
        // the table (with its PK) exists so the engine can resolve the reference.
        Schema::table('mst_berkas_item', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('mst_berkas_item')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_berkas_item');
    }
};
