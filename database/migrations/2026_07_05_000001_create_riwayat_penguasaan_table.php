<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Riwayat penguasaan tanah — daftar poin naratif riwayat penguasaan sebuah
 * bidang tanah, disimpan per-permohonan agar dokumen yang dibuat untuk
 * permohonan yang sama (Berita Acara Pemeriksaan Lapang, Risalah, SK) berbagi
 * teks yang identik. Sebelumnya kolom `riwayat_penguasaan` menempel pada
 * `berita_acara_pemeriksaan`; di sini datanya dipindah ke tabel tersendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_penguasaan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permohonan_id')->unique();
            // Daftar poin riwayat penguasaan (array string terurut), dibaca via cast 'array'.
            $table->json('poin')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('permohonan_id')->references('id')->on('permohonan')->onDelete('cascade');
        });

        // Pindahkan data lama dari berita_acara_pemeriksaan ke tabel baru.
        if (Schema::hasColumn('berita_acara_pemeriksaan', 'riwayat_penguasaan')) {
            DB::table('berita_acara_pemeriksaan')
                ->whereNotNull('riwayat_penguasaan')
                ->select('permohonan_id', 'riwayat_penguasaan')
                ->orderBy('permohonan_id')
                ->each(function ($ba) {
                    DB::table('riwayat_penguasaan')->insert([
                        'id' => (string) Str::uuid(),
                        'permohonan_id' => $ba->permohonan_id,
                        'poin' => $ba->riwayat_penguasaan, // sudah berupa JSON string
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            Schema::table('berita_acara_pemeriksaan', function (Blueprint $table) {
                $table->dropColumn('riwayat_penguasaan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('berita_acara_pemeriksaan', function (Blueprint $table) {
            $table->json('riwayat_penguasaan')->nullable()->after('tgl_pemeriksaan');
        });

        // Salin balik data ke kolom lama sebelum tabel dibuang.
        DB::table('riwayat_penguasaan')->orderBy('permohonan_id')->each(function ($rp) {
            DB::table('berita_acara_pemeriksaan')
                ->where('permohonan_id', $rp->permohonan_id)
                ->update(['riwayat_penguasaan' => $rp->poin]);
        });

        Schema::dropIfExists('riwayat_penguasaan');
    }
};
