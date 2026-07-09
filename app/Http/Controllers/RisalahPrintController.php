<?php

namespace App\Http\Controllers;

use App\Models\RisalahPanitiaA;
use App\Support\PanitiaResolver;
use Illuminate\View\View;

/**
 * Lembar cetak Risalah Panitia Pemeriksaan Tanah "A". Data pemohon/tanah/riwayat
 * diambil dari relasi permohonan; field naratif & daftar (data pendukung, dasar
 * hukum, pendapat panitia) dari risalah itu sendiri. Mengikuti pola
 * BeritaAcaraPrintController.
 */
class RisalahPrintController extends Controller
{
    public function __invoke(RisalahPanitiaA $risalah): View
    {
        $risalah->load([
            'permohonan.pemohon.desa.kecamatan.kabupaten.provinsi',
            'permohonan.pemohon.desa.kepalaDesaAktif',
            'permohonan.tanah.desa.kecamatan.kabupaten.provinsi',
            'permohonan.tanah.desa.kepalaDesaAktif',
            'permohonan.riwayatPenguasaan',
            'panitia',
        ]);

        // Kepala desa aktif dari desa lokasi tanah ikut sebagai penandatangan.
        $risalah->setRelation('panitia', PanitiaResolver::withKepalaDesa($risalah->panitia, $risalah->permohonan));

        return view('risalah.print', ['r' => $risalah]);
    }
}
