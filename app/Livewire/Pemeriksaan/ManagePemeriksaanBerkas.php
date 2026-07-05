<?php

namespace App\Livewire\Pemeriksaan;

use App\Enums\PemeriksaanStatusEnum;
use App\Models\MapLayananBerkas;
use App\Models\MstCatatan;
use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;
use App\Support\PemeriksaanSheet;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/pemeriksaan_berkas.py — per-permohonan document check.
 * The berkas checklist is the permohonan's layanan mapping (ordered by urutan,
 * like the FastAPI preview). One row per (permohonan, berkas) — enforced by the
 * DB unique constraint and handled here with updateOrCreate (the API's
 * "sudah terdaftar" 400 case). `catatan` is JSONB: an array of
 * {id, teks, is_custom} built from picked master-catatan + an optional custom note.
 */
#[Layout('components.layouts.app')]
class ManagePemeriksaanBerkas extends Component
{
    public string $selectedPermohonan = '';

    /** Filter cepat daftar berkas berdasar nama (agar tak perlu menggulir daftar panjang). */
    public string $search = '';

    // Editor catatan inline (dibuka per berkas). Status di-set langsung lewat
    // tombol segmented — tidak lagi lewat select box di dalam panel.
    public ?string $editingBerkasId = null;

    public array $selectedCatatanIds = [];

    public string $customCatatan = '';

    // Print preview modal
    public bool $showPrint = false;

    /** Ganti permohonan mengosongkan pencarian & menutup editor. */
    public function updatedSelectedPermohonan(): void
    {
        $this->search = '';
        $this->cancelPeriksa();
    }

    public function openPrint(): void
    {
        $this->showPrint = true;
    }

    public function closePrint(): void
    {
        $this->showPrint = false;
    }

    /**
     * Set status berkas dalam satu klik. PENDING (default "belum diperiksa") tak
     * disimpan — recordnya dihapus. OK langsung selesai; REVISI/TOLAK butuh
     * alasan sehingga editor catatan dibuka otomatis. Catatan yang sudah ada
     * dipertahankan saat status berubah.
     */
    public function setStatus(string $berkasItemId, string $status): void
    {
        // Nilai berasal dari tombol segmented (enum), tetap divalidasi defensif.
        if (PemeriksaanStatusEnum::tryFrom($status) === null) {
            return;
        }

        if ($status === PemeriksaanStatusEnum::PENDING->value) {
            PemeriksaanBerkas::where('permohonan_id', $this->selectedPermohonan)
                ->where('berkas_item_id', $berkasItemId)
                ->delete();

            if ($this->editingBerkasId === $berkasItemId) {
                $this->cancelPeriksa();
            }
            session()->flash('message', 'Berkas dikembalikan ke PENDING.');

            return;
        }

        $existing = PemeriksaanBerkas::where('permohonan_id', $this->selectedPermohonan)
            ->where('berkas_item_id', $berkasItemId)
            ->first();

        PemeriksaanBerkas::updateOrCreate(
            ['permohonan_id' => $this->selectedPermohonan, 'berkas_item_id' => $berkasItemId],
            ['status' => $status, 'catatan' => $existing?->catatan, 'petugas_id' => Auth::id()],
        );

        // REVISI/TOLAK sebaiknya disertai alasan — buka editor catatan langsung.
        // OK tak perlu catatan; tutup editor bila kebetulan terbuka untuk baris ini.
        if (in_array($status, [PemeriksaanStatusEnum::REVISI->value, PemeriksaanStatusEnum::TOLAK->value], true)) {
            $this->openCatatan($berkasItemId);
        } elseif ($this->editingBerkasId === $berkasItemId) {
            $this->cancelPeriksa();
        }
    }

    /** Buka editor catatan untuk sebuah berkas (memuat catatan yang tersimpan). */
    public function openCatatan(string $berkasItemId): void
    {
        $existing = PemeriksaanBerkas::where('permohonan_id', $this->selectedPermohonan)
            ->where('berkas_item_id', $berkasItemId)
            ->first();

        $this->editingBerkasId = $berkasItemId;

        $catatan = collect($existing?->catatan ?? []);
        $this->selectedCatatanIds = $catatan->where('is_custom', false)->pluck('id')->filter()->values()->all();
        $this->customCatatan = $catatan->firstWhere('is_custom', true)['teks'] ?? '';
    }

    public function cancelPeriksa(): void
    {
        $this->reset(['editingBerkasId', 'selectedCatatanIds', 'customCatatan']);
    }

    /** Simpan catatan untuk berkas yang sedang diedit (status tetap). */
    public function saveCatatan(): void
    {
        $this->validate([
            'selectedCatatanIds' => ['array'],
            'customCatatan' => ['nullable', 'string'],
        ]);

        $record = PemeriksaanBerkas::where('permohonan_id', $this->selectedPermohonan)
            ->where('berkas_item_id', $this->editingBerkasId)
            ->first();

        // Catatan hanya bermakna bila berkas sudah punya status (bukan PENDING).
        if (! $record) {
            $this->cancelPeriksa();

            return;
        }

        $catatan = [];
        foreach (MstCatatan::whereIn('id', $this->selectedCatatanIds)->get() as $mc) {
            $catatan[] = ['id' => $mc->id, 'teks' => $mc->teks, 'is_custom' => false];
        }
        if (trim($this->customCatatan) !== '') {
            $catatan[] = ['id' => null, 'teks' => trim($this->customCatatan), 'is_custom' => true];
        }

        $record->update(['catatan' => $catatan ?: null, 'petugas_id' => Auth::id()]);

        $this->cancelPeriksa();
        session()->flash('message', 'Catatan pemeriksaan tersimpan.');
    }

    public function render()
    {
        $permohonan = $this->selectedPermohonan
            ? Permohonan::with('layanan')->find($this->selectedPermohonan)
            : null;

        $allBerkas = $permohonan?->layanan_id
            ? MapLayananBerkas::with('berkasItem')
                ->where('layanan_id', $permohonan->layanan_id)
                ->orderBy('urutan')
                ->get()
                ->pluck('berkasItem')
                ->filter()
            : collect();

        // Filter nama berkas (case-insensitive) tanpa mengubah urutan.
        $berkasList = $this->search !== ''
            ? $allBerkas->filter(fn ($b) => str_contains(mb_strtolower($b->nama), mb_strtolower(trim($this->search))))->values()
            : $allBerkas;

        $pemeriksaan = $this->selectedPermohonan
            ? PemeriksaanBerkas::where('permohonan_id', $this->selectedPermohonan)->get()->keyBy('berkas_item_id')
            : collect();

        $catatanOptions = $this->editingBerkasId
            ? MstCatatan::where('is_active', true)
                ->where(fn ($q) => $q->whereNull('berkas_item_id')->orWhere('berkas_item_id', $this->editingBerkasId))
                ->orderBy('teks')
                ->get()
            : collect();

        // Build the print-sheet data only while the preview modal is open.
        $printParents = [];
        $printChildrenMap = [];
        if ($this->showPrint && $permohonan) {
            $permohonan->loadMissing(['pemohon', 'layanan', 'tanah.desa.kecamatan']);
            [$printParents, $printChildrenMap] = PemeriksaanSheet::build($permohonan);
        }

        return view('livewire.pemeriksaan.manage-pemeriksaan-berkas', [
            'permohonanList' => Permohonan::with('pemohon')->latest('created_at')->get(),
            'permohonan' => $permohonan,
            'berkasList' => $berkasList,
            'hasBerkas' => $allBerkas->isNotEmpty(),
            'pemeriksaan' => $pemeriksaan,
            'catatanOptions' => $catatanOptions,
            'statuses' => PemeriksaanStatusEnum::cases(),
            'printParents' => $printParents,
            'printChildrenMap' => $printChildrenMap,
        ]);
    }
}
