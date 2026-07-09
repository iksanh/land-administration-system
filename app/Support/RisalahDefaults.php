<?php

namespace App\Support;

/**
 * Nilai bawaan boilerplate untuk Risalah Panitia Pemeriksaan Tanah "A".
 * Dasar hukum bersifat standar (nyaris identik antar risalah) sehingga
 * dipra-isi otomatis saat membuat risalah baru; petugas tinggal menyesuaikan.
 */
class RisalahDefaults
{
    public const PERDA_RTRW = 'Peraturan Daerah Kabupaten Bone Bolango Nomor 5 Tahun 2021 tentang Rencana Tata Ruang Wilayah Kabupaten Bone Bolango Tahun 2021-2041';

    public const DASAR_PANITIA = 'Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Nomor 18 Tahun 2021 tentang Tata Cara Penetapan Hak Pengelolaan dan Hak Atas Tanah';

    /**
     * Daftar dasar hukum standar (urut sebagaimana lazim tercantum pada risalah).
     *
     * @return array<int, string>
     */
    public static function dasarHukum(): array
    {
        return [
            'Undang-Undang Nomor 5 Tahun 1960 tentang Peraturan Dasar Pokok-Pokok Agraria (Lembaran Negara Republik Indonesia Tahun 1960 Nomor 104, Tambahan Lembaran Negara Republik Indonesia Nomor 2043)',
            'Undang-Undang Nomor 6 Tahun 2003 tentang Pembentukan Kabupaten Bone Bolango',
            'Undang-Undang Nomor 26 Tahun 2007 tentang Penataan Ruang',
            'Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah',
            'Undang-Undang Nomor 30 Tahun 2014 tentang Administrasi Pemerintahan',
            'Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintah Daerah',
            'Undang-Undang Nomor 6 Tahun 2023 tentang Penetapan Peraturan Pemerintah Pengganti Undang-Undang Nomor 2 Tahun 2022 tentang Cipta Kerja Menjadi Undang-Undang',
            'Peraturan Pemerintah R.I. Nomor 24 Tahun 1997 tentang Pendaftaran Tanah',
            'Peraturan Pemerintah Nomor 128 Tahun 2015 tentang Jenis dan Tarif Atas Jenis Penerimaan Negara Bukan Pajak yang Berlaku pada Kementerian Agraria dan Tata Ruang/Badan Pertanahan Nasional',
            'Peraturan Pemerintah Nomor 5 Tahun 2021 tentang Penyelenggaraan Perizinan Berusaha Berbasis Risiko',
            'Peraturan Pemerintah Nomor 18 Tahun 2021 tentang Hak Pengelolaan, Hak atas Tanah, Satuan Rumah Susun, dan Pendaftaran Tanah',
            'Peraturan Pemerintah Nomor 20 Tahun 2021 tentang Penertiban Kawasan dan Tanah Telantar',
            'Peraturan Pemerintah Nomor 21 Tahun 2021 tentang Penyelenggaraan Penataan Ruang',
            'Peraturan Presiden R.I. Nomor 176 Tahun 2024 tentang Kementerian Agraria dan Tata Ruang',
            'Peraturan Presiden R.I. Nomor 177 Tahun 2024 tentang Badan Pertanahan Nasional',
            'Peraturan Menteri Negara Agraria/Kepala Badan Pertanahan Nasional Nomor 3 Tahun 1997 tentang Ketentuan Pelaksanaan Peraturan Pemerintah Nomor 24 Tahun 1997 tentang Pendaftaran Tanah sebagaimana telah beberapa kali diubah terakhir dengan Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Nomor 16 Tahun 2021',
            'Peraturan Kepala Badan Pertanahan Nasional R.I. Nomor 79 Tahun 2007 tentang Pembentukan Kantor Pertanahan Kabupaten Bone Bolango',
            'Peraturan Kepala Badan Pertanahan Nasional R.I. Nomor 1 Tahun 2010 tentang Standar Pelayanan dan Pengaturan Pertanahan',
            'Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Nomor 17 Tahun 2020 tentang Organisasi dan Tata Kerja Kantor Wilayah Badan Pertanahan Nasional dan Kantor Pertanahan',
            'Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Nomor 13 Tahun 2021 tentang Pelaksanaan Kesesuaian Kegiatan Pemanfaatan Ruang dan Sinkronisasi Program Pemanfaatan Ruang',
            'Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Nomor 18 Tahun 2021 tentang Tata Cara Penetapan Hak Pengelolaan dan Hak Atas Tanah',
            'Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Republik Indonesia Nomor 9 Tahun 2025 tentang Perubahan Atas Peraturan Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional Nomor 5 Tahun 2025 tentang Pelimpahan Kewenangan Penetapan Hak Atas Tanah dan Kegiatan Pendaftaran Tanah',
            'Peraturan Daerah Kabupaten Bone Bolango Nomor 2 Tahun 2011 tentang Bea Perolehan Hak Atas Tanah dan Bangunan',
            'Peraturan Daerah Kabupaten Bone Bolango Nomor 5 Tahun 2021 tentang Rencana Tata Ruang Wilayah Kabupaten Bone Bolango Tahun 2021-2041',
        ];
    }
}
