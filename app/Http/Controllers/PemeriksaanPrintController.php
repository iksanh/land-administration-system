<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;
use App\Support\PemeriksaanSheet;
use Illuminate\View\View;

/**
 * Standalone printable inspection sheet (ports preview-html from
 * app/api/routes/pemeriksaan_berkas.py). The in-app preview modal renders the
 * same `pemeriksaan._sheet` partial via App\Support\PemeriksaanSheet.
 */
class PemeriksaanPrintController extends Controller
{
    public function __invoke(Permohonan $permohonan): View
    {
        $permohonan->load(['pemohon', 'layanan', 'tanah']);

        [$parents, $childrenMap] = PemeriksaanSheet::build($permohonan);

        return view('pemeriksaan.print', [
            'permohonan' => $permohonan,
            'parents' => $parents,
            'childrenMap' => $childrenMap,
        ]);
    }
}
