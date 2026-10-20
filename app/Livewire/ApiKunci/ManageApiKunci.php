<?php

namespace App\Livewire\ApiKunci;

use App\Models\ApiKunci;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin-only: kelola kunci API sinkron (dipakai SIPANTAS untuk GET /api/sinkron/tanah).
 * Kunci asli hanya ditampilkan sekali setelah dibuat; DB menyimpan hash-nya saja.
 */
#[Layout('components.layouts.app')]
class ManageApiKunci extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public string $nama = '';

    /** Kunci asli yang baru dibuat — ditampilkan sekali, lalu dibuang. */
    public ?string $kunciBaru = null;

    public ?string $kunciBaruNama = null;

    /**
     * Middleware `role:admin` hanya menjaga muatan halaman pertama; boot()
     * jalan di setiap request Livewire, jadi aksi juga terjaga.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Hanya admin yang boleh mengelola kunci API.');
    }

    public function create(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:100'],
        ], [], ['nama' => 'nama kunci']);

        [$model, $kunci] = ApiKunci::terbitkan($data['nama'], auth()->id());

        $this->kunciBaru = $kunci;
        $this->kunciBaruNama = $model->nama;
        $this->reset(['nama', 'showForm']);
    }

    public function tutupKunciBaru(): void
    {
        $this->reset(['kunciBaru', 'kunciBaruNama']);
    }

    public function toggleActive(string $id): void
    {
        $kunci = ApiKunci::findOrFail($id);
        $kunci->update(['is_active' => ! $kunci->is_active]);
        session()->flash('message', $kunci->is_active ? 'Kunci API diaktifkan.' : 'Kunci API dinonaktifkan.');
    }

    public function delete(string $id): void
    {
        ApiKunci::findOrFail($id)->delete();
        session()->flash('message', 'Kunci API berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.api-kunci.manage-api-kunci', [
            'list' => ApiKunci::query()
                ->with('pembuat:id,name')
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nama', 'like', $term)->orWhere('awalan', 'like', $term));
                })
                ->orderByDesc('created_at')->get(),
            'adaKunciEnv' => (string) config('sinkron.api_key') !== '',
        ]);
    }
}
