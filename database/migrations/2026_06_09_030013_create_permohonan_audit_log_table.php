<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('permohonan_id')->nullable();
            $table->string('status_sebelumnya', 30)->nullable();
            $table->string('status_baru', 30);
            $table->uuid('petugas_id')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('permohonan_id')->references('id')->on('permohonan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_audit_log');
    }
};
