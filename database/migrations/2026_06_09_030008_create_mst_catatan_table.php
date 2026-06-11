<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_catatan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('teks');
            // NULL = global note, set = note specific to a berkas item.
            $table->uuid('berkas_item_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('berkas_item_id')->references('id')->on('mst_berkas_item')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_catatan');
    }
};
