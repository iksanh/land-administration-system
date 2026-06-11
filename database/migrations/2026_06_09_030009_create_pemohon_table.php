<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemohon', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nik', 16)->unique();
            $table->string('nama', 200);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            // gender_enum (L/P) — stored as string; enforced by the PHP enum cast + validation.
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->text('alamat_detail')->nullable();
            $table->string('desa_id', 10)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('desa_id')->references('id')->on('ref_desa')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemohon');
    }
};
