<?php

namespace App\Http\Controllers;

use App\Models\BeritaAcaraPemeriksaan;
use Illuminate\View\View;

/**
 * Lembar cetak Berita Acara Pemeriksaan Lapang (BAPL) oleh Panitia Pemeriksa
 * Tanah A. Data teknis diambil dari permohonan->tanah; field naratif dari berita
 * acara itu sendiri.
 */
class BeritaAcaraPrintController extends Controller
{
    public function __invoke(BeritaAcaraPemeriksaan $beritaAcara): View
    {
        $beritaAcara->load([
            'permohonan.pemohon',
            'permohonan.tanah.desa.kecamatan.kabupaten.provinsi',
            'permohonan.riwayatPenguasaan',
            'panitia',
            'lampiran',
        ]);

        return view('berita-acara.print', [
            'ba' => $beritaAcara,
        ]);
    }
}
