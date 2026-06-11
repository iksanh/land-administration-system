<?php

namespace App\Livewire\Berkas;

use App\Models\MstBerkasItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports the Berkas Item CRUD from app/api/routes/layanan.py (Master Berkas Item).
 * mst_berkas_item is self-referential (parent_id) — items can nest one level
 * under a parent. Deleting a parent cascades to its children (FK onDelete cascade).
 */
#[Layout('components.layouts.app')]
class ManageBerkasItem extends Component
{
    public string $search = '';
    public ?string $editingId = null;
    public string $nama = '';
    public bool $is_mandatory = true;
    public string $catatan = '';
    public ?string $parent_id = null;

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'is_mandatory' => ['boolean'],
            'catatan' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'uuid', 'exists:mst_berkas_item,id'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId && $this->parent_id === $this->editingId) {
            $this->addError('parent_id', 'Berkas tidak boleh menjadi induk dirinya sendiri.');

            return;
        }

        $data['catatan'] = $data['catatan'] !== '' ? $data['catatan'] : null;
        $data['parent_id'] = $data['parent_id'] ?: null;

        if ($this->editingId) {
            MstBerkasItem::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Berkas berhasil diperbarui.');
        } else {
            MstBerkasItem::create($data);
            session()->flash('message', 'Berkas berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $berkas = MstBerkasItem::findOrFail($id);
        $this->editingId = $berkas->id;
        $this->nama = $berkas->nama;
        $this->is_mandatory = $berkas->is_mandatory;
        $this->catatan = $berkas->catatan ?? '';
        $this->parent_id = $berkas->parent_id;
    }

    public function delete(string $id): void
    {
        MstBerkasItem::findOrFail($id)->delete();
        session()->flash('message', 'Berkas berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'nama', 'catatan', 'parent_id']);
        $this->is_mandatory = true;
    }

    public function render()
    {
        return view('livewire.berkas.manage-berkas-item', [
            'roots' => MstBerkasItem::with('subBerkas')
                ->whereNull('parent_id')
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nama', 'like', $term)
                        ->orWhereHas('subBerkas', fn ($s) => $s->where('nama', 'like', $term)));
                })
                ->orderBy('nama')->get(),
            'parentOptions' => MstBerkasItem::orderBy('nama')->get(),
        ]);
    }
}
