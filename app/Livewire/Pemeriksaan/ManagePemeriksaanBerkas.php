<?php

namespace App\Livewire\Pemeriksaan;

use App\Enums\PemeriksaanStatusEnum;
use App\Enums\PermohonanStatusEnum;
use App\Models\MapLayananBerkas;
use App\Models\MstCatatan;
use App\Models\PemeriksaanBerkas;
use App\Models\PemeriksaanBerkasFile;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Support\PemeriksaanSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

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
    use WithFileUploads;

    public string $selectedPermohonan = '';

    /**
     * Kata kunci combobox pemilih permohonan (no. registrasi / nama / NIK).
     * Difilter di server dan dibatasi PERMOHONAN_LIMIT hasil agar dropdown
     * tetap ringan saat data permohonan sudah banyak.
     */
    public string $permohonanSearch = '';

    private const PERMOHONAN_LIMIT = 20;

    /** Filter cepat daftar berkas berdasar nama (agar tak perlu menggulir daftar panjang). */
    public string $search = '';

    // Editor catatan inline (dibuka per berkas). Status di-set langsung lewat
    // tombol segmented — tidak lagi lewat select box di dalam panel.
    public ?string $editingBerkasId = null;

    public array $selectedCatatanIds = [];

    public string $customCatatan = '';

    // Print preview modal
    public bool $showPrint = false;

    // Unggah berkas (massal). $uploads = file sementara yang di-drag; $uploadMap
    // memetakan index file -> berkas_item_id (tebakan dari kode, bisa dikoreksi).
    public bool $showUpload = false;

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var array<int, string> index file -> berkas_item_id terpilih */
    public array $uploadMap = [];

    private const UPLOAD_MAX_KB = 10240; // 10 MB / file

    /**
     * Dukung tautan langsung dari halaman lain (mis. tombol aksi di
     * /permohonan): /pemeriksaan-berkas?permohonan=<id> membuka halaman
     * dengan permohonan tsb. sudah terpilih.
     */
    public function mount(): void
    {
        $pid = request('permohonan');

        if ($pid && Permohonan::whereKey($pid)->exists()) {
            $this->selectedPermohonan = $pid;
        }
    }

    /** Ganti permohonan mengosongkan pencarian & menutup editor/unggahan. */
    public function updatedSelectedPermohonan(): void
    {
        $this->search = '';
        $this->cancelPeriksa();
        $this->reset(['uploads', 'uploadMap', 'showUpload']);
    }

    /** Pilih permohonan dari dropdown combobox. */
    public function selectPermohonan(string $id): void
    {
        $this->selectedPermohonan = $id;
        $this->permohonanSearch = '';
        $this->updatedSelectedPermohonan();
    }

    /** Lepas pilihan untuk memilih permohonan lain. */
    public function clearPermohonan(): void
    {
        $this->selectedPermohonan = '';
        $this->permohonanSearch = '';
        $this->updatedSelectedPermohonan();
    }

    /** Tahap-tahap alur yang pekerjaannya ada di halaman ini. */
    private const PERIKSA_STAGES = [
        PermohonanStatusEnum::PERIKSA_BERKAS_STAF,
        PermohonanStatusEnum::PERIKSA_BERKAS_KORSUB,
    ];

    /**
     * Kirim manual ke tahap berikutnya (Staf → Korsub, atau Korsub → Proses
     * Daftar). Tidak mensyaratkan semua berkas OK — keputusan ada di petugas;
     * ringkasan hasil pemeriksaan direkam di catatan audit log. Hanya gerbang
     * role tahap yang ditegakkan.
     */
    public function selesaiPeriksa(): void
    {
        $p = Permohonan::findOrFail($this->selectedPermohonan);

        if (! in_array($p->status, self::PERIKSA_STAGES, true)) {
            return;
        }

        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! collect($p->status->allowedRoles())->contains(fn ($r) => $user->hasRole($r)))) {
            session()->flash('error', "Anda tidak berwenang menyelesaikan tahap \"{$p->status->label()}\" — tahap ini diproses oleh role {$p->status->allowedRoleLabels()}.");

            return;
        }

        $stat = $this->periksaStat($p);
        $next = $p->status->next();
        $old = $p->status;

        DB::transaction(function () use ($p, $old, $next, $stat) {
            $p->update(['status' => $next]);

            $belum = $stat['total'] - $stat['checked'];
            $bermasalah = $stat['checked'] - $stat['ok'];

            PermohonanAuditLog::create([
                'permohonan_id' => $p->id,
                'status_sebelumnya' => $old,
                'status_baru' => $next,
                'petugas_id' => Auth::id(),
                'catatan' => "{$old->label()} selesai — {$stat['ok']}/{$stat['total']} OK"
                    .($bermasalah > 0 ? ", {$bermasalah} revisi/tolak" : '')
                    .($belum > 0 ? ", {$belum} belum diperiksa" : '').'.',
            ]);
        });

        session()->flash('message', "Status maju ke {$next->label()}.");
    }

    /** Hitung progres pemeriksaan untuk checklist permohonan ini. */
    private function periksaStat(Permohonan $p): array
    {
        $berkasIds = $p->layanan_id
            ? MapLayananBerkas::where('layanan_id', $p->layanan_id)->pluck('berkas_item_id')
            : collect();

        $rows = PemeriksaanBerkas::where('permohonan_id', $p->id)
            ->whereIn('berkas_item_id', $berkasIds)
            ->get();

        return [
            'total' => $berkasIds->count(),
            'checked' => $rows->count(),
            'ok' => $rows->where('status', PemeriksaanStatusEnum::OK)->count(),
        ];
    }

    public function openPrint(): void
    {
        $this->showPrint = true;
    }

    public function closePrint(): void
    {
        $this->showPrint = false;
    }

    /** Buka modal unggah berkas (kosongkan sisa unggahan sebelumnya). */
    public function openUpload(): void
    {
        $this->reset(['uploads', 'uploadMap']);
        $this->showUpload = true;
    }

    public function closeUpload(): void
    {
        $this->reset(['uploads', 'uploadMap', 'showUpload']);
    }

    /**
     * Saat file baru masuk, tebak pemetaan berkas dari kode di nama file untuk
     * setiap file yang belum punya pilihan; buang pemetaan file yang sudah dilepas.
     */
    public function updatedUploads(): void
    {
        $candidates = $this->candidateBerkas();

        foreach ($this->uploads as $i => $file) {
            if (! array_key_exists($i, $this->uploadMap)) {
                $this->uploadMap[$i] = $this->guessBerkasId($file->getClientOriginalName(), $candidates);
            }
        }

        // Lepas pemetaan untuk file yang sudah tidak ada lagi di daftar unggahan.
        $this->uploadMap = array_intersect_key($this->uploadMap, $this->uploads);
    }

    /** Buang satu file dari antrean unggah sebelum disimpan. */
    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index], $this->uploadMap[$index]);
    }

    /** Daftar berkas (MstBerkasItem) milik layanan permohonan terpilih. */
    private function candidateBerkas(): Collection
    {
        $p = $this->selectedPermohonan ? Permohonan::find($this->selectedPermohonan) : null;

        if (! $p || ! $p->layanan_id) {
            return collect();
        }

        return MapLayananBerkas::with('berkasItem')
            ->where('layanan_id', $p->layanan_id)
            ->orderBy('urutan')
            ->get()
            ->pluck('berkasItem')
            ->filter()
            ->values();
    }

    /**
     * Tebak berkas_item_id dari nama file dengan mencocokkan `kode` berkas.
     * Normalisasi ke huruf/angka besar; kode terpanjang diprioritaskan agar
     * kode pendek (mis. "KK") tidak mengalahkan kode yang lebih spesifik.
     * Mengembalikan '' bila tak ada yang cocok (dipilih manual).
     */
    private function guessBerkasId(string $filename, Collection $candidates): string
    {
        $norm = preg_replace('/[^A-Z0-9]/', '', strtoupper(pathinfo($filename, PATHINFO_FILENAME)));

        if ($norm === '') {
            return '';
        }

        $withKode = $candidates
            ->filter(fn ($b) => filled($b->kode))
            ->sortByDesc(fn ($b) => strlen($b->kode));

        foreach ($withKode as $b) {
            $kode = preg_replace('/[^A-Z0-9]/', '', strtoupper($b->kode));
            if ($kode === '') {
                continue;
            }
            if (str_contains($norm, $kode)) {
                return $b->id;
            }
        }

        return '';
    }

    /**
     * Simpan file yang sudah dipetakan ke disk privat + baris
     * pemeriksaan_berkas_file. File tanpa pemetaan dilewati (bukan gagal).
     * Tidak mengubah status pemeriksaan.
     */
    public function saveUploads(): void
    {
        if (! $this->selectedPermohonan) {
            return;
        }

        $this->validate([
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.self::UPLOAD_MAX_KB],
        ], [
            'uploads.required' => 'Belum ada file yang dipilih.',
            'uploads.*.mimes' => 'Hanya file PDF, JPG, atau PNG yang diizinkan.',
            'uploads.*.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $candidateIds = $this->candidateBerkas()->pluck('id')->all();

        $saved = 0;
        $skipped = 0;
        foreach ($this->uploads as $i => $file) {
            $berkasId = $this->uploadMap[$i] ?? '';
            if ($berkasId === '' || ! in_array($berkasId, $candidateIds, true)) {
                $skipped++;

                continue;
            }

            $path = $file->store('pemeriksaan/'.$this->selectedPermohonan, 'local');

            PemeriksaanBerkasFile::create([
                'permohonan_id' => $this->selectedPermohonan,
                'berkas_item_id' => $berkasId,
                'file_path' => $path,
                'nama_asli' => $file->getClientOriginalName(),
                'ukuran' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'uploaded_by' => Auth::id(),
            ]);
            $saved++;
        }

        $this->reset(['uploads', 'uploadMap', 'showUpload']);

        $msg = "{$saved} file tersimpan";
        if ($skipped > 0) {
            $msg .= ", {$skipped} dilewati (belum dipetakan ke berkas)";
        }
        session()->flash('message', $msg.'.');
    }

    /** Hapus satu file lampiran yang sudah tersimpan (dari disk + DB). */
    public function deleteFile(string $fileId): void
    {
        $file = PemeriksaanBerkasFile::where('permohonan_id', $this->selectedPermohonan)
            ->whereKey($fileId)
            ->first();

        if (! $file) {
            return;
        }

        Storage::disk('local')->delete($file->file_path);
        $file->delete();

        session()->flash('message', 'File dihapus.');
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
            ? Permohonan::with(['layanan', 'pemohon'])->find($this->selectedPermohonan)
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

        // File lampiran yang sudah tersimpan, dikelompokkan per berkas.
        $filesMap = $this->selectedPermohonan
            ? PemeriksaanBerkasFile::where('permohonan_id', $this->selectedPermohonan)
                ->orderBy('created_at')
                ->get()
                ->groupBy('berkas_item_id')
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

        // Combobox: filter di server, batasi hasil; total dipakai untuk hint
        // "persempit pencarian" bila hasil melebihi batas.
        $permohonanQuery = Permohonan::with('pemohon')
            ->when(trim($this->permohonanSearch) !== '', function ($q) {
                $term = '%'.trim($this->permohonanSearch).'%';
                $q->where(fn ($w) => $w->where('nomor_registrasi', 'like', $term)
                    ->orWhereHas('pemohon', fn ($p) => $p->where('nama', 'like', $term)->orWhere('nik', 'like', $term)));
            })
            ->latest('created_at');

        // Panel "selesai periksa" hanya relevan saat permohonan sedang di
        // tahap pemeriksaan berkas (Staf/Korsub).
        $periksaStage = $permohonan && in_array($permohonan->status, self::PERIKSA_STAGES, true);
        $user = Auth::user();

        return view('livewire.pemeriksaan.manage-pemeriksaan-berkas', [
            'periksaStat' => $periksaStage ? $this->periksaStat($permohonan) : null,
            'canSelesai' => $periksaStage && $user
                && ($user->isAdmin() || collect($permohonan->status->allowedRoles())->contains(fn ($r) => $user->hasRole($r))),
            'permohonanList' => (clone $permohonanQuery)->limit(self::PERMOHONAN_LIMIT)->get(),
            'permohonanTotal' => $permohonanQuery->count(),
            'permohonanLimit' => self::PERMOHONAN_LIMIT,
            'permohonan' => $permohonan,
            'berkasList' => $berkasList,
            'hasBerkas' => $allBerkas->isNotEmpty(),
            // Opsi dropdown pemetaan di modal unggah — daftar penuh (tak terpengaruh pencarian).
            'uploadBerkasOptions' => $this->showUpload ? $allBerkas->values() : collect(),
            'pemeriksaan' => $pemeriksaan,
            'filesMap' => $filesMap,
            'catatanOptions' => $catatanOptions,
            'statuses' => PemeriksaanStatusEnum::cases(),
            'printParents' => $printParents,
            'printChildrenMap' => $printChildrenMap,
        ]);
    }
}
