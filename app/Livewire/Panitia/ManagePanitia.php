<?php

namespace App\Livewire\Panitia;

use App\Enums\PeranPanitiaEnum;
use App\Models\PanitiaPemeriksa;
use App\Models\SkPanitia;
use App\Support\PanitiaResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Config Panitia — kelola SK pembentukan Panitia Pemeriksa Tanah "A" beserta
 * susunan anggotanya. Halaman berbentuk master-detail: daftar SK di atas,
 * anggota SK terpilih di bawah.
 *
 * Hanya satu SK boleh aktif; SK aktif itulah yang otomatis dipakai saat
 * menyusun Berita Acara & Risalah, sehingga nomor SK yang tercetak selalu
 * konsisten dengan daftar penandatangannya.
 */
#[Layout('components.layouts.app')]
class ManagePanitia extends Component
{
    public string $search = '';

    /** SK yang sedang dikelola anggotanya. */
    public ?string $skId = null;

    // ---- Form SK -------------------------------------------------------
    public bool $showSkForm = false;

    public ?string $editingSkId = null;

    public string $sk_nomor = '';

    public string $sk_tanggal = '';

    public string $sk_tentang = '';

    public string $sk_berlaku_mulai = '';

    public string $sk_berlaku_sampai = '';

    public string $sk_keterangan = '';

    // ---- Form anggota --------------------------------------------------
    public ?string $editingId = null;

    public string $nama = '';

    public string $nip = '';

    public string $jabatan = '';

    public string $peran = 'ANGGOTA';

    public int $urutan = 0;

    public bool $is_active = true;

    public function mount(): void
    {
        $this->skId = PanitiaResolver::skAktif()?->id
            ?? SkPanitia::orderByDesc('created_at')->value('id');
    }

    // =====================================================================
    // SK
    // =====================================================================

    protected function skRules(): array
    {
        return [
            'sk_nomor' => ['required', 'string', 'max:150'],
            'sk_tanggal' => ['nullable', 'date'],
            'sk_tentang' => ['nullable', 'string'],
            'sk_berlaku_mulai' => ['nullable', 'date'],
            'sk_berlaku_sampai' => ['nullable', 'date', 'after_or_equal:sk_berlaku_mulai'],
            'sk_keterangan' => ['nullable', 'string'],
        ];
    }

    public function newSk(): void
    {
        $this->resetSkForm();
        $this->showSkForm = true;
    }

    public function editSk(string $id): void
    {
        $sk = SkPanitia::findOrFail($id);

        $this->editingSkId = $sk->id;
        $this->sk_nomor = $sk->nomor;
        $this->sk_tanggal = $sk->tanggal?->format('Y-m-d') ?? '';
        $this->sk_tentang = $sk->tentang ?? '';
        $this->sk_berlaku_mulai = $sk->berlaku_mulai?->format('Y-m-d') ?? '';
        $this->sk_berlaku_sampai = $sk->berlaku_sampai?->format('Y-m-d') ?? '';
        $this->sk_keterangan = $sk->keterangan ?? '';
        $this->showSkForm = true;
    }

    public function saveSk(): void
    {
        $this->validate($this->skRules());

        $payload = [
            'nomor' => $this->sk_nomor,
            'tanggal' => $this->sk_tanggal ?: null,
            'tentang' => $this->sk_tentang ?: null,
            'berlaku_mulai' => $this->sk_berlaku_mulai ?: null,
            'berlaku_sampai' => $this->sk_berlaku_sampai ?: null,
            'keterangan' => $this->sk_keterangan ?: null,
        ];

        if ($this->editingSkId) {
            SkPanitia::findOrFail($this->editingSkId)->update($payload);
            session()->flash('message', 'SK panitia berhasil diperbarui.');
        } else {
            // SK pertama langsung aktif supaya Berita Acara & Risalah punya rujukan.
            $payload['is_active'] = ! SkPanitia::where('is_active', true)->exists();
            $sk = SkPanitia::create($payload);
            $this->skId = $sk->id;
            session()->flash('message', 'SK panitia berhasil ditambahkan. Tambahkan anggotanya di bawah.');
        }

        $this->resetSkForm();
    }

    /** Jadikan SK ini satu-satunya yang aktif. */
    public function activateSk(string $id): void
    {
        $sk = SkPanitia::findOrFail($id);

        DB::transaction(function () use ($sk) {
            SkPanitia::where('is_active', true)->whereKeyNot($sk->id)->update(['is_active' => false]);
            $sk->update(['is_active' => true]);
        });

        $this->skId = $sk->id;
        session()->flash('message', 'SK '.$sk->nomor.' kini aktif dan otomatis dipakai pada Berita Acara & Risalah baru.');
    }

    /**
     * Salin susunan anggota SK ini ke SK baru — cara cepat mengganti SK tanpa
     * mengetik ulang seluruh anggota. SK salinan belum aktif; aktifkan setelah
     * nomor & tanggalnya disesuaikan.
     */
    public function duplicateSk(string $id): void
    {
        $sumber = SkPanitia::with('anggota')->findOrFail($id);

        $salinan = DB::transaction(function () use ($sumber) {
            $salinan = SkPanitia::create([
                'nomor' => $sumber->nomor.' (salinan)',
                'tentang' => $sumber->tentang,
                'is_active' => false,
            ]);

            foreach ($sumber->anggota as $anggota) {
                $salinan->anggota()->create($anggota->only(['nama', 'nip', 'jabatan', 'peran', 'urutan', 'is_active']));
            }

            return $salinan;
        });

        $this->skId = $salinan->id;
        $this->editSk($salinan->id);
        session()->flash('message', 'Susunan panitia disalin. Perbarui nomor & tanggal SK, lalu aktifkan.');
    }

    public function deleteSk(string $id): void
    {
        $sk = SkPanitia::findOrFail($id);

        // Menghapus SK ikut menghapus anggotanya (cascade), yang akan mencabut
        // penandatangan dari Berita Acara / Risalah yang sudah terbit.
        if ($this->skTerpakai($id)) {
            session()->flash('error', 'SK '.$sk->nomor.' tidak dapat dihapus karena anggotanya sudah menandatangani Berita Acara / Risalah. Cukup aktifkan SK lain sebagai gantinya.');

            return;
        }

        $sk->delete();

        if ($this->skId === $id) {
            $this->skId = PanitiaResolver::skAktif()?->id ?? SkPanitia::orderByDesc('created_at')->value('id');
        }
        $this->resetForm();
        session()->flash('message', 'SK panitia berhasil dihapus.');
    }

    public function selectSk(string $id): void
    {
        $this->skId = $id;
        $this->resetForm();
    }

    public function resetSkForm(): void
    {
        $this->reset([
            'editingSkId', 'sk_nomor', 'sk_tanggal', 'sk_tentang',
            'sk_berlaku_mulai', 'sk_berlaku_sampai', 'sk_keterangan', 'showSkForm',
        ]);
    }

    /** Apakah anggota SK ini sudah dipakai pada dokumen yang terbit? */
    private function skTerpakai(string $skId): bool
    {
        $anggotaIds = PanitiaPemeriksa::where('sk_panitia_id', $skId)->pluck('id');

        if ($anggotaIds->isEmpty()) {
            return false;
        }

        return DB::table('berita_acara_panitia')->whereIn('panitia_id', $anggotaIds)->exists()
            || DB::table('risalah_panitia')->whereIn('panitia_id', $anggotaIds)->exists();
    }

    // =====================================================================
    // Anggota
    // =====================================================================

    protected function rules(): array
    {
        return [
            'skId' => ['required', 'exists:sk_panitia,id'],
            'nama' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:200'],
            'peran' => ['required', Rule::enum(PeranPanitiaEnum::class)],
            'urutan' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'skId.required' => 'Pilih atau buat SK panitia terlebih dahulu sebelum menambah anggota.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['sk_panitia_id'] = $data['skId'];
        unset($data['skId']);

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
        $this->skId = $p->sk_panitia_id;
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
        $skTerpilih = $this->skId ? SkPanitia::find($this->skId) : null;

        return view('livewire.panitia.manage-panitia', [
            'skList' => SkPanitia::withCount('anggota')
                ->orderByDesc('is_active')->orderByDesc('tanggal')->orderByDesc('created_at')
                ->get(),
            'skTerpilih' => $skTerpilih,
            'panitiaList' => $skTerpilih
                ? PanitiaPemeriksa::where('sk_panitia_id', $skTerpilih->id)
                    ->when($this->search !== '', function ($q) {
                        $term = '%'.trim($this->search).'%';
                        $q->where(fn ($w) => $w->where('nama', 'like', $term)
                            ->orWhere('nip', 'like', $term)
                            ->orWhere('jabatan', 'like', $term));
                    })
                    ->orderBy('urutan')->orderBy('nama')->get()
                : collect(),
            'peranOptions' => PeranPanitiaEnum::cases(),
        ]);
    }
}
