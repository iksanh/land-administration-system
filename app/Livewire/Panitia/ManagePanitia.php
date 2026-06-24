<?php

namespace App\Livewire\Panitia;

use App\Enums\PeranPanitiaEnum;
use App\Models\PanitiaPemeriksa;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Master anggota Panitia Pemeriksa Tanah A — dipakai ulang saat menyusun
 * Berita Acara Pemeriksaan Lapang.
 */
#[Layout('components.layouts.app')]
class ManagePanitia extends Component
{
    public string $search = '';

    public ?string $editingId = null;

    public string $nama = '';

    public string $nip = '';

    public string $jabatan = '';

    public string $peran = 'ANGGOTA';

    public int $urutan = 0;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:200'],
            'peran' => ['required', Rule::enum(PeranPanitiaEnum::class)],
            'urutan' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            PanitiaPemeriksa::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Anggota panitia berhasil diperbarui.');
        } else {
            PanitiaPemeriksa::create($data);
            session()->flash('message', 'Anggota panitia berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $p = PanitiaPemeriksa::findOrFail($id);
        $this->editingId = $p->id;
        $this->nama = $p->nama;
        $this->nip = $p->nip ?? '';
        $this->jabatan = $p->jabatan ?? '';
        $this->peran = $p->peran->value;
        $this->urutan = $p->urutan;
        $this->is_active = $p->is_active;
    }

    public function delete(string $id): void
    {
        PanitiaPemeriksa::findOrFail($id)->delete();
        session()->flash('message', 'Anggota panitia berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'nama', 'nip', 'jabatan', 'urutan']);
        $this->peran = 'ANGGOTA';
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.panitia.manage-panitia', [
            'panitiaList' => PanitiaPemeriksa::query()
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nama', 'like', $term)
                        ->orWhere('nip', 'like', $term)
                        ->orWhere('jabatan', 'like', $term));
                })
                ->orderBy('urutan')->orderBy('nama')->get(),
            'peranOptions' => PeranPanitiaEnum::cases(),
        ]);
    }
}
