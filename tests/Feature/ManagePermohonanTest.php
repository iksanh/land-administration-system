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
            ->set('newStatus', 'SUBMITTED')
            ->set('statusCatatan', 'Diajukan pemohon')
            ->call('changeStatus')
            ->assertHasNoErrors();

        $this->assertSame(PermohonanStatusEnum::SUBMITTED, $p->refresh()->status);

        $log = PermohonanAuditLog::where('permohonan_id', $p->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(PermohonanStatusEnum::DRAFT, $log->status_sebelumnya);
        $this->assertSame(PermohonanStatusEnum::SUBMITTED, $log->status_baru);
        $this->assertSame($user->id, $log->petugas_id);
        $this->assertSame('Diajukan pemohon', $log->catatan);
    }

    public function test_draft_permohonan_can_be_deleted(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-3']);

        Livewire::test(ManagePermohonan::class)->call('delete', $p->id);

        $this->assertDatabaseMissing('permohonan', ['id' => $p->id]);
    }

    public function test_non_draft_permohonan_cannot_be_deleted(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-4', 'status' => PermohonanStatusEnum::SUBMITTED->value]);

        Livewire::test(ManagePermohonan::class)
            ->call('delete', $p->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permohonan', ['id' => $p->id]);
    }
}
