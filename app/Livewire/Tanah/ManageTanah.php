<?php

namespace App\Livewire\Tanah;

use App\Livewire\Concerns\WithWilayahPicker;
use App\Models\Pemohon;
use App\Models\Tanah;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/tanah.py. The list joins pemohon + desa names (here via
 * eager-loaded relationships). luas / luas_surat must be > 0 (DB check
 * constraints check_luas_positif / check_luas_surat_positif).
 */
#[Layout('components.layouts.app')]
class ManageTanah extends Component
{
    use WithWilayahPicker;

    public string $search = '';
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $pemohon_id = '';
    public string $desa_id = '';
    public string $luas = '';
    public string $luas_surat = '';
    public string $penggunaan_tanah = '';
    public string $nomor_pbt = '';
    public string $tanggal_pbt = '';
    public string $nib = '';
    public string $tgl_peta_analisis = '';
    public string $rencana_penggunaan_rtrw = '';
    public string $kesesuaian_penggunaan_tanah = '';
    public string $penggunaan_tanah_sk = '';
    public string $batas_utara = '';
    public string $batas_timur = '';
    public string $batas_selatan = '';
    public string $batas_barat = '';

    protected function rules(): array
    {
        return [
            'pemohon_id' => ['nullable', 'exists:pemohon,id'],
            'desa_id' => ['nullable', 'exists:ref_desa,id'],
            'luas' => ['nullable', 'numeric', 'gt:0'],
            'luas_surat' => ['nullable', 'numeric', 'gt:0'],
            'penggunaan_tanah' => ['nullable', 'string', 'max:200'],
            'nomor_pbt' => ['nullable', 'string', 'max:100'],
            'tanggal_pbt' => ['nullable', 'date'],
            'nib' => ['nullable', 'string', 'max:100'],
            'tgl_peta_analisis' => ['nullable', 'date'],
            'rencana_penggunaan_rtrw' => ['nullable', 'string', 'max:200'],
            'kesesuaian_penggunaan_tanah' => ['nullable', 'string', 'max:50'],
            'penggunaan_tanah_sk' => ['nullable', 'string', 'max:200'],
            'batas_utara' => ['nullable', 'string'],
            'batas_timur' => ['nullable', 'string'],
            'batas_selatan' => ['nullable', 'string'],
            'batas_barat' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        foreach ($data as $key => $value) {
            $data[$key] = $value !== '' ? $value : null;
        }

        if ($this->editingId) {
            Tanah::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Data tanah berhasil diperbarui.');
        } else {
            Tanah::create($data);
            session()->flash('message', 'Data tanah berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $t = Tanah::findOrFail($id);
        $this->editingId = $t->id;
        $this->pemohon_id = $t->pemohon_id ?? '';
        $this->desa_id = $t->desa_id ?? '';
        $this->syncWilayahFromDesa();
        $this->luas = $t->luas ?? '';
        $this->luas_surat = $t->luas_surat ?? '';
        $this->penggunaan_tanah = $t->penggunaan_tanah ?? '';
        $this->nomor_pbt = $t->nomor_pbt ?? '';
        $this->tanggal_pbt = $t->tanggal_pbt?->format('Y-m-d') ?? '';
        $this->nib = $t->nib ?? '';
        $this->tgl_peta_analisis = $t->tgl_peta_analisis?->format('Y-m-d') ?? '';
        $this->rencana_penggunaan_rtrw = $t->rencana_penggunaan_rtrw ?? '';
        $this->kesesuaian_penggunaan_tanah = $t->kesesuaian_penggunaan_tanah ?? '';
        $this->penggunaan_tanah_sk = $t->penggunaan_tanah_sk ?? '';
        $this->batas_utara = $t->batas_utara ?? '';
        $this->batas_timur = $t->batas_timur ?? '';
        $this->batas_selatan = $t->batas_selatan ?? '';
        $this->batas_barat = $t->batas_barat ?? '';
        $this->showForm = true;
    }

    public function delete(string $id): void
    {
        Tanah::findOrFail($id)->delete();
        session()->flash('message', 'Data tanah berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'showForm', 'pemohon_id', 'desa_id', 'luas', 'luas_surat',
            'penggunaan_tanah', 'nomor_pbt', 'tanggal_pbt', 'nib',
            'tgl_peta_analisis', 'rencana_penggunaan_rtrw', 'kesesuaian_penggunaan_tanah', 'penggunaan_tanah_sk',
            'batas_utara', 'batas_timur', 'batas_selatan', 'batas_barat',
        ]);
        $this->resetWilayah();
    }

    public function render()
    {
        return view('livewire.tanah.manage-tanah', array_merge($this->wilayahLists(), [
            'tanahList' => Tanah::with(['pemohon', 'desa'])
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('penggunaan_tanah', 'like', $term)
                        ->orWhere('nomor_pbt', 'like', $term)
                        ->orWhere('nib', 'like', $term)
                        ->orWhereHas('pemohon', fn ($p) => $p->where('nama', 'like', $term)->orWhere('nik', 'like', $term))
                        ->orWhereHas('desa', fn ($d) => $d->where('nama', 'like', $term)));
                })
                ->latest('created_at')->get(),
            'pemohonList' => Pemohon::orderBy('nama')->get(),
        ]));
    }
}
