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
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $nomor_registrasi = '';
    public string $pemohon_id = '';
    public string $tanah_id = '';
    public string $layanan_id = '';
    public string $tgl_pendaftaran = '';

    // Status-change panel
    public ?string $statusEditingId = null;
    public string $newStatus = '';
    public string $statusCatatan = '';

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
        $this->newStatus = $p->status->value;
        $this->statusCatatan = '';
    }

    public function cancelStatusChange(): void
    {
        $this->reset(['statusEditingId', 'newStatus', 'statusCatatan']);
    }

    public function changeStatus(): void
    {
        $this->validate([
            'newStatus' => ['required', Rule::enum(PermohonanStatusEnum::class)],
            'statusCatatan' => ['nullable', 'string'],
        ]);

        $p = Permohonan::findOrFail($this->statusEditingId);
        $old = $p->status;
        $new = PermohonanStatusEnum::from($this->newStatus);

        if ($old === $new) {
            session()->flash('error', 'Status tidak berubah.');

            return;
        }

        DB::transaction(function () use ($p, $old, $new) {
            $p->update(['status' => $new]);

            PermohonanAuditLog::create([
                'permohonan_id' => $p->id,
                'status_sebelumnya' => $old,
                'status_baru' => $new,
                'petugas_id' => Auth::id(),
                'catatan' => $this->statusCatatan !== '' ? $this->statusCatatan : null,
            ]);
        });

        $this->cancelStatusChange();
        session()->flash('message', "Status diubah ke {$new->value}.");
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
                ->latest('created_at')->get(),
            'pemohonList' => Pemohon::orderBy('nama')->get(),
            'tanahList' => Tanah::with('pemohon')->latest('created_at')->get(),
            'layananList' => MstLayanan::orderBy('nama')->get(),
            'statuses' => PermohonanStatusEnum::cases(),
            // Tanah already tied to another permohonan — disabled in the picker.
            'usedTanahIds' => Permohonan::whereNotNull('tanah_id')
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->pluck('tanah_id')->all(),
        ]);
    }
}
