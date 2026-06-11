<?php

namespace App\Livewire\Wilayah;

use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefProvinsi;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/wilayah.py — a parent-filtered drilldown:
 * provinsi → kabupaten → kecamatan → desa. PKs are coded strings
 * (2 / 4 / 6 / 10 chars), so each "add" form takes an explicit id + nama.
 */
#[Layout('components.layouts.app')]
class ManageWilayah extends Component
{
    public string $selProvinsi = '';
    public string $selKabupaten = '';
    public string $selKecamatan = '';

    // Add-form fields, one set per level.
    public string $provId = '';
    public string $provNama = '';
    public string $kabId = '';
    public string $kabNama = '';
    public string $kecId = '';
    public string $kecNama = '';
    public string $desaId = '';
    public string $desaNama = '';
    public string $desaKepala = '';

    public function selectProvinsi(string $id): void
    {
        $this->selProvinsi = $id;
        $this->selKabupaten = '';
        $this->selKecamatan = '';
    }

    public function selectKabupaten(string $id): void
    {
        $this->selKabupaten = $id;
        $this->selKecamatan = '';
    }

    public function selectKecamatan(string $id): void
    {
        $this->selKecamatan = $id;
    }

    public function addProvinsi(): void
    {
        $data = $this->validate([
            'provId' => ['required', 'string', 'max:2', 'unique:ref_provinsi,id'],
            'provNama' => ['required', 'string', 'max:100'],
        ]);

        RefProvinsi::create(['id' => $data['provId'], 'nama' => $data['provNama']]);
        $this->reset(['provId', 'provNama']);
        session()->flash('message', 'Provinsi berhasil ditambahkan.');
    }

    public function addKabupaten(): void
    {
        if (! $this->selProvinsi) {
            return;
        }

        $data = $this->validate([
            'kabId' => ['required', 'string', 'max:4', 'unique:ref_kabupaten,id'],
            'kabNama' => ['required', 'string', 'max:100'],
        ]);

        RefKabupaten::create([
            'id' => $data['kabId'],
            'provinsi_id' => $this->selProvinsi,
            'nama' => $data['kabNama'],
        ]);
        $this->reset(['kabId', 'kabNama']);
        session()->flash('message', 'Kabupaten berhasil ditambahkan.');
    }

    public function addKecamatan(): void
    {
        if (! $this->selKabupaten) {
            return;
        }

        $data = $this->validate([
            'kecId' => ['required', 'string', 'max:6', 'unique:ref_kecamatan,id'],
            'kecNama' => ['required', 'string', 'max:100'],
        ]);

        RefKecamatan::create([
            'id' => $data['kecId'],
            'kabupaten_id' => $this->selKabupaten,
            'nama' => $data['kecNama'],
        ]);
        $this->reset(['kecId', 'kecNama']);
        session()->flash('message', 'Kecamatan berhasil ditambahkan.');
    }

    public function addDesa(): void
    {
        if (! $this->selKecamatan) {
            return;
        }

        $data = $this->validate([
            'desaId' => ['required', 'string', 'max:10', 'unique:ref_desa,id'],
            'desaNama' => ['required', 'string', 'max:100'],
            'desaKepala' => ['nullable', 'string', 'max:200'],
        ]);

        RefDesa::create([
            'id' => $data['desaId'],
            'kecamatan_id' => $this->selKecamatan,
            'nama' => $data['desaNama'],
            'nama_kepala_desa' => $data['desaKepala'] ?: null,
        ]);
        $this->reset(['desaId', 'desaNama', 'desaKepala']);
        session()->flash('message', 'Desa berhasil ditambahkan.');
    }

    public function render()
    {
        return view('livewire.wilayah.manage-wilayah', [
            'provinsiList' => RefProvinsi::orderBy('nama')->get(),
            'kabupatenList' => $this->selProvinsi
                ? RefKabupaten::where('provinsi_id', $this->selProvinsi)->orderBy('nama')->get()
                : collect(),
            'kecamatanList' => $this->selKabupaten
                ? RefKecamatan::where('kabupaten_id', $this->selKabupaten)->orderBy('nama')->get()
                : collect(),
            'desaList' => $this->selKecamatan
                ? RefDesa::where('kecamatan_id', $this->selKecamatan)->orderBy('nama')->get()
                : collect(),
        ]);
    }
}
