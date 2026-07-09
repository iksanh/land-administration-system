<?php

namespace App\Livewire\Wilayah;

use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefKepalaDesa;
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

    // Kelola kepala desa (modal) untuk desa terpilih.
    public string $kadesDesaId = '';

    public ?string $kadesEditingId = null;

    public string $kadesNama = '';

    public string $kadesNip = '';

    public string $kadesJabatan = 'Kepala Desa';

    public string $kadesPeriode = '';

    public bool $kadesAktif = true;

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

    /** Buka modal pengelolaan kepala desa untuk sebuah desa. */
    public function manageKades(string $desaId): void
    {
        $this->kadesDesaId = $desaId;
        $this->resetKadesForm();
    }

    public function closeKades(): void
    {
        $this->reset(['kadesDesaId']);
        $this->resetKadesForm();
    }

    public function saveKades(): void
    {
        if (! $this->kadesDesaId) {
            return;
        }

        $data = $this->validate([
            'kadesNama' => ['required', 'string', 'max:150'],
            'kadesNip' => ['nullable', 'string', 'max:30'],
            'kadesJabatan' => ['nullable', 'string', 'max:100'],
            'kadesPeriode' => ['nullable', 'string', 'max:50'],
            'kadesAktif' => ['boolean'],
        ]);

        $attrs = [
            'desa_id' => $this->kadesDesaId,
            'nama' => $data['kadesNama'],
            'nip' => $data['kadesNip'] ?: null,
            'jabatan' => $data['kadesJabatan'] ?: 'Kepala Desa',
            'periode' => $data['kadesPeriode'] ?: null,
            'is_active' => $data['kadesAktif'],
        ];

        if ($this->kadesEditingId) {
            RefKepalaDesa::where('desa_id', $this->kadesDesaId)
                ->findOrFail($this->kadesEditingId)->update($attrs);
            session()->flash('message', 'Kepala desa berhasil diperbarui.');
        } else {
            RefKepalaDesa::create($attrs);
            session()->flash('message', 'Kepala desa berhasil ditambahkan.');
        }

        $this->resetKadesForm();
    }

    public function editKades(string $id): void
    {
        $kd = RefKepalaDesa::where('desa_id', $this->kadesDesaId)->findOrFail($id);
        $this->kadesEditingId = $kd->id;
        $this->kadesNama = $kd->nama;
        $this->kadesNip = $kd->nip ?? '';
        $this->kadesJabatan = $kd->jabatan ?? 'Kepala Desa';
        $this->kadesPeriode = $kd->periode ?? '';
        $this->kadesAktif = $kd->is_active;
    }

    /** Aktifkan / non-aktifkan kepala desa (satu-klik). */
    public function toggleKades(string $id): void
    {
        $kd = RefKepalaDesa::where('desa_id', $this->kadesDesaId)->findOrFail($id);
        $kd->update(['is_active' => ! $kd->is_active]);
    }

    public function deleteKades(string $id): void
    {
        RefKepalaDesa::where('desa_id', $this->kadesDesaId)->findOrFail($id)->delete();
        if ($this->kadesEditingId === $id) {
            $this->resetKadesForm();
        }
        session()->flash('message', 'Kepala desa berhasil dihapus.');
    }

    public function resetKadesForm(): void
    {
        $this->reset(['kadesEditingId', 'kadesNama', 'kadesNip', 'kadesPeriode']);
        $this->kadesJabatan = 'Kepala Desa';
        $this->kadesAktif = true;
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
                ? RefDesa::withCount('kepalaDesaAktif')
                    ->where('kecamatan_id', $this->selKecamatan)->orderBy('nama')->get()
                : collect(),
            'kadesDesa' => $this->kadesDesaId
                ? RefDesa::find($this->kadesDesaId)
                : null,
            'kadesList' => $this->kadesDesaId
                ? RefKepalaDesa::where('desa_id', $this->kadesDesaId)
                    ->orderByDesc('is_active')->orderBy('urutan')->orderBy('nama')->get()
                : collect(),
        ]);
    }
}
