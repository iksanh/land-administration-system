<?php

namespace App\Support;

use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;

/**
 * Builds the ordered/grouped data for the printable inspection sheet
 * (shared by PemeriksaanPrintController and the in-app print modal).
 * Returns [parents, childrenMap] — parents ordered by the layanan's
 * MapLayananBerkas.urutan (nulls last); childrenMap keyed by parent berkas id.
 */
class PemeriksaanSheet
{
    public static function build(Permohonan $permohonan): array
    {
        $rows = PemeriksaanBerkas::query()
            ->with('berkasItem')
            ->where('pemeriksaan_berkas.permohonan_id', $permohonan->id)
            ->leftJoin('map_layanan_berkas', function ($join) use ($permohonan) {
                $join->on('map_layanan_berkas.berkas_item_id', '=', 'pemeriksaan_berkas.berkas_item_id')
                    ->where('map_layanan_berkas.layanan_id', '=', $permohonan->layanan_id);
            })
            // MySQL has no "NULLS LAST"; emulate it (NULLs sort first by default on ASC).
            ->orderByRaw('map_layanan_berkas.urutan is null, map_layanan_berkas.urutan asc')
            ->select('pemeriksaan_berkas.*')
            ->get();

        $parents = [];
        $childrenMap = [];
        foreach ($rows as $row) {
            if (! $row->berkasItem) {
                continue;
            }
            if ($row->berkasItem->parent_id) {
                $childrenMap[$row->berkasItem->parent_id][] = $row;
            } else {
                $parents[] = $row;
            }
        }

        return [$parents, $childrenMap];
    }
}
