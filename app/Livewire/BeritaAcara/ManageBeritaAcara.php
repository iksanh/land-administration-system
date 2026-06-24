<?php

namespace App\Livewire\BeritaAcara;

use App\Models\BeritaAcaraPemeriksaan;
use App\Models\PanitiaPemeriksa;
use App\Models\Permohonan;
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

    public const DEFAULT_KEBERATAN = 'Bahwa pada saat kami melakukan Pemeriksaan Lapang tidak ada yang mengajukan keberatan atau merasa keberatan terhadap Permohonan Hak dimaksud.';

    public const DEFAULT_PERDA = 'Peraturan Daerah Kabupaten Bone Bolango Nomor 5 Tahun 2021 tentang Rencana Tata Ruang Wilayah Kabupaten Bone Bolango Tahun 2021-2041';

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $permohonan_id = '';

    public string $nomor_ba = '';

    public string $tgl_pemeriksaan = '';

    /** @var array<int, string> daftar poin riwayat penguasaan */
    public array $riwayat_penguasaan = [];

    public string $keadaan_tanah = '';

    public string $catatan_keberatan = '';

    public string $perda_rtrw = '';

    /** @var array<int, string> dipilihnya panitia (id), urut sesuai tampil */
    public array $selectedPanitia = [];

    /** @var array berkas foto baru yang diunggah */
    public array $newPhotos = [];

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
        $this->riwayat_penguasaan = ['']; // satu poin kosong agar form siap diisi
        // Pra-pilih seluruh anggota panitia aktif sesuai urutan.
        $this->selectedPanitia = PanitiaPemeriksa::where('is_active', true)
            ->orderBy('urutan')->orderBy('nama')->pluck('id')->all();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $ba = BeritaAcaraPemeriksaan::with('panitia')->findOrFail($id);

        $this->editingId = $ba->id;
        $this->permohonan_id = $ba->permohonan_id;
        $this->nomor_ba = $ba->nomor_ba ?? '';
        $this->tgl_pemeriksaan = $ba->tgl_pemeriksaan?->format('Y-m-d') ?? '';
        $this->riwayat_penguasaan = ! empty($ba->riwayat_penguasaan) ? $ba->riwayat_penguasaan : [''];
        $this->keadaan_tanah = $ba->keadaan_tanah ?? '';
        $this->catatan_keberatan = $ba->catatan_keberatan ?? '';
        $this->perda_rtrw = $ba->perda_rtrw ?? '';
        $this->selectedPanitia = $ba->panitia->pluck('id')->all();
        $this->newPhotos = [];
        $this->showForm = true;
    }

    /** Tambah satu poin riwayat penguasaan kosong di akhir. */
    public function addRiwayat(): void
    {
        $this->riwayat_penguasaan[] = '';
    }

    public function removeRiwayat(int $index): void
    {
        unset($this->riwayat_penguasaan[$index]);
        $this->riwayat_penguasaan = array_values($this->riwayat_penguasaan);
    }

    /** Geser poin ke atas (-1) atau ke bawah (+1). */
    public function moveRiwayat(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->riwayat_penguasaan[$index], $this->riwayat_penguasaan[$target])) {
            return;
        }

        [$this->riwayat_penguasaan[$index], $this->riwayat_penguasaan[$target]]
            = [$this->riwayat_penguasaan[$target], $this->riwayat_penguasaan[$index]];
    }

    protected function rules(): array
    {
        return [
            'permohonan_id' => ['required', 'exists:permohonan,id'],
            'nomor_ba' => ['nullable', 'string', 'max:100'],
            'tgl_pemeriksaan' => ['nullable', 'date'],
            'riwayat_penguasaan' => ['array'],
            'riwayat_penguasaan.*' => ['nullable', 'string'],
            'keadaan_tanah' => ['nullable', 'string'],
            'catatan_keberatan' => ['nullable', 'string'],
            'perda_rtrw' => ['nullable', 'string', 'max:255'],
            'selectedPanitia' => ['array'],
            'selectedPanitia.*' => ['exists:panitia_pemeriksa,id'],
            'newPhotos' => ['array'],
            'newPhotos.*' => ['image', 'max:5120'], // maks 5 MB / foto
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // Buang poin kosong & rapikan urutan sebelum disimpan.
        $riwayat = array_values(array_filter(
            array_map('trim', $data['riwayat_penguasaan']),
            fn ($v) => $v !== '',
        ));

        $ba = DB::transaction(function () use ($data, $riwayat) {
            $ba = BeritaAcaraPemeriksaan::updateOrCreate(
                ['permohonan_id' => $data['permohonan_id']],
                [
                    'nomor_ba' => $data['nomor_ba'] ?: null,
                    'tgl_pemeriksaan' => $data['tgl_pemeriksaan'] ?: null,
                    'riwayat_penguasaan' => $riwayat ?: null,
                    'keadaan_tanah' => $data['keadaan_tanah'] ?: null,
                    'catatan_keberatan' => $data['catatan_keberatan'] ?: null,
                    'perda_rtrw' => $data['perda_rtrw'] ?: null,
                ],
            );

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
            'riwayat_penguasaan', 'keadaan_tanah', 'catatan_keberatan',
            'perda_rtrw', 'selectedPanitia', 'newPhotos', 'showForm',
        ]);
    }

    public function render()
    {
        $editing = $this->editingId
            ? BeritaAcaraPemeriksaan::with('lampiran')->find($this->editingId)
            : null;

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
            'panitiaList' => PanitiaPemeriksa::where('is_active', true)->orderBy('urutan')->orderBy('nama')->get(),
            'selectedTanah' => $this->permohonan_id
                ? Permohonan::with(['tanah.desa.kecamatan.kabupaten.provinsi', 'pemohon'])->find($this->permohonan_id)
                : null,
            'lampiranList' => $editing?->lampiran ?? collect(),
        ]);
    }
}
