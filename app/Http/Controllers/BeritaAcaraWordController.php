<?php

namespace App\Http\Controllers;

use App\Models\BeritaAcaraPemeriksaan;
use App\Support\PanitiaResolver;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Unduh Berita Acara sebagai berkas Word (.doc) yang bisa diedit manual.
 * Memakai HTML ber-header Microsoft Word (tanpa dependency tambahan); gambar
 * di-embed base64 lewat partial `berita-acara._dokumen` (mode 'word').
 */
class BeritaAcaraWordController extends Controller
{
    public function __invoke(BeritaAcaraPemeriksaan $beritaAcara): Response
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

        $html = view('berita-acara.word', ['ba' => $beritaAcara])->render();

        $slug = Str::slug($beritaAcara->permohonan?->nomor_registrasi ?: $beritaAcara->id);
        $filename = 'Berita-Acara-'.($slug ?: 'pemeriksaan').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
