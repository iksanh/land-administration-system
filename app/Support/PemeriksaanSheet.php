<?php

namespace App\Support;

use App\Models\MapLayananBerkas;
use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;

/**
 * Builds the ordered/grouped data for the printable inspection sheet
 * (shared by PemeriksaanPrintController and the in-app print modal).
 * Returns [parents, childrenMap] — parents ordered by the layanan's
 * MapLayananBerkas.urutan (nulls last); childrenMap keyed by parent berkas id.
 *
 * The sheet is sourced from the layanan's MapLayananBerkas checklist — the same
 * authoritative list the pemeriksaan page shows — so every mapped berkas appears,
 * including ones added to the mapping after inspection started. The matching
 * PemeriksaanBerkas record (status/catatan) is attached where one exists; berkas
 * not yet inspected render with no catatan ("OK"). Sourcing from the inspection
 * records instead would silently drop newly-mapped berkas and any child whose
 * parent hadn't been inspected.
 */
class PemeriksaanSheet
{
    public static function build(Permohonan $permohonan): array
    {
        $mappings = MapLayananBerkas::query()
            ->with('berkasItem')
            ->where('layanan_id', $permohonan->layanan_id)
            // MySQL has no "NULLS LAST"; emulate it (NULLs sort first by default on ASC).
            ->orderByRaw('urutan is null, urutan asc')
            ->get()
            ->filter(fn ($m) => $m->berkasItem !== null);

        $pemeriksaan = PemeriksaanBerkas::where('permohonan_id', $permohonan->id)
            ->get()
            ->keyBy('berkas_item_id');

        // Mapped berkas ids — a child only nests under its parent when that parent
        // is itself mapped; otherwise it's shown top-level (mirrors the page list).
        $mappedIds = array_flip($mappings->pluck('berkasItem.id')->all());

        $parents = [];
        $childrenMap = [];
        foreach ($mappings as $mapping) {
            $berkasItem = $mapping->berkasItem;

            $row = $pemeriksaan->get($berkasItem->id)
                ?? tap(new PemeriksaanBerkas, fn ($r) => $r->berkas_item_id = $berkasItem->id);
            $row->setRelation('berkasItem', $berkasItem);

            $parentId = $berkasItem->parent_id;
            if ($parentId && isset($mappedIds[$parentId])) {
                $childrenMap[$parentId][] = $row;
            } else {
                $parents[] = $row;
            }
        }

        return [$parents, $childrenMap];
    }
}
