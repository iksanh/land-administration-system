<?php

namespace App\Livewire\Berkas;

use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstLayanan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports the Map Layanan-Berkas routes from app/api/routes/layanan.py.
 * map_layanan_berkas has a COMPOSITE PK (layanan_id, berkas_item_id) so all
 * lookups/updates/deletes go through where() clauses, not find(). Toggling a
 * berkas adds it (urutan = current max + 1) or removes it; the exists() guard
 * keeps the duplicate-mapping rule the API enforced (no PK violation).
 *
 * Urutan is edited by drag-and-drop (Livewire's wire:sort → sortBerkas) or the
 * up/down buttons (moveBerkas); both rewrite the whole list as a contiguous
 * 1..n sequence so gaps/duplicates from older manual input disappear.
 */
#[Layout('components.layouts.app')]
class ManageMapLayananBerkas extends Component
{
    public string $selectedLayanan = '';

    public function toggle(string $berkasId): void
    {
        if (! $this->selectedLayanan) {
            return;
        }

        $query = MapLayananBerkas::where('layanan_id', $this->selectedLayanan)
            ->where('berkas_item_id', $berkasId);

        if ($query->exists()) {
            $query->delete();
            $this->renumber($this->orderedIds());

            return;
        }

        $nextUrutan = (int) MapLayananBerkas::where('layanan_id', $this->selectedLayanan)->max('urutan') + 1;

        MapLayananBerkas::create([
            'layanan_id' => $this->selectedLayanan,
            'berkas_item_id' => $berkasId,
            'urutan' => $nextUrutan,
        ]);
    }

    /**
     * Drop handler for wire:sort — $position is the 0-based target index.
     */
    public function sortBerkas(string $berkasId, int $position): void
    {
        if (! $this->selectedLayanan) {
            return;
        }

        $ids = $this->orderedIds();
        $from = array_search($berkasId, $ids, true);

        if ($from === false) {
            return;
        }

        array_splice($ids, $from, 1);
        array_splice($ids, max(0, min($position, count($ids))), 0, [$berkasId]);

        $this->renumber($ids);
    }

    /** Up/down buttons: $step is -1 (naik) or +1 (turun). */
    public function moveBerkas(string $berkasId, int $step): void
    {
        $from = array_search($berkasId, $this->orderedIds(), true);

        if ($from !== false) {
            $this->sortBerkas($berkasId, $from + ($step < 0 ? -1 : 1));
        }
    }

    /** Mapped berkas ids of the selected layanan in their current order. */
    private function orderedIds(): array
    {
        return MapLayananBerkas::where('layanan_id', $this->selectedLayanan)
            ->orderBy('urutan')
            ->orderBy('berkas_item_id')
            ->pluck('berkas_item_id')
            ->all();
    }

    /** Persist $ids as urutan 1..n (composite PK → update via where()). */
    private function renumber(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                MapLayananBerkas::where('layanan_id', $this->selectedLayanan)
                    ->where('berkas_item_id', $id)
                    ->update(['urutan' => $i + 1]);
            }
        });
    }

    public function render()
    {
        $mapped = collect();

        if ($this->selectedLayanan) {
            $mapped = MapLayananBerkas::with('berkasItem')
                ->where('layanan_id', $this->selectedLayanan)
                ->orderBy('urutan')
                ->orderBy('berkas_item_id')
                ->get();
        }

        return view('livewire.berkas.manage-map-layanan-berkas', [
            'layananList' => MstLayanan::orderBy('nama')->get(),
            'berkasItems' => MstBerkasItem::orderBy('nama')->get(),
            'mapped' => $mapped,
            'mappedIds' => $mapped->pluck('berkas_item_id')->all(),
        ]);
    }
}
