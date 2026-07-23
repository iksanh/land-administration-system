<?php

namespace Tests\Feature;

use App\Enums\PermohonanStatusEnum;
use App\Livewire\Permohonan\ManagePermohonan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManagePermohonanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_permohonan_defaulting_to_draft(): void
    {
        Livewire::test(ManagePermohonan::class)
            ->set('nomor_registrasi', 'REG-2026-001')
            ->call('save')
            ->assertHasNoErrors();

        $p = Permohonan::where('nomor_registrasi', 'REG-2026-001')->first();
        $this->assertNotNull($p);
        $this->assertSame(PermohonanStatusEnum::DRAFT, $p->status);
    }

    public function test_nomor_registrasi_must_be_unique(): void
    {
        Permohonan::create(['nomor_registrasi' => 'REG-1']);

        Livewire::test(ManagePermohonan::class)
            ->set('nomor_registrasi', 'REG-1')
            ->call('save')
            ->assertHasErrors(['nomor_registrasi' => 'unique']);
    }

    public function test_blank_nomor_registrasi_is_rejected(): void
    {
        Livewire::test(ManagePermohonan::class)
            ->set('nomor_registrasi', '   ')
            ->call('save')
            ->assertHasErrors(['nomor_registrasi']);
    }

    public function test_tanah_already_on_a_permohonan_cannot_be_reused(): void
    {
        $pemohon = Pemohon::create(['nik' => '7503010101010002', 'nama' => 'Budi']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 100]);
        Permohonan::create(['nomor_registrasi' => 'REG-A', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id]);

        Livewire::test(ManagePermohonan::class)
            ->set('nomor_registrasi', 'REG-B')
            ->set('tanah_id', $tanah->id)
            ->call('save')
            ->assertHasErrors(['tanah_id' => 'unique']);

        $this->assertDatabaseMissing('permohonan', ['nomor_registrasi' => 'REG-B']);
    }

    public function test_editing_keeps_its_own_tanah_without_tripping_the_unique_rule(): void
    {
        $tanah = Tanah::create(['luas' => 100]);
        $p = Permohonan::create(['nomor_registrasi' => 'REG-C', 'tanah_id' => $tanah->id]);

        Livewire::test(ManagePermohonan::class)
            ->call('edit', $p->id)
            ->set('nomor_registrasi', 'REG-C-EDIT')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('REG-C-EDIT', $p->refresh()->nomor_registrasi);
        $this->assertSame($tanah->id, $p->tanah_id);
    }

    public function test_changing_status_writes_an_audit_log(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'p@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);
        $p = Permohonan::create(['nomor_registrasi' => 'REG-2']);

        Livewire::actingAs($user)
            ->test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('statusCatatan', 'Diajukan pemohon')
            ->call('advanceStatus')
            ->assertHasNoErrors();

        $this->assertSame(PermohonanStatusEnum::PERIKSA_BERKAS_STAF, $p->refresh()->status);

        $log = PermohonanAuditLog::where('permohonan_id', $p->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(PermohonanStatusEnum::DRAFT, $log->status_sebelumnya);
        $this->assertSame(PermohonanStatusEnum::PERIKSA_BERKAS_STAF, $log->status_baru);
        $this->assertSame($user->id, $log->petugas_id);
        $this->assertSame('Diajukan pemohon', $log->catatan);
    }

    public function test_status_filter_limits_list_and_empty_means_all(): void
    {
        $draft = Permohonan::create(['nomor_registrasi' => 'REG-30']);
        $terdaftar = Permohonan::create(['nomor_registrasi' => 'REG-31', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);
        $ditolak = Permohonan::create(['nomor_registrasi' => 'REG-32', 'status' => PermohonanStatusEnum::DITOLAK->value]);

        Livewire::test(ManagePermohonan::class)
            // Default (kosong) = semua tampil.
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 3)
            // Satu status dicentang.
            ->set('statusFilter', [PermohonanStatusEnum::TERDAFTAR->value])
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 1 && $list->first()->id === $terdaftar->id)
            // Dua status dicentang.
            ->set('statusFilter', [PermohonanStatusEnum::DRAFT->value, PermohonanStatusEnum::DITOLAK->value])
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 2)
            // Reset = semua kembali tampil.
            ->set('statusFilter', [])
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 3);
    }

    public function test_advance_past_terdaftar_requires_kkp_fields(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-20', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);

        // Tanpa data KKP → tertahan di Terdaftar.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('nomor_berkas', '')
            ->set('tahun_berkas', '')
            ->set('tanggal_daftar_kkp', '')
            ->call('advanceStatus')
            ->assertHasErrors(['nomor_berkas', 'tahun_berkas', 'tanggal_daftar_kkp']);
        $this->assertSame(PermohonanStatusEnum::TERDAFTAR, $p->refresh()->status);

        // Lengkap → maju ke Konsep RPD & BA & SK dan data tersimpan.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('nomor_berkas', '12345')
            ->set('tahun_berkas', '2026')
            ->set('tanggal_daftar_kkp', '2026-07-23')
            ->call('advanceStatus')
            ->assertHasNoErrors();

        $p->refresh();
        $this->assertSame(PermohonanStatusEnum::KONSEP_RPD_BA_SK_STAF, $p->status);
        $this->assertSame('12345', $p->nomor_berkas);
        $this->assertSame(2026, $p->tahun_berkas);
        $this->assertSame('2026-07-23', $p->tanggal_daftar_kkp->format('Y-m-d'));
    }

    public function test_advance_on_other_steps_does_not_require_kkp_fields(): void
    {
        // Termasuk PROSES_DAFTAR → TERDAFTAR: data KKP belum diwajibkan di sini.
        $p = Permohonan::create(['nomor_registrasi' => 'REG-21', 'status' => PermohonanStatusEnum::PROSES_DAFTAR->value]);

        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('nomor_berkas', '')
            ->set('tahun_berkas', '')
            ->set('tanggal_daftar_kkp', '')
            ->call('advanceStatus')
            ->assertHasNoErrors();

        $this->assertSame(PermohonanStatusEnum::TERDAFTAR, $p->refresh()->status);
    }

    public function test_regress_moves_one_step_back_and_requires_note(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-10', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);

        // Tanpa catatan → ditolak validasi, status tidak berubah.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->call('regressStatus')
            ->assertHasErrors('statusCatatan');
        $this->assertSame(PermohonanStatusEnum::TERDAFTAR, $p->refresh()->status);

        // Dengan catatan → mundur tepat satu tahap.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('statusCatatan', 'Berkas kurang lengkap')
            ->call('regressStatus')
            ->assertHasNoErrors();
        $this->assertSame(PermohonanStatusEnum::PROSES_DAFTAR, $p->refresh()->status);
    }

    public function test_regress_is_blocked_at_first_step(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-11']);

        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('statusCatatan', 'Coba mundur')
            ->call('regressStatus')
            ->assertHasErrors('statusCatatan');

        $this->assertSame(PermohonanStatusEnum::DRAFT, $p->refresh()->status);
    }

    public function test_reject_requires_note_and_reopen_restores_previous_status(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-12', 'status' => PermohonanStatusEnum::TURUN_PANITIA->value]);

        // Tolak tanpa catatan → gagal.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->call('rejectStatus')
            ->assertHasErrors('statusCatatan');
        $this->assertSame(PermohonanStatusEnum::TURUN_PANITIA, $p->refresh()->status);

        // Tolak dengan catatan → DITOLAK + audit log.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('statusCatatan', 'Dokumen tidak sah')
            ->call('rejectStatus')
            ->assertHasNoErrors();
        $this->assertSame(PermohonanStatusEnum::DITOLAK, $p->refresh()->status);

        // Buka kembali → balik ke status sebelum penolakan.
        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->set('statusCatatan', 'Dokumen sudah dilengkapi')
            ->call('reopenStatus')
            ->assertHasNoErrors();
        $this->assertSame(PermohonanStatusEnum::TURUN_PANITIA, $p->refresh()->status);
    }

    public function test_advance_is_blocked_at_final_step(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-13', 'status' => PermohonanStatusEnum::LOKET_PENYERAHAN->value]);

        Livewire::test(ManagePermohonan::class)
            ->call('startStatusChange', $p->id)
            ->call('advanceStatus')
            ->assertHasErrors('statusCatatan');

        $this->assertSame(PermohonanStatusEnum::LOKET_PENYERAHAN, $p->refresh()->status);
    }

    public function test_draft_permohonan_can_be_deleted(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-3']);

        Livewire::test(ManagePermohonan::class)->call('delete', $p->id);

        $this->assertDatabaseMissing('permohonan', ['id' => $p->id]);
    }

    public function test_non_draft_permohonan_cannot_be_deleted(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-4', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);

        Livewire::test(ManagePermohonan::class)
            ->call('delete', $p->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permohonan', ['id' => $p->id]);
    }
}
