<?php

namespace App\Http\Controllers;

use App\Models\RisalahPanitiaA;
use App\Support\PanitiaResolver;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Unduh Risalah sebagai berkas Word (.doc) yang bisa diedit manual. Memakai
 * HTML ber-header Microsoft Word (tanpa dependency tambahan), meniru pola
 * BeritaAcaraWordController.
 */
class RisalahWordController extends Controller
{
    public function __invoke(RisalahPanitiaA $risalah): Response
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

        $html = view('risalah.word', ['r' => $risalah])->render();

        $slug = Str::slug($risalah->permohonan?->nomor_registrasi ?: $risalah->id);
        $filename = 'Risalah-'.($slug ?: 'panitia-a').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
