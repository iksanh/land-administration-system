<?php

namespace App\Livewire\BeritaAcara;

use App\Livewire\Concerns\WithRiwayatPenguasaan;
use App\Models\BeritaAcaraPemeriksaan;
use App\Models\PanitiaPemeriksa;
use App\Models\Permohonan;
use App\Models\SkPanitia;
use App\Support\PanitiaResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Berita Acara Pemeriksaan Lapang (BAPL). Data teknis tanah (luas, PBT, NIB,
 * batas, penggunaan) diambil otomatis dari permohonan terpilih; komponen ini
 * hanya mengelola field khusus berita acara + anggota panitia + lampiran foto.
 */
#[Layout('components.layouts.app')]
class ManageBeritaAcara extends Component
{
    use WithFileUploads;
    use WithRiwayatPenguasaan;

    public const DEFAULT_KEBERATAN = 'Bahwa pada saat kami melakukan Pemeriksaan Lapang tidak ada yang mengajukan keberatan atau merasa keberatan terhadap Permohonan Hak dimaksud.';

    public const DEFAULT_PERDA = 'Peraturan Daerah Kabupaten Bone Bolango Nomor 5 Tahun 2021 tentang Rencana Tata Ruang Wilayah Kabupaten Bone Bolango Tahun 2021-2041';

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $permohonan_id = '';

    public string $nomor_ba = '';

    public string $tgl_pemeriksaan = '';

    public string $keadaan_tanah = '';

    public string $catatan_keberatan = '';

    public string $perda_rtrw = '';

    /**
     * SK panitia yang dipakai dokumen ini. Diisi otomatis dari SK aktif saat
     * berita acara dibuat lalu DIKUNCI — mengganti SK aktif tidak mengubah
     * berita acara yang sudah tersimpan.
     */
    public ?string $sk_panitia_id = null;

    /** @var array<int, string> dipilihnya panitia (id), urut sesuai tampil */
    public array $selectedPanitia = [];

    /** @var array berkas foto baru yang diunggah */
    public array $newPhotos = [];

    // Modal pratinjau cetak (mengikuti pola Pemeriksaan Berkas): dokumen ditampilkan
    // di layar; tombol Cetak mencetak lewat iframe tersembunyi ke rute standalone.
    public bool $showPrint = false;

    public ?string $printId = null;

    public function mount(): void
    {
        if ($pid = request('permohonan')) {
            $this->createFor($pid);
        }
    }

    /** Buka form untuk sebuah permohonan (buat baru atau edit jika sudah ada). */
    public function createFor(string $permohonanId): void
    {
        $existing = BeritaAcaraPemeriksaan::where('permohonan_id', $permohonanId)->first();

        if ($existing) {
            $this->edit($existing->id);

            return;
        }

        $this->resetForm();
        $this->permohonan_id = $permohonanId;
        $this->catatan_keberatan = self::DEFAULT_KEBERATAN;
        $this->perda_rtrw = self::DEFAULT_PERDA;
        $this->tgl_pemeriksaan = now()->format('Y-m-d');
        $this->loadRiwayat($permohonanId);
        // SK panitia yang berlaku menentukan penandatangan; seluruh anggotanya
        // dipra-pilih sesuai urutan tanda tangan.
        $this->sk_panitia_id = PanitiaResolver::skAktif()?->id;
        $this->selectedPanitia = PanitiaResolver::anggotaSk($this->sk_panitia_id)->pluck('id')->all();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $ba = BeritaAcaraPemeriksaan::with('panitia')->findOrFail($id);

        $this->editingId = $ba->id;
        $this->permohonan_id = $ba->permohonan_id;
        $this->nomor_ba = $ba->nomor_ba ?? '';
        $this->tgl_pemeriksaan = $ba->tgl_pemeriksaan?->format('Y-m-d') ?? '';
        $this->loadRiwayat($ba->permohonan_id);
        $this->keadaan_tanah = $ba->keadaan_tanah ?? '';
        $this->catatan_keberatan = $ba->catatan_keberatan ?? '';
        $this->perda_rtrw = $ba->perda_rtrw ?? '';
        $this->sk_panitia_id = $ba->sk_panitia_id;
        $this->selectedPanitia = $ba->panitia->pluck('id')->all();
        $this->newPhotos = [];
        $this->showForm = true;
    }

    protected function rules(): array
    {
        return [
            'permohonan_id' => ['required', 'exists:permohonan,id'],
            'nomor_ba' => ['nullable', 'string', 'max:100'],
            'tgl_pemeriksaan' => ['nullable', 'date'],
            'keadaan_tanah' => ['nullable', 'string'],
            'catatan_keberatan' => ['nullable', 'string'],
            'perda_rtrw' => ['nullable', 'string', 'max:255'],
            'sk_panitia_id' => ['nullable', 'exists:sk_panitia,id'],
            'selectedPanitia' => ['array'],
            'selectedPanitia.*' => ['exists:panitia_pemeriksa,id'],
            'newPhotos' => ['array'],
            'newPhotos.*' => ['image', 'max:5120'], // maks 5 MB / foto
        ] + $this->riwayatRules();
    }

    /**
     * Ganti SK panitia pada form: susunan penandatangan ikut disegarkan dari SK
     * yang baru dipilih agar nomor SK dan daftar nama tidak pernah berselisih.
     */
    public function updatedSkPanitiaId($value): void
    {
        $this->selectedPanitia = PanitiaResolver::anggotaSk($value ?: null)->pluck('id')->all();
    }

    /**
     * Kandidat penandatangan: anggota aktif di bawah SK dokumen ini, ditambah
     * anggota yang terlanjur dipilih (mis. sudah dinonaktifkan setelah dokumen
     * dibuat) supaya centangnya tidak hilang diam-diam.
     */
    private function panitiaPilihan()
    {
        $skId = $this->sk_panitia_id;
        $terpilih = array_values($this->selectedPanitia);

        if (! $skId && $terpilih === []) {
            return collect();
        }

        return PanitiaPemeriksa::query()
            ->where(function ($q) use ($skId, $terpilih) {
                if ($skId) {
                    $q->where(fn ($w) => $w->where('sk_panitia_id', $skId)->where('is_active', true));
                }
                if ($terpilih !== []) {
                    $q->orWhereIn('id', $terpilih);
                }
            })
            ->orderBy('urutan')->orderBy('nama')->get();
    }

    public function save(): void
    {
        $data = $this->validate();

        $ba = DB::transaction(function () use ($data) {
            $ba = BeritaAcaraPemeriksaan::updateOrCreate(
                ['permohonan_id' => $data['permohonan_id']],
                [
                    'nomor_ba' => $data['nomor_ba'] ?: null,
                    'tgl_pemeriksaan' => $data['tgl_pemeriksaan'] ?: null,
                    'keadaan_tanah' => $data['keadaan_tanah'] ?: null,
                    'catatan_keberatan' => $data['catatan_keberatan'] ?: null,
                    'perda_rtrw' => $data['perda_rtrw'] ?: null,
                    'sk_panitia_id' => $data['sk_panitia_id'] ?: null,
                ],
            );

            // Riwayat penguasaan disimpan sebagai record tersendiri (dipakai ulang
            // oleh Risalah & SK) — lihat trait WithRiwayatPenguasaan.
            $this->saveRiwayat($data['permohonan_id']);

            // Sinkron panitia + simpan urutan tampil.
            $sync = [];
            foreach (array_values($this->selectedPanitia) as $i => $panitiaId) {
                $sync[$panitiaId] = ['urutan' => $i];
            }
            $ba->panitia()->sync($sync);

            // Simpan foto baru ke disk public.
            $nextOrder = (int) $ba->lampiran()->max('urutan');
            foreach ($this->newPhotos as $photo) {
                $path = $photo->store('berita-acara', 'public');
                $ba->lampiran()->create(['path' => $path, 'urutan' => ++$nextOrder]);
            }

            return $ba;
        });

        $this->newPhotos = [];
        $this->editingId = $ba->id;
        session()->flash('message', 'Berita Acara berhasil disimpan.');
    }

    public function removeLampiran(string $lampiranId): void
    {
        if (! $this->editingId) {
            return;
        }

        $ba = BeritaAcaraPemeriksaan::findOrFail($this->editingId);
        $lampiran = $ba->lampiran()->whereKey($lampiranId)->first();

        if ($lampiran) {
            Storage::disk('public')->delete($lampiran->path);
            $lampiran->delete();
        }
    }

    public function delete(string $id): void
    {
        $ba = BeritaAcaraPemeriksaan::with('lampiran')->findOrFail($id);

        foreach ($ba->lampiran as $lampiran) {
            Storage::disk('public')->delete($lampiran->path);
        }
        $ba->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }
        session()->flash('message', 'Berita Acara berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'permohonan_id', 'nomor_ba', 'tgl_pemeriksaan',
            'keadaan_tanah', 'catatan_keberatan',
            'perda_rtrw', 'sk_panitia_id', 'selectedPanitia', 'newPhotos', 'showForm',
        ]);
        $this->resetRiwayat();
    }

    /** Buka modal pratinjau cetak untuk sebuah Berita Acara. */
    public function openPrint(string $id): void
    {
        $this->printId = $id;
        $this->showPrint = true;
    }

    public function closePrint(): void
    {
        $this->reset(['showPrint', 'printId']);
    }

    public function render()
    {
        $editing = $this->editingId
            ? BeritaAcaraPemeriksaan::with('lampiran')->find($this->editingId)
            : null;

        // Susun dokumen pratinjau hanya saat modal terbuka. Kepala desa aktif ikut
        // sebagai penandatangan — sama seperti BeritaAcaraPrintController.
        $printBa = null;
        if ($this->showPrint && $this->printId) {
            $printBa = BeritaAcaraPemeriksaan::with([
                'permohonan.pemohon.desa.kepalaDesaAktif',
                'permohonan.tanah.desa.kecamatan.kabupaten.provinsi',
                'permohonan.tanah.desa.kepalaDesaAktif',
                'permohonan.riwayatPenguasaan',
                'panitia',
                'lampiran',
            ])->find($this->printId);

            if ($printBa) {
                $printBa->setRelation('panitia', PanitiaResolver::withKepalaDesa($printBa->panitia, $printBa->permohonan));
            }
        }

        return view('livewire.berita-acara.manage-berita-acara', [
            'list' => BeritaAcaraPemeriksaan::query()
                ->with(['permohonan.pemohon'])
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where('nomor_ba', 'like', $term)
                        ->orWhereHas('permohonan', fn ($p) => $p->where('nomor_registrasi', 'like', $term))
                        ->orWhereHas('permohonan.pemohon', fn ($p) => $p->where('nama', 'like', $term));
                })
                ->latest('created_at')->get(),
            'permohonanList' => Permohonan::with('pemohon')->orderBy('nomor_registrasi')->get(),
            'panitiaList' => $this->panitiaPilihan(),
            'skList' => SkPanitia::orderByDesc('is_active')->orderByDesc('tanggal')->orderByDesc('created_at')->get(),
            'skAktif' => PanitiaResolver::skAktif(),
            'selectedTanah' => $selectedTanah = $this->permohonan_id
                ? Permohonan::with([
                    'tanah.desa.kecamatan.kabupaten.provinsi', 'tanah.desa.kepalaDesaAktif',
                    'pemohon.desa.kepalaDesaAktif',
                ])->find($this->permohonan_id)
                : null,
            'kepalaDesaOtomatis' => ($selectedTanah?->tanah?->desa ?? $selectedTanah?->pemohon?->desa)?->kepalaDesaAktif ?? collect(),
            'lampiranList' => $editing?->lampiran ?? collect(),
            'printBa' => $printBa,
        ]);
    }
}
