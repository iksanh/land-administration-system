<?php

namespace App\Livewire\Pemeriksaan;

use App\Enums\PemeriksaanStatusEnum;
use App\Models\MapLayananBerkas;
use App\Models\MstCatatan;
use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;
use App\Support\PemeriksaanSheet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

    // Inline check panel
    public ?string $editingBerkasId = null;
    public string $formStatus = 'PENDING';
    public array $selectedCatatanIds = [];
    public string $customCatatan = '';

    // Print preview modal
    public bool $showPrint = false;

    public function openPrint(): void
    {
        $this->showPrint = true;
    }

    public function closePrint(): void
    {
        $this->showPrint = false;
    }

    public function startPeriksa(string $berkasItemId): void
    {
        $existing = PemeriksaanBerkas::where('permohonan_id', $this->selectedPermohonan)
            ->where('berkas_item_id', $berkasItemId)
            ->first();

        $this->editingBerkasId = $berkasItemId;
        $this->formStatus = $existing?->status?->value ?? 'PENDING';

        $catatan = collect($existing?->catatan ?? []);
        $this->selectedCatatanIds = $catatan->where('is_custom', false)->pluck('id')->filter()->values()->all();
        $this->customCatatan = $catatan->firstWhere('is_custom', true)['teks'] ?? '';
    }

    public function cancelPeriksa(): void
    {
        $this->reset(['editingBerkasId', 'formStatus', 'selectedCatatanIds', 'customCatatan']);
        $this->formStatus = 'PENDING';
    }

    public function savePeriksa(): void
    {
        $this->validate([
            'formStatus' => ['required', Rule::enum(PemeriksaanStatusEnum::class)],
            'selectedCatatanIds' => ['array'],
            'customCatatan' => ['nullable', 'string'],
        ]);

        $catatan = [];
        foreach (MstCatatan::whereIn('id', $this->selectedCatatanIds)->get() as $mc) {
            $catatan[] = ['id' => $mc->id, 'teks' => $mc->teks, 'is_custom' => false];
        }
        if (trim($this->customCatatan) !== '') {
            $catatan[] = ['id' => null, 'teks' => trim($this->customCatatan), 'is_custom' => true];
        }

        PemeriksaanBerkas::updateOrCreate(
            ['permohonan_id' => $this->selectedPermohonan, 'berkas_item_id' => $this->editingBerkasId],
            ['status' => $this->formStatus, 'catatan' => $catatan ?: null, 'petugas_id' => Auth::id()],
        );

        $this->cancelPeriksa();
        session()->flash('message', 'Pemeriksaan berkas tersimpan.');
    }

    public function render()
    {
        $permohonan = $this->selectedPermohonan
            ? Permohonan::with('layanan')->find($this->selectedPermohonan)
            : null;

        $berkasList = $permohonan?->layanan_id
            ? MapLayananBerkas::with('berkasItem')
                ->where('layanan_id', $permohonan->layanan_id)
                ->orderBy('urutan')
                ->get()
                ->pluck('berkasItem')
                ->filter()
            : collect();

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
            $permohonan->loadMissing(['pemohon', 'layanan', 'tanah']);
            [$printParents, $printChildrenMap] = PemeriksaanSheet::build($permohonan);
        }

        return view('livewire.pemeriksaan.manage-pemeriksaan-berkas', [
            'permohonanList' => Permohonan::with('pemohon')->latest('created_at')->get(),
            'permohonan' => $permohonan,
            'berkasList' => $berkasList,
            'pemeriksaan' => $pemeriksaan,
            'catatanOptions' => $catatanOptions,
            'statuses' => PemeriksaanStatusEnum::cases(),
            'printParents' => $printParents,
            'printChildrenMap' => $printChildrenMap,
        ]);
    }
}
