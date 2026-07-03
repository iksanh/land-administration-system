<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemohon', function (Blueprint $table) {
            // jenis_pemohon_enum (diri_sendiri/dikuasakan) — stored as string,
            // enforced by the PHP enum cast + validation. Existing rows default
            // to diri_sendiri so the kuasa_* columns stay empty for them.
            $table->string('jenis_pemohon', 20)->default('diri_sendiri')->after('nama');

            // Penerima kuasa (the authorised proxy) — only filled when dikuasakan.
            $table->string('kuasa_nama', 200)->nullable()->after('desa_id');
            $table->string('kuasa_nik', 16)->nullable()->after('kuasa_nama');
            $table->string('kuasa_pekerjaan', 100)->nullable()->after('kuasa_nik');
            $table->string('kuasa_no_hp', 20)->nullable()->after('kuasa_pekerjaan');
            $table->text('kuasa_alamat')->nullable()->after('kuasa_no_hp');
            $table->string('kuasa_hubungan', 100)->nullable()->after('kuasa_alamat');
            // Surat kuasa (power-of-attorney letter) reference.
            $table->string('kuasa_no_surat', 100)->nullable()->after('kuasa_hubungan');
            $table->date('kuasa_tanggal_surat')->nullable()->after('kuasa_no_surat');
        });
    }

    public function down(): void
    {
        Schema::table('pemohon', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_pemohon',
                'kuasa_nama',
                'kuasa_nik',
                'kuasa_pekerjaan',
                'kuasa_no_hp',
                'kuasa_alamat',
                'kuasa_hubungan',
                'kuasa_no_surat',
                'kuasa_tanggal_surat',
            ]);
        });
    }
};
