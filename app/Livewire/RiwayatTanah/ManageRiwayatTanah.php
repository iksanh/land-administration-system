<?php

namespace App\Livewire\RiwayatTanah;

use App\Livewire\Concerns\WithRiwayatPenguasaan;
use App\Models\BeritaAcaraPemeriksaan;
use App\Models\Permohonan;
use App\Models\RisalahPanitiaA;
use App\Models\RiwayatPenguasaan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Input Riwayat Penguasaan Tanah — halaman tersendiri, pemilik tunggal data
 * `riwayat_penguasaan` (1:1 per permohonan).
 *
 * Sebelumnya riwayat hanya bisa diisi dari dalam form Berita Acara, padahal
 * teksnya dipakai bersama oleh Berita Acara, Risalah, dan SK. Dipisah ke sini
 * supaya riwayat bisa diisi tanpa lebih dulu membuat Berita Acara, dan supaya
 * penyimpanannya tidak menumpang transaksi dokumen mana pun.
 *
 * Struktur data tidak berubah — tabel, model, dan template cetak tetap sama.
 */
#[Layout('components.layouts.app')]
class ManageRiwayatTanah extends Component
{
    use WithRiwayatPenguasaan;

    public string $search = '';

    /** Tampilkan hanya permohonan yang riwayatnya belum diisi. */
    public bool $hanyaBelumDiisi = false;

    public bool $showForm = false;

    public string $permohonan_id = '';

    public function mount(): void
    {
        if ($pid = request('permohonan')) {
            $this->editFor($pid);
        }
    }

    /** Buka editor riwayat untuk sebuah permohonan. */
    public function editFor(string $permohonanId): void
    {
        $this->permohonan_id = $permohonanId;
        $this->loadRiwayat($permohonanId);
        $this->cancelTypo();
        $this->showForm = true;
    }

    /** Ganti permohonan dari dropdown: muat riwayat milik permohonan itu. */
    public function updatedPermohonanId(string $value): void
    {
        $this->cancelTypo();

        if ($value === '') {
            $this->riwayat_penguasaan = [];

            return;
        }

        $this->loadRiwayat($value);
    }

    protected function rules(): array
    {
        return [
            'permohonan_id' => ['required', 'exists:permohonan,id'],
        ] + $this->riwayatRules();
    }

    public function save(): void
    {
        $data = $this->validate();

        $this->saveRiwayat($data['permohonan_id']);
        // Muat ulang agar kotak teks memantulkan hasil bersih (poin kosong dibuang).
        $this->loadRiwayat($data['permohonan_id']);

        session()->flash('message', 'Riwayat penguasaan berhasil disimpan.');
    }

    /** Kosongkan seluruh poin riwayat permohonan ini. */
    public function clear(string $permohonanId): void
    {
        RiwayatPenguasaan::where('permohonan_id', $permohonanId)->delete();

        if ($this->permohonan_id === $permohonanId) {
            $this->loadRiwayat($permohonanId);
        }
        session()->flash('message', 'Riwayat penguasaan berhasil dikosongkan.');
    }

    public function resetForm(): void
    {
        $this->reset(['permohonan_id', 'showForm']);
        $this->resetRiwayat();
    }

    public function render()
    {
        $list = Permohonan::query()
            ->with(['pemohon', 'riwayatPenguasaan'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where('nomor_registrasi', 'like', $term)
                    ->orWhereHas('pemohon', fn ($p) => $p->where('nama', 'like', $term));
            })
            // Belum diisi = tidak punya record, atau record-nya berisi poin null
            // (saveRiwayat menyimpan null saat seluruh poin kosong).
            ->when($this->hanyaBelumDiisi, fn ($q) => $q->where(fn ($w) => $w
                ->whereDoesntHave('riwayatPenguasaan')
                ->orWhereHas('riwayatPenguasaan', fn ($r) => $r->whereNull('poin'))))
            ->orderBy('nomor_registrasi')
            ->get();

        // Dokumen yang memakai riwayat permohonan terpilih — ditampilkan sebagai
        // peringatan dampak sebelum teksnya diubah.
        $terpilih = $this->permohonan_id
            ? Permohonan::with('pemohon')->find($this->permohonan_id)
            : null;

        return view('livewire.riwayat-tanah.manage-riwayat-tanah', [
            'list' => $list,
            'permohonanList' => Permohonan::with('pemohon')->orderBy('nomor_registrasi')->get(),
            'terpilih' => $terpilih,
            'beritaAcara' => $terpilih
                ? BeritaAcaraPemeriksaan::where('permohonan_id', $terpilih->id)->first()
                : null,
            'risalah' => $terpilih
                ? RisalahPanitiaA::where('permohonan_id', $terpilih->id)->first()
                : null,
        ]);
    }
}
