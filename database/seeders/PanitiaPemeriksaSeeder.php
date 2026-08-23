<?php

namespace Database\Seeders;

use App\Models\SkPanitia;
use Illuminate\Database\Seeder;

/**
 * SK pembentukan Panitia Pemeriksa Tanah A beserta anggota tetapnya (sesuai
 * contoh Berita Acara). Idempoten: aman dijalankan ulang. Kepala Desa
 * ditambahkan per-permohonan lewat aplikasi karena berbeda tiap desa.
 */
class PanitiaPemeriksaSeeder extends Seeder
{
    public function run(): void
    {
        $sk = SkPanitia::firstOrCreate(
            ['nomor' => 'SK Panitia "A" — contoh'],
            [
                'tentang' => 'Pembentukan Panitia Pemeriksaan Tanah "A" Kantor Pertanahan Kabupaten Bone Bolango',
                'is_active' => ! SkPanitia::where('is_active', true)->exists(),
            ],
        );

        $anggota = [
            ['nama' => 'YUDHI SATRIA PULO, S.H., M.H.', 'jabatan' => 'Kepala Seksi Penetapan Hak dan Pendaftaran Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'KETUA', 'urutan' => 1],
            ['nama' => 'ALPIUS PANAMBE, S.SiT', 'jabatan' => 'Kepala Seksi Survei dan Pemetaan Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'ANGGOTA', 'urutan' => 2],
            ['nama' => 'ICHSANDY MASLOMAN, S.H', 'jabatan' => 'Kepala Seksi Penataan dan Pemberdayaan Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'ANGGOTA', 'urutan' => 3],
            ['nama' => 'SRI BINTANG PAMUNGKASLARA, S.Si', 'jabatan' => 'Penata Pertanahan Pertama Kantor Pertanahan Kabupaten Bone Bolango', 'peran' => 'SEKRETARIS', 'urutan' => 4],
        ];

        foreach ($anggota as $row) {
            $sk->anggota()->firstOrCreate(['nama' => $row['nama']], $row);
        }
    }
}
