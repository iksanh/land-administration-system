<?php

namespace App\Livewire\Layanan;

use App\Models\MstLayanan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports the Layanan CRUD from app/api/routes/layanan.py (Master Layanan).
 * `kode` is unique; create/edit share one form.
 */
#[Layout('components.layouts.app')]
class ManageLayanan extends Component
{
    public string $search = '';
    public ?string $editingId = null;
    public string $kode = '';
    public string $nama = '';
    public string $deskripsi = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', Rule::unique('mst_layanan', 'kode')->ignore($this->editingId)],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            MstLayanan::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Layanan berhasil diperbarui.');
        } else {
            MstLayanan::create($data);
            session()->flash('message', 'Layanan berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $layanan = MstLayanan::findOrFail($id);
        $this->editingId = $layanan->id;
        $this->kode = $layanan->kode;
        $this->nama = $layanan->nama;
        $this->deskripsi = $layanan->deskripsi ?? '';
        $this->is_active = $layanan->is_active;
    }

    public function delete(string $id): void
    {
        MstLayanan::findOrFail($id)->delete();
        session()->flash('message', 'Layanan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'kode', 'nama', 'deskripsi']);
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.layanan.manage-layanan', [
            'layanan' => MstLayanan::query()
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nama', 'like', $term)
                        ->orWhere('kode', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term));
                })
                ->orderBy('nama')->get(),
        ]);
    }
}
