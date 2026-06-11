<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_layanan_berkas', function (Blueprint $table) {
            $table->uuid('layanan_id');
            $table->uuid('berkas_item_id');
            $table->integer('urutan');

            $table->primary(['layanan_id', 'berkas_item_id']);
            $table->foreign('layanan_id')->references('id')->on('mst_layanan')->onDelete('cascade');
            $table->foreign('berkas_item_id')->references('id')->on('mst_berkas_item')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_layanan_berkas');
    }
};
