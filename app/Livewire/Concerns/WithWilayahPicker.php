<?php

namespace App\Livewire\Concerns;

use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefProvinsi;

/**
 * Cascading wilayah picker (Provinsi → Kabupaten → Kecamatan → Desa) for any
 * Livewire component that has a `public string $desa_id` property. Each level
 * filters the next so users narrow down to a village instead of scrolling a
 * single huge desa list. Used by ManagePemohon and ManageTanah.
 *
 * Wire up the host component:
 *   - edit():       after setting $desa_id, call $this->syncWilayahFromDesa();
 *   - resetForm():  call $this->resetWilayah();
 *   - render():     array_merge($this->wilayahLists(), [...]) into the view data.
 */
trait WithWilayahPicker
{
    public string $wProvinsi = '';
    public string $wKabupaten = '';
    public string $wKecamatan = '';

    public function updatedWProvinsi(): void
    {
        $this->wKabupaten = '';
        $this->wKecamatan = '';
        $this->desa_id = '';
    }

    public function updatedWKabupaten(): void
    {
        $this->wKecamatan = '';
        $this->desa_id = '';
    }

    public function updatedWKecamatan(): void
    {
        $this->desa_id = '';
    }

    /** Backfill the cascade from an existing desa_id (when editing a record). */
    public function syncWilayahFromDesa(): void
    {
        $this->wProvinsi = $this->wKabupaten = $this->wKecamatan = '';

        if (! $this->desa_id) {
            return;
        }

        $desa = RefDesa::with('kecamatan.kabupaten')->find($this->desa_id);
        if (! $desa) {
            return;
        }

        $this->wKecamatan = $desa->kecamatan_id ?? '';
        $this->wKabupaten = $desa->kecamatan?->kabupaten_id ?? '';
        $this->wProvinsi = $desa->kecamatan?->kabupaten?->provinsi_id ?? '';
    }

    public function resetWilayah(): void
    {
        $this->wProvinsi = '';
        $this->wKabupaten = '';
        $this->wKecamatan = '';
    }

    /** Filtered option lists for the four cascading selects. */
    public function wilayahLists(): array
    {
        return [
            'provinsiList' => RefProvinsi::orderBy('nama')->get(),
            'kabupatenList' => $this->wProvinsi
                ? RefKabupaten::where('provinsi_id', $this->wProvinsi)->orderBy('nama')->get()
                : collect(),
            'kecamatanList' => $this->wKabupaten
                ? RefKecamatan::where('kabupaten_id', $this->wKabupaten)->orderBy('nama')->get()
                : collect(),
            'desaList' => $this->wKecamatan
                ? RefDesa::where('kecamatan_id', $this->wKecamatan)->orderBy('nama')->get()
                : collect(),
        ];
    }
}
