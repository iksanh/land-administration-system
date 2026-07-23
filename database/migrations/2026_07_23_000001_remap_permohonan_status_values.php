<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alur status lama (DRAFT → SUBMITTED → … → SELESAI) diganti dengan alur
 * tahapan kantor (docs/gambar status berkas.png). Migrasi ini memetakan
 * nilai lama yang mungkin masih tersimpan ke padanan terdekatnya di alur
 * baru. DRAFT (Pra-Daftar) dan DITOLAK tetap dipakai.
 */
return new class extends Migration
{
    /** @var array<string, string> old status => new status */
    private array $map = [
        'SUBMITTED' => 'PERIKSA_BERKAS_STAF',
        'VERIFIKASI_BERKAS' => 'PERIKSA_BERKAS_KORSUB',
        'PENGUKURAN' => 'SU_EL',
        'PANITIA' => 'TURUN_PANITIA',
        'SK_TERBIT' => 'TTD_SK',
        'SELESAI' => 'LOKET_PENYERAHAN',
    ];

    public function up(): void
    {
        $this->remap($this->map);
    }

    public function down(): void
    {
        $this->remap(array_flip($this->map));
    }

    /** @param array<string, string> $map */
    private function remap(array $map): void
    {
        foreach ($map as $old => $new) {
            DB::table('permohonan')->where('status', $old)->update(['status' => $new]);
            DB::table('permohonan_audit_log')->where('status_sebelumnya', $old)->update(['status_sebelumnya' => $new]);
            DB::table('permohonan_audit_log')->where('status_baru', $old)->update(['status_baru' => $new]);
        }
    }
};
