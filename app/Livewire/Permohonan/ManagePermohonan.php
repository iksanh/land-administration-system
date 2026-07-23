<?php

namespace App\Livewire\Permohonan;

use App\Enums\PermohonanStatusEnum;
use App\Models\MstLayanan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Models\Tanah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/permohonan.py — the workflow centerpiece.
 *  - unique nomor_registrasi (the API returned 400 on duplicate);
 *  - list decorates pemohon/layanan/tanah info (FastAPI's outer joins);
 *  - status changes go through changeStatus(), which also writes a
 *    permohonan_audit_log row (status_sebelumnya/baru + petugas + catatan);
 *  - delete is only allowed while status is DRAFT.
 */
#[Layout('components.layouts.app')]
class ManagePermohonan extends Component
{
    public string $search = '';

    /**
     * Filter checklist kolom status: kosong = tampilkan semua (default),
     * berisi nilai enum = hanya status tercentang yang tampil.
     */
    public array $statusFilter = [];

    public bool $showForm = false;
    public ?string $editingId = null;

    public string $nomor_registrasi = '';
    public string $pemohon_id = '';
    public string $tanah_id = '';
    public string $layanan_id = '';
    public string $tgl_pendaftaran = '';

    // Status-change modal (stepper: maju/mundur satu tahap, tolak, buka kembali)
    public ?string $statusEditingId = null;
    public string $statusCatatan = '';

    // Identitas berkas KKP — wajib diisi saat maju PROSES_DAFTAR → TERDAFTAR.
    public string $nomor_berkas = '';
    public string $tahun_berkas = '';
    public string $tanggal_daftar_kkp = '';

    protected function rules(): array
    {
        return [
            'nomor_registrasi' => ['required', 'string', 'max:50', Rule::unique('permohonan', 'nomor_registrasi')->ignore($this->editingId)],
            'pemohon_id' => ['nullable', 'exists:pemohon,id'],
            // A tanah may only be tied to one permohonan: once registered it
            // cannot be re-registered on another permohonan.
            'tanah_id' => ['nullable', 'exists:tanah,id', Rule::unique('permohonan', 'tanah_id')->ignore($this->editingId)],
            'layanan_id' => ['nullable', 'exists:mst_layanan,id'],
            'tgl_pendaftaran' => ['nullable', 'date'],
        ];
    }

    protected function messages(): array
    {
        return [
            'tanah_id.unique' => 'Tanah ini sudah terdaftar pada permohonan lain dan tidak dapat didaftarkan ulang.',
        ];
    }

    public function save(): void
    {
        $this->nomor_registrasi = trim($this->nomor_registrasi);
        $data = $this->validate();

        foreach (['pemohon_id', 'tanah_id', 'layanan_id', 'tgl_pendaftaran'] as $field) {
            $data[$field] = $data[$field] !== '' ? $data[$field] : null;
        }

        if ($this->editingId) {
            // Status is changed only via changeStatus(), never the main form.
            Permohonan::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Permohonan berhasil diperbarui.');
        } else {
            Permohonan::create($data);
            session()->flash('message', 'Permohonan berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $p = Permohonan::findOrFail($id);
        $this->editingId = $p->id;
        $this->nomor_registrasi = $p->nomor_registrasi;
        $this->pemohon_id = $p->pemohon_id ?? '';
        $this->tanah_id = $p->tanah_id ?? '';
        $this->layanan_id = $p->layanan_id ?? '';
        $this->tgl_pendaftaran = $p->tgl_pendaftaran?->format('Y-m-d') ?? '';
        $this->showForm = true;
    }

    public function delete(string $id): void
    {
        $p = Permohonan::findOrFail($id);

        if ($p->status !== PermohonanStatusEnum::DRAFT) {
            session()->flash('error', 'Hanya permohonan berstatus DRAFT yang bisa dihapus.');

            return;
        }

        $p->delete();
        session()->flash('message', 'Permohonan berhasil dihapus.');
    }

    public function startStatusChange(string $id): void
    {
        $p = Permohonan::findOrFail($id);
        $this->statusEditingId = $p->id;
        $this->statusCatatan = '';

        // Prefill data KKP: nilai tersimpan, atau tahun/tanggal hari ini agar
        // petugas tinggal mengisi nomor berkas.
        $this->nomor_berkas = $p->nomor_berkas ?? '';
        $this->tahun_berkas = (string) ($p->tahun_berkas ?? now()->year);
        $this->tanggal_daftar_kkp = $p->tanggal_daftar_kkp?->format('Y-m-d') ?? now()->format('Y-m-d');

        $this->resetErrorBag();
    }

    public function cancelStatusChange(): void
    {
        $this->reset(['statusEditingId', 'statusCatatan', 'nomor_berkas', 'tahun_berkas', 'tanggal_daftar_kkp']);
        $this->resetErrorBag();
    }

    /** Maju ke tahap berikutnya dalam alur. */
    public function advanceStatus(): void
    {
        $p = Permohonan::findOrFail($this->statusEditingId);
        $next = $p->status->next();

        if (! $next) {
            $this->addError('statusCatatan', 'Permohonan sudah berada di tahap akhir.');

            return;
        }

        // Gerbang setelah TERDAFTAR: identitas berkas KKP wajib lengkap
        // sebelum lanjut ke Konsep RPD & BA & SK.
        $extra = [];
        if ($next === PermohonanStatusEnum::KONSEP_RPD_BA_SK_STAF) {
            $data = $this->validate([
                'nomor_berkas' => ['required', 'string', 'max:50'],
                'tahun_berkas' => ['required', 'digits:4', 'integer', 'between:2000,2100'],
                'tanggal_daftar_kkp' => ['required', 'date'],
            ], [
                'nomor_berkas.required' => 'Nomor berkas wajib diisi sebelum lanjut ke Konsep RPD & BA & SK.',
                'tahun_berkas.required' => 'Tahun berkas wajib diisi sebelum lanjut ke Konsep RPD & BA & SK.',
                'tahun_berkas.digits' => 'Tahun berkas harus 4 digit (misal '.now()->year.').',
                'tahun_berkas.between' => 'Tahun berkas harus antara 2000 dan 2100.',
                'tanggal_daftar_kkp.required' => 'Tanggal daftar KKP wajib diisi sebelum lanjut ke Konsep RPD & BA & SK.',
                'tanggal_daftar_kkp.date' => 'Tanggal daftar KKP tidak valid.',
            ]);

            $extra = [
                'nomor_berkas' => trim($data['nomor_berkas']),
                'tahun_berkas' => (int) $data['tahun_berkas'],
                'tanggal_daftar_kkp' => $data['tanggal_daftar_kkp'],
            ];
        }

        $this->applyStatus($p, $next, $extra);
        session()->flash('message', "Status maju ke {$next->label()}.");
    }

    /** Mundur satu tahap untuk koreksi — wajib catatan alasan. */
    public function regressStatus(): void
    {
        $p = Permohonan::findOrFail($this->statusEditingId);
        $prev = $p->status->prev();

        if (! $prev) {
            $this->addError('statusCatatan', 'Status sudah di tahap awal dan tidak bisa dimundurkan.');

            return;
        }

        if (! $this->requireCatatan('Catatan alasan wajib diisi saat memundurkan status.')) {
            return;
        }

        $this->applyStatus($p, $prev);
        session()->flash('message', "Status dikembalikan ke {$prev->label()}.");
    }

    /** Tolak permohonan dari tahap mana pun — wajib catatan alasan. */
    public function rejectStatus(): void
    {
        $p = Permohonan::findOrFail($this->statusEditingId);

        if ($p->status === PermohonanStatusEnum::DITOLAK) {
            return;
        }

        if (! $this->requireCatatan('Catatan alasan penolakan wajib diisi.')) {
            return;
        }

        $this->applyStatus($p, PermohonanStatusEnum::DITOLAK);
        session()->flash('message', 'Permohonan ditolak.');
    }

    /** Buka kembali permohonan DITOLAK ke tahap sebelum penolakan — wajib catatan. */
    public function reopenStatus(): void
    {
        $p = Permohonan::findOrFail($this->statusEditingId);

        if ($p->status !== PermohonanStatusEnum::DITOLAK) {
            return;
        }

        if (! $this->requireCatatan('Catatan wajib diisi saat membuka kembali permohonan.')) {
            return;
        }

        $restore = PermohonanAuditLog::where('permohonan_id', $p->id)
            ->where('status_baru', PermohonanStatusEnum::DITOLAK->value)
            ->latest('id')->first()
            ?->status_sebelumnya ?? PermohonanStatusEnum::DRAFT;

        $this->applyStatus($p, $restore);
        session()->flash('message', "Permohonan dibuka kembali ke {$restore->label()}.");
    }

    private function requireCatatan(string $message): bool
    {
        if (trim($this->statusCatatan) === '') {
            $this->addError('statusCatatan', $message);

            return false;
        }

        return true;
    }

    private function applyStatus(Permohonan $p, PermohonanStatusEnum $new, array $extra = []): void
    {
        $old = $p->status;

        DB::transaction(function () use ($p, $old, $new, $extra) {
            $p->update(['status' => $new] + $extra);

            PermohonanAuditLog::create([
                'permohonan_id' => $p->id,
                'status_sebelumnya' => $old,
                'status_baru' => $new,
                'petugas_id' => Auth::id(),
                'catatan' => trim($this->statusCatatan) !== '' ? trim($this->statusCatatan) : null,
            ]);
        });

        // Modal tetap terbuka agar petugas melihat posisi tahap terbaru
        // dan bisa lanjut beberapa tahap tanpa membuka ulang.
        $this->statusCatatan = '';
        $this->resetErrorBag('statusCatatan');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'showForm', 'nomor_registrasi',
            'pemohon_id', 'tanah_id', 'layanan_id', 'tgl_pendaftaran',
        ]);
    }

    public function render()
    {
        return view('livewire.permohonan.manage-permohonan', [
            'permohonanList' => Permohonan::with(['pemohon', 'layanan', 'tanah.desa'])
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nomor_registrasi', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhereHas('pemohon', fn ($p) => $p->where('nama', 'like', $term)->orWhere('nik', 'like', $term))
                        ->orWhereHas('layanan', fn ($l) => $l->where('nama', 'like', $term)));
                })
                ->when($this->statusFilter !== [], fn ($q) => $q->whereIn('status', $this->statusFilter))
                ->latest('created_at')->get(),
            'statuses' => PermohonanStatusEnum::cases(),
            'pemohonList' => Pemohon::orderBy('nama')->get(),
            'tanahList' => Tanah::with('pemohon')->latest('created_at')->get(),
            'layananList' => MstLayanan::orderBy('nama')->get(),
            'statusPermohonan' => $this->statusEditingId
                ? Permohonan::with('pemohon')->find($this->statusEditingId)
                : null,
            // Tanah already tied to another permohonan — disabled in the picker.
            'usedTanahIds' => Permohonan::whereNotNull('tanah_id')
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->pluck('tanah_id')->all(),
        ]);
    }
}
