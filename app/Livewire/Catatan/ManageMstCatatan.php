<?php

namespace App\Livewire\Catatan;

use App\Models\MstBerkasItem;
use App\Models\MstCatatan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/catatan.py (Master Catatan) — the reusable check-note
 * catalog that feeds the Pemeriksaan Berkas screen. A null berkas_item_id is a
 * global note; a set one is specific to that berkas.
 */
#[Layout('components.layouts.app')]
class ManageMstCatatan extends Component
{
    public ?string $editingId = null;
    public string $teks = '';
    public string $berkas_item_id = '';
    public bool $is_active = true;

    public string $search = '';

    protected function rules(): array
    {
        return [
            'teks' => ['required', 'string'],
            'berkas_item_id' => ['nullable', 'exists:mst_berkas_item,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['berkas_item_id'] = $data['berkas_item_id'] !== '' ? $data['berkas_item_id'] : null;

        if ($this->editingId) {
            MstCatatan::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Catatan berhasil diperbarui.');
        } else {
            MstCatatan::create($data);
            session()->flash('message', 'Catatan berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $c = MstCatatan::findOrFail($id);
        $this->editingId = $c->id;
        $this->teks = $c->teks;
        $this->berkas_item_id = $c->berkas_item_id ?? '';
        $this->is_active = $c->is_active;
    }

    public function delete(string $id): void
    {
        MstCatatan::findOrFail($id)->delete();
        session()->flash('message', 'Catatan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'teks', 'berkas_item_id']);
        $this->is_active = true;
    }

    public function render()
    {
        $query = MstCatatan::with('berkasItem')->orderBy('teks');
        if (trim($this->search) !== '') {
            // MySQL has no ILIKE; LIKE is case-insensitive with the default utf8mb4 collation.
            $query->where('teks', 'like', '%'.trim($this->search).'%');
        }

        return view('livewire.catatan.manage-mst-catatan', [
            'catatanList' => $query->get(),
            'berkasOptions' => MstBerkasItem::orderBy('nama')->get(),
        ]);
    }
}
