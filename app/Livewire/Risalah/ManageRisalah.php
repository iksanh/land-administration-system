<?php

namespace App\Livewire\Risalah;

use App\Livewire\Concerns\WithOrderedLists;
use App\Livewire\Concerns\WithRiwayatPenguasaan;
use App\Models\BeritaAcaraPemeriksaan;
use App\Models\PanitiaPemeriksa;
use App\Models\Permohonan;
use App\Models\RisalahPanitiaA;
use App\Support\PanitiaResolver;
use App\Support\RisalahDefaults;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Risalah Panitia Pemeriksaan Tanah "A". Superset dari Berita Acara: memakai
 * ulang riwayat penguasaan (WithRiwayatPenguasaan), master panitia, dan pola
 * auto-isi dari permohonan. Menambah field khusus risalah — data pendukung &
 * dasar hukum (daftar terurut via WithOrderedLists) serta pendapat per anggota
 * panitia (disimpan di pivot risalah_panitia).
 */
#[Layout('components.layouts.app')]
class ManageRisalah extends Component
{
    use WithOrderedLists;
    use WithRiwayatPenguasaan;

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $permohonan_id = '';

    public string $nomor_risalah = '';

    public string $tgl_risalah = '';

    public string $jenis_hak = 'Hak Milik';

    public string $jangka_waktu = '-';

    public string $nomor_sk_panitia = '';

    public string $tgl_sk_panitia = '';

    public string $rtrw_kawasan = '';

    public string $perda_rtrw = '';

    public string $tgl_bap = '';

    public string $kesimpulan_tambahan = '';

    /** @var array<int, string> daftar data pendukung (terlampir) */
    public array $data_pendukung = [];

    /** @var array<int, string> daftar dasar hukum */
    public array $dasar_hukum = [];

    /** @var array<int, string> id panitia terpilih, urut sesuai tampil */
    public array $selectedPanitia = [];

    /** @var array<string, string> pendapat per anggota, dipetakan panitia_id => teks */
    public array $pendapat = [];

    /** Modal detail riwayat penguasaan (read-only, referensi Berita Acara). */
    public bool $showRiwayatModal = false;

    // Modal pratinjau cetak (mengikuti pola Pemeriksaan Berkas & Berita Acara):
    // dokumen ditampilkan di layar; tombol Cetak mencetak lewat iframe tersembunyi.
    public bool $showPrint = false;

    public ?string $printId = null;

    public function mount(): void
    {
        if ($pid = request('permohonan')) {
            $this->createFor($pid);
        }
    }

    /** Buka form untuk sebuah permohonan (buat baru atau edit bila sudah ada). */
    public function createFor(string $permohonanId): void
    {
        $existing = RisalahPanitiaA::where('permohonan_id', $permohonanId)->first();

        if ($existing) {
            $this->edit($existing->id);

            return;
        }

        $this->resetForm();
        $this->permohonan_id = $permohonanId;
        $this->tgl_risalah = now()->format('Y-m-d');
        $this->perda_rtrw = RisalahDefaults::PERDA_RTRW;
        $this->dasar_hukum = RisalahDefaults::dasarHukum();
        $this->data_pendukung = [''];
        $this->loadRiwayat($permohonanId);

        // Sinkronkan tanggal BAP bila Berita Acara untuk permohonan ini sudah ada.
        $ba = BeritaAcaraPemeriksaan::where('permohonan_id', $permohonanId)->first();
        $this->tgl_bap = $ba?->tgl_pemeriksaan?->format('Y-m-d') ?? '';

        // Pra-pilih seluruh anggota panitia aktif sesuai urutan.
        $this->selectedPanitia = PanitiaPemeriksa::where('is_active', true)
            ->orderBy('urutan')->orderBy('nama')->pluck('id')->all();
        $this->showForm = true;
    }

    /**
     * Saat memilih permohonan pada form baru: muat riwayat penguasaan (read-only,
     * referensi Berita Acara) dan tanggal BAP agar pratinjau tetap sinkron.
     */
    public function updatedPermohonanId(string $value): void
    {
        if ($this->editingId !== null || $value === '') {
            $this->riwayat_penguasaan = [];

            return;
        }

        $this->loadRiwayat($value);
        $ba = BeritaAcaraPemeriksaan::where('permohonan_id', $value)->first();
        $this->tgl_bap = $ba?->tgl_pemeriksaan?->format('Y-m-d') ?? '';
    }

    /** Buka modal detail riwayat penguasaan (bersumber dari Berita Acara). */
    public function showRiwayatDetail(): void
    {
        $this->showRiwayatModal = true;
    }

    public function closeRiwayatModal(): void
    {
        $this->showRiwayatModal = false;
    }

    /** Buka modal pratinjau cetak untuk sebuah Risalah. */
    public function openPrint(string $id): void
    {
        $this->printId = $id;
        $this->showPrint = true;
    }

    public function closePrint(): void
    {
        $this->reset(['showPrint', 'printId']);
    }

    public function edit(string $id): void
    {
        $r = RisalahPanitiaA::with('panitia')->findOrFail($id);

        $this->editingId = $r->id;
        $this->permohonan_id = $r->permohonan_id;
        $this->nomor_risalah = $r->nomor_risalah ?? '';
        $this->tgl_risalah = $r->tgl_risalah?->format('Y-m-d') ?? '';
        $this->jenis_hak = $r->jenis_hak ?? 'Hak Milik';
        $this->jangka_waktu = $r->jangka_waktu ?? '-';
        $this->nomor_sk_panitia = $r->nomor_sk_panitia ?? '';
        $this->tgl_sk_panitia = $r->tgl_sk_panitia?->format('Y-m-d') ?? '';
        $this->rtrw_kawasan = $r->rtrw_kawasan ?? '';
        $this->perda_rtrw = $r->perda_rtrw ?? '';
        $this->tgl_bap = $r->tgl_bap?->format('Y-m-d') ?? '';
        $this->kesimpulan_tambahan = $r->kesimpulan_tambahan ?? '';
        $this->data_pendukung = ! empty($r->data_pendukung) ? array_values($r->data_pendukung) : [''];
        $this->dasar_hukum = ! empty($r->dasar_hukum) ? array_values($r->dasar_hukum) : RisalahDefaults::dasarHukum();
        $this->loadRiwayat($r->permohonan_id);

        $this->selectedPanitia = $r->panitia->pluck('id')->all();
        $this->pendapat = $r->panitia->mapWithKeys(
            fn ($p) => [$p->id => $p->pivot->pendapat ?? ''],
        )->all();
        $this->showForm = true;
    }

    protected function rules(): array
    {
        return [
            'permohonan_id' => ['required', 'exists:permohonan,id'],
            'nomor_risalah' => ['nullable', 'string', 'max:100'],
            'tgl_risalah' => ['nullable', 'date'],
            'jenis_hak' => ['nullable', 'string', 'max:100'],
            'jangka_waktu' => ['nullable', 'string', 'max:100'],
            'nomor_sk_panitia' => ['nullable', 'string', 'max:150'],
            'tgl_sk_panitia' => ['nullable', 'date'],
            'rtrw_kawasan' => ['nullable', 'string', 'max:200'],
            'perda_rtrw' => ['nullable', 'string', 'max:255'],
            'tgl_bap' => ['nullable', 'date'],
            'kesimpulan_tambahan' => ['nullable', 'string'],
            'data_pendukung' => ['array'],
            'data_pendukung.*' => ['nullable', 'string'],
            'dasar_hukum' => ['array'],
            'dasar_hukum.*' => ['nullable', 'string'],
            'selectedPanitia' => ['array'],
            'selectedPanitia.*' => ['exists:panitia_pemeriksa,id'],
            'pendapat' => ['array'],
            'pendapat.*' => ['nullable', 'string'],
        ];
        // Riwayat penguasaan tidak divalidasi/disimpan di sini — bersifat read-only,
        // referensi dari Berita Acara (diedit di modul Berita Acara Lapang).
    }

    public function save(): void
    {
        $data = $this->validate();

        $r = DB::transaction(function () use ($data) {
            $r = RisalahPanitiaA::updateOrCreate(
                ['permohonan_id' => $data['permohonan_id']],
                [
                    'nomor_risalah' => $data['nomor_risalah'] ?: null,
                    'tgl_risalah' => $data['tgl_risalah'] ?: null,
                    'jenis_hak' => $data['jenis_hak'] ?: null,
                    'jangka_waktu' => $data['jangka_waktu'] ?: null,
                    'nomor_sk_panitia' => $data['nomor_sk_panitia'] ?: null,
                    'tgl_sk_panitia' => $data['tgl_sk_panitia'] ?: null,
                    'rtrw_kawasan' => $data['rtrw_kawasan'] ?: null,
                    'perda_rtrw' => $data['perda_rtrw'] ?: null,
                    'tgl_bap' => $data['tgl_bap'] ?: null,
                    'kesimpulan_tambahan' => $data['kesimpulan_tambahan'] ?: null,
                    'data_pendukung' => $this->cleanList($this->data_pendukung),
                    'dasar_hukum' => $this->cleanList($this->dasar_hukum),
                ],
            );

            // Riwayat penguasaan TIDAK disimpan dari sini — read-only, bersumber
            // dari Berita Acara (record 1:1 per permohonan diedit di modul BA).

            // Sinkron panitia + simpan urutan tampil dan pendapat per anggota.
            $sync = [];
            foreach (array_values($this->selectedPanitia) as $i => $panitiaId) {
                $sync[$panitiaId] = [
                    'urutan' => $i,
                    'pendapat' => ($this->pendapat[$panitiaId] ?? '') ?: null,
                ];
            }
            $r->panitia()->sync($sync);

            return $r;
        });

        $this->editingId = $r->id;
        session()->flash('message', 'Risalah berhasil disimpan.');
    }

    public function delete(string $id): void
    {
        RisalahPanitiaA::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }
        session()->flash('message', 'Risalah berhasil dihapus.');
    }

    /** Pulihkan daftar dasar hukum ke daftar standar bawaan. */
    public function resetDasarHukum(): void
    {
        $this->dasar_hukum = RisalahDefaults::dasarHukum();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'permohonan_id', 'nomor_risalah', 'tgl_risalah', 'jenis_hak',
            'jangka_waktu', 'nomor_sk_panitia', 'tgl_sk_panitia', 'rtrw_kawasan',
            'perda_rtrw', 'tgl_bap', 'kesimpulan_tambahan', 'data_pendukung',
            'dasar_hukum', 'selectedPanitia', 'pendapat', 'showForm', 'showRiwayatModal',
        ]);
        $this->jenis_hak = 'Hak Milik';
        $this->jangka_waktu = '-';
        $this->resetRiwayat();
    }

    public function render()
    {
        // Susun dokumen pratinjau hanya saat modal terbuka. Kepala desa aktif ikut
        // sebagai penandatangan — sama seperti RisalahPrintController.
        $printRisalah = null;
        if ($this->showPrint && $this->printId) {
            $printRisalah = RisalahPanitiaA::with([
                'permohonan.pemohon.desa.kecamatan.kabupaten.provinsi',
                'permohonan.pemohon.desa.kepalaDesaAktif',
                'permohonan.tanah.desa.kecamatan.kabupaten.provinsi',
                'permohonan.tanah.desa.kepalaDesaAktif',
                'permohonan.riwayatPenguasaan',
                'panitia',
            ])->find($this->printId);

            if ($printRisalah) {
                $printRisalah->setRelation('panitia', PanitiaResolver::withKepalaDesa($printRisalah->panitia, $printRisalah->permohonan));
            }
        }

        return view('livewire.risalah.manage-risalah', [
            'printRisalah' => $printRisalah,
            'list' => RisalahPanitiaA::query()
                ->with(['permohonan.pemohon'])
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where('nomor_risalah', 'like', $term)
                        ->orWhereHas('permohonan', fn ($p) => $p->where('nomor_registrasi', 'like', $term))
                        ->orWhereHas('permohonan.pemohon', fn ($p) => $p->where('nama', 'like', $term));
                })
                ->latest('created_at')->get(),
            'permohonanList' => Permohonan::with('pemohon')->orderBy('nomor_registrasi')->get(),
            'panitiaList' => PanitiaPemeriksa::where('is_active', true)->orderBy('urutan')->orderBy('nama')->get(),
            'selectedTanah' => $selectedTanah = $this->permohonan_id
                ? Permohonan::with([
                    'tanah.desa.kecamatan.kabupaten.provinsi', 'tanah.desa.kepalaDesaAktif',
                    'pemohon.desa.kepalaDesaAktif',
                ])->find($this->permohonan_id)
                : null,
            'kepalaDesaOtomatis' => ($selectedTanah?->tanah?->desa ?? $selectedTanah?->pemohon?->desa)?->kepalaDesaAktif ?? collect(),
            // Berita Acara sebagai sumber riwayat penguasaan (read-only di Risalah).
            'beritaAcara' => $this->permohonan_id
                ? BeritaAcaraPemeriksaan::where('permohonan_id', $this->permohonan_id)->first()
                : null,
        ]);
    }
}
