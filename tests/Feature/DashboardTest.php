<?php

namespace Tests\Feature;

use App\Enums\PermohonanStatusEnum;
use App\Livewire\Dashboard;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Petugas', 'email' => 'p@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);
    }

    public function test_summary_counts_split_by_workflow_position(): void
    {
        Permohonan::create(['nomor_registrasi' => 'REG-1']); // DRAFT
        Permohonan::create(['nomor_registrasi' => 'REG-2', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);
        Permohonan::create(['nomor_registrasi' => 'REG-3', 'status' => PermohonanStatusEnum::LOKET_PENYERAHAN->value]);
        Permohonan::create(['nomor_registrasi' => 'REG-4', 'status' => PermohonanStatusEnum::DITOLAK->value]);

        Livewire::actingAs($this->user())
            ->test(Dashboard::class)
            ->assertViewHas('totalPermohonan', 4)
            ->assertViewHas('dalamProses', 1)
            ->assertViewHas('praDaftar', 1)
            ->assertViewHas('selesai', 1)
            ->assertViewHas('ditolak', 1);
    }

    public function test_perlu_perhatian_lists_active_files_oldest_stage_first(): void
    {
        $lama = Permohonan::create(['nomor_registrasi' => 'REG-LAMA', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);
        $baru = Permohonan::create(['nomor_registrasi' => 'REG-BARU', 'status' => PermohonanStatusEnum::TERDAFTAR->value]);
        $selesai = Permohonan::create(['nomor_registrasi' => 'REG-DONE', 'status' => PermohonanStatusEnum::LOKET_PENYERAHAN->value]);

        // REG-LAMA terakhir berubah status 20 hari lalu; REG-BARU baru kemarin.
        // (created_at bukan fillable — set lewat forceFill.)
        PermohonanAuditLog::create([
            'permohonan_id' => $lama->id,
            'status_baru' => PermohonanStatusEnum::TERDAFTAR,
        ])->forceFill(['created_at' => now()->subDays(20)])->save();
        PermohonanAuditLog::create([
            'permohonan_id' => $baru->id,
            'status_baru' => PermohonanStatusEnum::TERDAFTAR,
        ])->forceFill(['created_at' => now()->subDay()])->save();

        Livewire::actingAs($this->user())
            ->test(Dashboard::class)
            ->assertViewHas('perluPerhatian', function ($list) use ($lama, $selesai) {
                return $list->first()->id === $lama->id
                    && ! $list->contains('id', $selesai->id); // selesai/ditolak tak ikut
            });
    }

    public function test_recent_activity_and_monthly_trend_are_provided(): void
    {
        $p = Permohonan::create(['nomor_registrasi' => 'REG-9']);
        PermohonanAuditLog::create([
            'permohonan_id' => $p->id,
            'status_sebelumnya' => PermohonanStatusEnum::DRAFT,
            'status_baru' => PermohonanStatusEnum::PERIKSA_BERKAS_STAF,
        ]);

        Livewire::actingAs($this->user())
            ->test(Dashboard::class)
            ->assertViewHas('aktivitas', fn ($logs) => $logs->count() === 1)
            ->assertViewHas('bulanan', fn ($bulanan) => $bulanan->count() === 6 && $bulanan->last()['count'] === 1)
            ->assertSee('Perlu Perhatian')
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('6 Bulan Terakhir');
    }
}
