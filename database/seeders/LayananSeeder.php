<?php

namespace Database\Seeders;

use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstLayanan;
use Illuminate\Database\Seeder;

/**
 * Ports app/db/seeder_layanan.py — 5 layanan, 12 berkas items, and the
 * layanan→berkas mappings (with urutan ordering).
 */
class LayananSeeder extends Seeder
{
    public function run(): void
    {
        $layanan = [
            ['SRT-HAK-MILIK', 'Sertifikasi Hak Milik', 'Layanan penerbitan sertifikat hak milik atas tanah'],
            ['SRT-HAK-GUNA', 'Sertifikasi Hak Guna Bangunan', 'Layanan penerbitan sertifikat hak guna bangunan'],
            ['UKUR-TANAH', 'Pengukuran Tanah', 'Layanan pengukuran dan pemetaan bidang tanah'],
            ['BALIK-NAMA', 'Balik Nama Sertifikat', 'Layanan balik nama kepemilikan sertifikat tanah'],
            ['PECAH-TANAH', 'Pemecahan Sertifikat Tanah', 'Layanan pemecahan sertifikat tanah menjadi beberapa bidang'],
        ];

        $layananByKode = [];
        foreach ($layanan as [$kode, $nama, $deskripsi]) {
            $layananByKode[$kode] = MstLayanan::firstOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'deskripsi' => $deskripsi, 'is_active' => true],
            );
        }

        $berkas = [
            ['KTP Pemohon', true], ['Kartu Keluarga', true], ['NPWP', false],
            ['Surat Permohonan', true], ['Bukti Pembayaran PBB', true], ['Surat Tanah / Girik', true],
            ['Akta Jual Beli', true], ['Surat Waris', false], ['IMB / PBG', false],
            ['Sertifikat Asli', true], ['Surat Kuasa (jika dikuasakan)', false], ['Peta Bidang Tanah', true],
        ];

        $berkasByNama = [];
        foreach ($berkas as [$nama, $mandatory]) {
            $berkasByNama[$nama] = MstBerkasItem::firstOrCreate(
                ['nama' => $nama],
                ['is_mandatory' => $mandatory],
            );
        }

        $mapping = [
            'SRT-HAK-MILIK' => [
                ['KTP Pemohon', 1], ['Kartu Keluarga', 2], ['NPWP', 3], ['Surat Permohonan', 4],
                ['Bukti Pembayaran PBB', 5], ['Surat Tanah / Girik', 6], ['Peta Bidang Tanah', 7],
                ['Surat Kuasa (jika dikuasakan)', 8],
            ],
            'SRT-HAK-GUNA' => [
                ['KTP Pemohon', 1], ['Kartu Keluarga', 2], ['NPWP', 3], ['Surat Permohonan', 4],
                ['Bukti Pembayaran PBB', 5], ['IMB / PBG', 6], ['Peta Bidang Tanah', 7],
                ['Surat Kuasa (jika dikuasakan)', 8],
            ],
            'UKUR-TANAH' => [
                ['KTP Pemohon', 1], ['Surat Permohonan', 2], ['Bukti Pembayaran PBB', 3],
                ['Surat Tanah / Girik', 4], ['Peta Bidang Tanah', 5],
            ],
            'BALIK-NAMA' => [
                ['KTP Pemohon', 1], ['Kartu Keluarga', 2], ['Surat Permohonan', 3], ['Akta Jual Beli', 4],
                ['Sertifikat Asli', 5], ['Bukti Pembayaran PBB', 6], ['Surat Kuasa (jika dikuasakan)', 7],
            ],
            'PECAH-TANAH' => [
                ['KTP Pemohon', 1], ['Kartu Keluarga', 2], ['Surat Permohonan', 3], ['Sertifikat Asli', 4],
                ['Bukti Pembayaran PBB', 5], ['Peta Bidang Tanah', 6], ['Surat Kuasa (jika dikuasakan)', 7],
            ],
        ];

        foreach ($mapping as $kode => $items) {
            $layananId = $layananByKode[$kode]->id;
            foreach ($items as [$namaBerkas, $urutan]) {
                $berkasId = $berkasByNama[$namaBerkas]->id;
                $exists = MapLayananBerkas::where('layanan_id', $layananId)
                    ->where('berkas_item_id', $berkasId)
                    ->exists();
                if (! $exists) {
                    MapLayananBerkas::create([
                        'layanan_id' => $layananId,
                        'berkas_item_id' => $berkasId,
                        'urutan' => $urutan,
                    ]);
                }
            }
        }
    }
}
