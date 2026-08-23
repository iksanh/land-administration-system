<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Berita Acara & Risalah menyimpan SK panitia yang dipakai saat dokumen dibuat
 * (snapshot). Mengganti SK aktif karena itu tidak mengubah dokumen lama —
 * dokumen tetap mencetak susunan panitia sesuai SK-nya sendiri.
 */
return new class extends Migration
{
    private const TABEL = [
        'berita_acara_pemeriksaan' => ['pivot' => 'berita_acara_panitia', 'fk' => 'berita_acara_id', 'after' => 'tgl_pemeriksaan'],
        'risalah_panitia_a' => ['pivot' => 'risalah_panitia', 'fk' => 'risalah_id', 'after' => 'tgl_sk_panitia'],
    ];

    public function up(): void
    {
        foreach (self::TABEL as $nama => $meta) {
            Schema::table($nama, function (Blueprint $table) use ($meta) {
                $table->uuid('sk_panitia_id')->nullable()->after($meta['after']);
                $table->foreign('sk_panitia_id')->references('id')->on('sk_panitia')->onDelete('set null');
            });

            // Backfill: ambil SK dari anggota panitia yang sudah menandatangani.
            $sumber = DB::table($meta['pivot'])
                ->join('panitia_pemeriksa', 'panitia_pemeriksa.id', '=', $meta['pivot'].'.panitia_id')
                ->whereNotNull('panitia_pemeriksa.sk_panitia_id')
                ->select($meta['pivot'].'.'.$meta['fk'].' as dokumen_id', 'panitia_pemeriksa.sk_panitia_id')
                ->distinct()
                ->get();

            foreach ($sumber as $row) {
                DB::table($nama)->where('id', $row->dokumen_id)
                    ->whereNull('sk_panitia_id')
                    ->update(['sk_panitia_id' => $row->sk_panitia_id]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABEL) as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->dropForeign(['sk_panitia_id']);
                $table->dropColumn('sk_panitia_id');
            });
        }
    }
};
