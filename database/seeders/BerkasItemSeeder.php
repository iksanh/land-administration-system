<?php

namespace Database\Seeders;

use App\Models\MstBerkasItem;
use Illuminate\Database\Seeder;

/**
 * Ports seed_berkas.py / import_data.py — the 36-row berkas verification
 * catalog with self-referential parents and per-item checking notes (catatan).
 * Idempotent by `nama` so it can coexist with LayananSeeder's berkas items.
 */
class BerkasItemSeeder extends Seeder
{
    public function run(): void
    {
        // [legacy_id, nama, catatan, parent_legacy_id]
        $rows = [
            [1, 'PBT (Peta Bidang Tanah)', "1.\tCek apakah PBT ada/tidak ada\n2.\tCek Luas PBT dengan surat tanah, apabila terdapat indikasi bidang dipecah-pecah, mintakan PBT yang baru hasil gabungan bidang.\n3.\tCek Nama Pemohon di PBT, nama pemohon di PBT harus sama dengan nama pemohon di Permohonan SK.", null],
            [2, 'Peta Analisis', "1.\tCek apakah Peta Analisis ada/tidak ada\n2.\tCek Bidang tanah masuk di pola ruang apa?\n3.\tApabila masuk sempadan sungai/pantai, berikan catatan rekomendasi\n4. Apabila sebagian masuk sempadan, berikan rekomendasi untuk potong bidang.", null],
            [3, 'Formulir Permohonan', 'Cek kelengkapan isian sesuai dengan peta bidang tanah (tgl, nomor, NIB, luas), cek tanggal formulir.', null],
            [4, 'Surat pernyataan penguasaan fisik bidang tanah', "Cek kelengkapan isian, status tanah harus diisi 'tanah negara'.", 3],
            [5, 'Surat pernyataan tanah-tanah yang dimiliki pemohon', 'Cek kelengkapan isian, cek tanggal surat.', 3],
            [6, 'Surat pernyataan penggunaan Tanah', 'Cek kelengkapan isian, cek tanggal surat.', 3],
            [7, 'Surat keterangan penguasaan Tanah', 'Cek kelengkapan isian, cek riwayat sampai tahun 1960.', 3],
            [8, 'Surat pernyataan', "Cek kelengkapan isian, status tanah harus diisi 'tanah negara'.", 3],
            [9, 'Surat pernyataan tidak dalam sengketa dan belum bersertipikat', 'Cek kelengkapan isian.', null],
            [10, 'Surat pernyataan pemasangan tanda batas dan persetujuan pemilik yang berbatasan', 'Cek kelengkapan isian, cek tanda-tangan tetangga batas, cek sketsa.', 3],
            [11, 'Surat Kuasa apabila dikuasakan', 'Cek apakah ada/tidak ada, apabila tidak ada mintakan kelengkapan.', null],
            [12, 'KTP Pemohon/KTP Pemberi Kuasa', 'Cek apakah ada/tidak ada, apabila tidak ada mintakan kelengkapan.', null],
            [13, 'KTP Penerima Kuasa', 'Cek apakah ada/tidak ada, apabila tidak ada mintakan kelengkapan.', null],
            [14, 'Kartu Keluarga', 'Cek apakah ada/tidak ada, apabila tidak ada mintakan kelengkapan.', null],
            [15, 'SPPT/PBB tahun berjalan', 'Cek apakah ada/tidak ada, PBB harus tahun berjalan dan atas nama pemohon.', null],
            [16, 'Validasi PPH', 'Cek apakah ada/tidak ada, Cek NOP yang dipakai dan nama yang membayar PPh.', null],
            [17, 'Surat Keterangan Waris', "1.\tCek Surat Keterangan Waris dan Surat Pernyataan Pelepasan Waris.\n2.\tCek ttd semua ahli waris, saksi, kepala desa, dan camat.\n3.\tCek Surat Pernyataan Pelepasan/Pembagian Waris.", 36],
            [18, 'Surat Tanah - Surat Pernyataan Pelepasan Waris', '', null],
            [19, 'Surat Keterangan/Pernyataan Jual Beli', "1.\tCek kelengkapan surat (nama penjual dan pembeli, letak, luas, batas-batas).\n2.\tApabila dilampirkan kuitansi, mintakan surat jual beli.", 36],
            [20, 'Surat Tanah - Surat Pernyataan Penyerahan Hak Atas Tanah', '', null],
            [21, 'Surat Tanah - Surat Pernyataan Hibah (Pemberian)', 'Cek kelengkapan surat hibah (nama pemberi dan penerima, letak, luas, batas-batas).', null],
            [22, 'Surat Lain Lain', "1.\tMintakan surat pemilik tanah sebelumnya sampai riwayat 20 tahun.\n2.\tCek KTP pemohon, cek pekerjaan apabila PNS.", 36],
            [23, 'Surat Keterangan Beda Nama', 'Mintakan Surat Keterangan Beda Nama apabila terdapat beda nama antara KTP dengan Surat tanah.', null],
            [24, 'Surat Keterangan dari desa batas-batas tanah dahulu dan sekarang', 'Cek kelengkapan isian surat dan pengisian batas-batas dahulu dan sekarang.', null],
            [25, 'Surat Pernyataan Absentee', '', null],
            [26, 'Surat Pernyataan Perbedaan Luas', 'Mintakan kelengkapan surat apabila terdapat beda luas antara surat tanah dengan Peta Bidang Tanah.', null],
            [27, 'Dokumen Lain-lain', '', null],
            [28, 'KKPR', 'Belum Ada', null],
            [29, 'Akta Pendirian Badan Hukum', '', null],
            [30, 'Surat Pengesahan Badan Hukum', '', null],
            [31, 'Nomor Induk Berusaha', '', null],
            [32, 'Surat Tanah - Surat Keterangan Penguasaan Tanah', '1. Cek riwayat tanah sampai tahun 1960.', null],
            [33, 'Kuitansi Jual Beli', '', 36],
            [34, 'Surat Tanah - Surat Keterangan/Pernyataan Jual Beli', 'Cek kelengkapan surat jual beli.', null],
            [35, 'Surat Pernyataan Kepemilikan Tanah', "1. Cek kesesuaian materai\n2. Cek kelengkapan tanda-tangan\n3. Cek kesesuaian nomor regis.", 36],
            [36, 'Alas Hak yang dilampirkan', '', null],
        ];

        // Pass 1: create all rows (no parent yet), keep legacy_id → model.
        $byLegacyId = [];
        foreach ($rows as [$legacyId, $nama, $catatan, $parentLegacyId]) {
            $byLegacyId[$legacyId] = MstBerkasItem::firstOrCreate(
                ['nama' => $nama],
                ['is_mandatory' => true, 'catatan' => $catatan !== '' ? $catatan : null],
            );
        }

        // Pass 2: wire up parents now that every row has a UUID.
        foreach ($rows as [$legacyId, $nama, $catatan, $parentLegacyId]) {
            if ($parentLegacyId !== null && isset($byLegacyId[$parentLegacyId])) {
                $item = $byLegacyId[$legacyId];
                $item->parent_id = $byLegacyId[$parentLegacyId]->id;
                $item->save();
            }
        }
    }
}
