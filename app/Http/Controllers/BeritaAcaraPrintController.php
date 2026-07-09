<?php

namespace App\Http\Controllers;

use App\Models\BeritaAcaraPemeriksaan;
use App\Support\PanitiaResolver;
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
            'permohonan.pemohon.desa.kepalaDesaAktif',
            'permohonan.tanah.desa.kecamatan.kabupaten.provinsi',
            'permohonan.tanah.desa.kepalaDesaAktif',
            'permohonan.riwayatPenguasaan',
            'panitia',
            'lampiran',
        ]);

        // Kepala desa aktif dari desa lokasi tanah ikut sebagai penandatangan.
        $beritaAcara->setRelation('panitia', PanitiaResolver::withKepalaDesa($beritaAcara->panitia, $beritaAcara->permohonan));

        return view('berita-acara.print', [
            'ba' => $beritaAcara,
        ]);
    }
}
