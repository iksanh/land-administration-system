<?php

namespace App\Livewire\Berkas;

use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstLayanan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports the Map Layanan-Berkas routes from app/api/routes/layanan.py.
 * map_layanan_berkas has a COMPOSITE PK (layanan_id, berkas_item_id) so all
 * lookups/updates/deletes go through where() clauses, not find(). Toggling a
 * berkas adds it (urutan = current max + 1) or removes it; the exists() guard
 * keeps the duplicate-mapping rule the API enforced (no PK violation).
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

            return;
        }

        $nextUrutan = (int) MapLayananBerkas::where('layanan_id', $this->selectedLayanan)->max('urutan') + 1;

        MapLayananBerkas::create([
            'layanan_id' => $this->selectedLayanan,
            'berkas_item_id' => $berkasId,
            'urutan' => $nextUrutan,
        ]);
    }

    public function updateUrutan(string $berkasId, $urutan): void
    {
        MapLayananBerkas::where('layanan_id', $this->selectedLayanan)
            ->where('berkas_item_id', $berkasId)
            ->update(['urutan' => max(1, (int) $urutan)]);
    }

    public function render()
    {
        $mapped = collect();

        if ($this->selectedLayanan) {
            $mapped = MapLayananBerkas::with('berkasItem')
                ->where('layanan_id', $this->selectedLayanan)
                ->orderBy('urutan')
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
