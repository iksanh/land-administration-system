<?php

namespace App\Livewire\Pemohon;

use App\Enums\GenderEnum;
use App\Enums\JenisPemohonEnum;
use App\Livewire\Concerns\WithWilayahPicker;
use App\Models\Pemohon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/pemohon.py. NIK is unique (the API returned 400 on
 * duplicate — here a validation error). jenis_kelamin is the L/P GenderEnum.
 */
#[Layout('components.layouts.app')]
class ManagePemohon extends Component
{
    use WithWilayahPicker;

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $nik = '';

    public string $nama = '';

    public string $jenis_pemohon = JenisPemohonEnum::DIRI_SENDIRI->value;

    public string $tempat_lahir = '';

    public string $tanggal_lahir = '';

    public string $jenis_kelamin = '';

    public string $pekerjaan = '';

    public string $alamat_detail = '';

    public string $desa_id = '';

    // Data penerima kuasa — only used when jenis_pemohon = dikuasakan.
    public string $kuasa_nama = '';

    public string $kuasa_nik = '';

    public string $kuasa_pekerjaan = '';

    public string $kuasa_no_hp = '';

    public string $kuasa_alamat = '';

    public string $kuasa_hubungan = '';

    public string $kuasa_no_surat = '';

    public string $kuasa_tanggal_surat = '';

    /** True when the applicant delegates the permit to an authorised proxy. */
    protected function isDikuasakan(): bool
    {
        return $this->jenis_pemohon === JenisPemohonEnum::DIKUASAKAN->value;
    }

    protected function rules(): array
    {
        $requiredWhenKuasa = $this->isDikuasakan() ? 'required' : 'nullable';

        return [
            'nik' => ['required', 'string', 'max:16', Rule::unique('pemohon', 'nik')->ignore($this->editingId)],
            'nama' => ['required', 'string', 'max:200'],
            'jenis_pemohon' => ['required', Rule::enum(JenisPemohonEnum::class)],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', Rule::enum(GenderEnum::class)],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'alamat_detail' => ['nullable', 'string'],
            'desa_id' => ['nullable', 'exists:ref_desa,id'],
            // Penerima kuasa: required only when dikuasakan, otherwise ignored.
            'kuasa_nama' => [$requiredWhenKuasa, 'string', 'max:200'],
            'kuasa_nik' => [$requiredWhenKuasa, 'string', 'max:16'],
            'kuasa_pekerjaan' => ['nullable', 'string', 'max:100'],
            'kuasa_no_hp' => ['nullable', 'string', 'max:20'],
            'kuasa_alamat' => ['nullable', 'string'],
            'kuasa_hubungan' => ['nullable', 'string', 'max:100'],
            'kuasa_no_surat' => ['nullable', 'string', 'max:100'],
            'kuasa_tanggal_surat' => ['nullable', 'date'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'kuasa_nama' => 'nama yang dikuasakan',
            'kuasa_nik' => 'NIK yang dikuasakan',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // When filed atas nama diri sendiri, drop any kuasa data that may linger
        // from a previous selection so the record stays clean.
        if (! $this->isDikuasakan()) {
            foreach (['kuasa_nama', 'kuasa_nik', 'kuasa_pekerjaan', 'kuasa_no_hp', 'kuasa_alamat', 'kuasa_hubungan', 'kuasa_no_surat', 'kuasa_tanggal_surat'] as $field) {
                $data[$field] = '';
            }
        }

        // Optional fields: empty string -> null (esp. the enum & FK columns).
        foreach (['tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan', 'alamat_detail', 'desa_id',
            'kuasa_nama', 'kuasa_nik', 'kuasa_pekerjaan', 'kuasa_no_hp', 'kuasa_alamat', 'kuasa_hubungan', 'kuasa_no_surat', 'kuasa_tanggal_surat'] as $field) {
            $data[$field] = $data[$field] !== '' ? $data[$field] : null;
        }

        if ($this->editingId) {
            Pemohon::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Pemohon berhasil diperbarui.');
        } else {
            Pemohon::create($data);
            session()->flash('message', 'Pemohon berhasil dibuat.');
        }

        $this->resetForm();
    }

    public function edit(string $id): void
    {
        $p = Pemohon::findOrFail($id);
        $this->editingId = $p->id;
        $this->nik = $p->nik;
        $this->nama = $p->nama;
        $this->jenis_pemohon = $p->jenis_pemohon?->value ?? JenisPemohonEnum::DIRI_SENDIRI->value;
        $this->tempat_lahir = $p->tempat_lahir ?? '';
        $this->tanggal_lahir = $p->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->jenis_kelamin = $p->jenis_kelamin?->value ?? '';
        $this->pekerjaan = $p->pekerjaan ?? '';
        $this->alamat_detail = $p->alamat_detail ?? '';
        $this->desa_id = $p->desa_id ?? '';
        $this->kuasa_nama = $p->kuasa_nama ?? '';
        $this->kuasa_nik = $p->kuasa_nik ?? '';
        $this->kuasa_pekerjaan = $p->kuasa_pekerjaan ?? '';
        $this->kuasa_no_hp = $p->kuasa_no_hp ?? '';
        $this->kuasa_alamat = $p->kuasa_alamat ?? '';
        $this->kuasa_hubungan = $p->kuasa_hubungan ?? '';
        $this->kuasa_no_surat = $p->kuasa_no_surat ?? '';
        $this->kuasa_tanggal_surat = $p->kuasa_tanggal_surat?->format('Y-m-d') ?? '';
        $this->syncWilayahFromDesa();
        $this->showForm = true;
    }

    public function delete(string $id): void
    {
        Pemohon::findOrFail($id)->delete();
        session()->flash('message', 'Pemohon berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'showForm', 'nik', 'nama', 'jenis_pemohon', 'tempat_lahir', 'tanggal_lahir',
            'jenis_kelamin', 'pekerjaan', 'alamat_detail', 'desa_id',
            'kuasa_nama', 'kuasa_nik', 'kuasa_pekerjaan', 'kuasa_no_hp', 'kuasa_alamat',
            'kuasa_hubungan', 'kuasa_no_surat', 'kuasa_tanggal_surat',
        ]);
        $this->resetWilayah();
    }

    public function render()
    {
        return view('livewire.pemohon.manage-pemohon', array_merge($this->wilayahLists(), [
            'pemohonList' => Pemohon::with('desa')
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('nama', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('pekerjaan', 'like', $term));
                })
                ->orderBy('nama')->get(),
        ]));
    }
}
