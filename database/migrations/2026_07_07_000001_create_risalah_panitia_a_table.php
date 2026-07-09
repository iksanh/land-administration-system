<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risalah Panitia Pemeriksaan Tanah "A" — dokumen telaah lengkap (superset dari
 * Berita Acara Pemeriksaan Lapang). Satu permohonan = satu risalah
 * (permohonan_id unik). Sebagian besar narasi di-render otomatis dari
 * `pemohon`, `tanah`, dan `riwayat_penguasaan` (dipakai ulang bersama Berita
 * Acara); tabel ini hanya menyimpan field khusus risalah: nomor/tanggal, dasar
 * hukum, data pendukung, dan referensi SK panitia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risalah_panitia_a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permohonan_id')->unique();
            $table->string('nomor_risalah', 100)->nullable();
            $table->date('tgl_risalah')->nullable();
            $table->string('jenis_hak', 100)->default('Hak Milik');
            $table->string('jangka_waktu', 100)->nullable();
            // SK Kepala Kantor pembentukan Panitia "A".
            $table->string('nomor_sk_panitia', 150)->nullable();
            $table->date('tgl_sk_panitia')->nullable();
            // Kawasan RTRW hasil Peta Analisis, mis. "Kawasan Permukiman Perdesaan".
            $table->string('rtrw_kawasan', 200)->nullable();
            $table->string('perda_rtrw', 255)->nullable();
            // Tanggal Berita Acara Pemeriksaan Lapang yang dirujuk pada data pendukung.
            $table->date('tgl_bap')->nullable();
            // Daftar terurut (array string), dibaca via cast 'array'.
            $table->json('data_pendukung')->nullable();
            $table->json('dasar_hukum')->nullable();
            // Catatan tambahan pada bagian kesimpulan (opsional).
            $table->text('kesimpulan_tambahan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('permohonan_id')->references('id')->on('permohonan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risalah_panitia_a');
    }
};
