<?php

namespace App\Livewire\Pemohon;

use App\Enums\GenderEnum;
use App\Livewire\Concerns\WithWilayahPicker;
use App\Models\Pemohon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/pemohon.py. NIK is unique (the API returned 400 on
 * duplicate — here a validation error). jenis_kelamin is the L/P GenderEnum.
 */
#[Layout('components.layouts.app')]
class ManagePemohon extends Component
{
    use WithWilayahPicker;

    public string $search = '';
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $nik = '';
    public string $nama = '';
    public string $tempat_lahir = '';
    public string $tanggal_lahir = '';
    public string $jenis_kelamin = '';
    public string $pekerjaan = '';
    public string $alamat_detail = '';
    public string $desa_id = '';

    protected function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:16', Rule::unique('pemohon', 'nik')->ignore($this->editingId)],
            'nama' => ['required', 'string', 'max:200'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', Rule::enum(GenderEnum::class)],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'alamat_detail' => ['nullable', 'string'],
            'desa_id' => ['nullable', 'exists:ref_desa,id'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // Optional fields: empty string -> null (esp. the enum & FK columns).
        foreach (['tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan', 'alamat_detail', 'desa_id'] as $field) {
            $data[$field] = $data[$field] !== '' ? $data[$field] : null;
        }

        if ($this->editingId) {
            Pemohon::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Pemohon berhasil diperbarui.');
        } else {
            Pemohon::create($data);
            session()->flash('message', 'Pemohon berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $p = Pemohon::findOrFail($id);
        $this->editingId = $p->id;
        $this->nik = $p->nik;
        $this->nama = $p->nama;
        $this->tempat_lahir = $p->tempat_lahir ?? '';
        $this->tanggal_lahir = $p->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->jenis_kelamin = $p->jenis_kelamin?->value ?? '';
        $this->pekerjaan = $p->pekerjaan ?? '';
        $this->alamat_detail = $p->alamat_detail ?? '';
        $this->desa_id = $p->desa_id ?? '';
        $this->syncWilayahFromDesa();
        $this->showForm = true;
    }

    public function delete(string $id): void
    {
        Pemohon::findOrFail($id)->delete();
        session()->flash('message', 'Pemohon berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'showForm', 'nik', 'nama', 'tempat_lahir', 'tanggal_lahir',
            'jenis_kelamin', 'pekerjaan', 'alamat_detail', 'desa_id',
        ]);
        $this->resetWilayah();
    }

    public function render()
    {
        return view('livewire.pemohon.manage-pemohon', array_merge($this->wilayahLists(), [
            'pemohonList' => Pemohon::with('desa')
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nama', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('pekerjaan', 'like', $term));
                })
                ->orderBy('nama')->get(),
        ]));
    }
}
