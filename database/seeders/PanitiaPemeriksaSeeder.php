<?php

namespace Database\Seeders;

use App\Models\PanitiaPemeriksa;
use Illuminate\Database\Seeder;

/**
 * Anggota tetap Panitia Pemeriksa Tanah A (sesuai contoh Berita Acara).
 * Idempoten: aman dijalankan ulang. Kepala Desa ditambahkan per-permohonan
 * lewat aplikasi karena berbeda tiap desa.
 */
class PanitiaPemeriksaSeeder extends Seeder
{
    public function run(): void
    {
        $anggota = [
            ['nama' => 'YUDHI SATRIA PULO, S.H., M.H.', 'jabatan' => 'Kepala Seksi Penetapan Hak dan Pendaftaran Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'KETUA', 'urutan' => 1],
            ['nama' => 'ALPIUS PANAMBE, S.SiT', 'jabatan' => 'Kepala Seksi Survei dan Pemetaan Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'ANGGOTA', 'urutan' => 2],
            ['nama' => 'ICHSANDY MASLOMAN, S.H', 'jabatan' => 'Kepala Seksi Penataan dan Pemberdayaan Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'ANGGOTA', 'urutan' => 3],
            ['nama' => 'SRI BINTANG PAMUNGKASLARA, S.Si', 'jabatan' => 'Penata Pertanahan Pertama Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'SEKRETARIS', 'urutan' => 4],
        ];

        foreach ($anggota as $row) {
            PanitiaPemeriksa::firstOrCreate(['nama' => $row['nama']], $row);
        }
    }
}
