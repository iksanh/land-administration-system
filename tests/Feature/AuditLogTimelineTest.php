<?php

namespace Tests\Feature;

use App\Enums\PermohonanStatusEnum;
use App\Livewire\AuditLog\AuditLogTimeline;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_only_logs_for_selected_permohonan_newest_first(): void
    {
        $a = Permohonan::create(['nomor_registrasi' => 'REG-A']);
        $b = Permohonan::create(['nomor_registrasi' => 'REG-B']);

        $first = PermohonanAuditLog::create([
            'permohonan_id' => $a->id,
            'status_sebelumnya' => PermohonanStatusEnum::DRAFT,
            'status_baru' => PermohonanStatusEnum::PERIKSA_BERKAS_STAF,
        ]);
        $second = PermohonanAuditLog::create([
            'permohonan_id' => $a->id,
            'status_sebelumnya' => PermohonanStatusEnum::PERIKSA_BERKAS_STAF,
            'status_baru' => PermohonanStatusEnum::PERIKSA_BERKAS_KORSUB,
        ]);
        PermohonanAuditLog::create([
            'permohonan_id' => $b->id,
            'status_sebelumnya' => PermohonanStatusEnum::DRAFT,
            'status_baru' => PermohonanStatusEnum::DITOLAK,
        ]);

        Livewire::test(AuditLogTimeline::class)
            ->set('selectedPermohonan', $a->id)
            ->assertViewHas('logs', fn ($logs) => $logs->count() === 2 && $logs->first()->id === $second->id)
            ->assertDontSee('Ditolak');
    }

    public function test_no_permohonan_selected_shows_empty(): void
    {
        Livewire::test(AuditLogTimeline::class)
            ->assertViewHas('logs', fn ($logs) => $logs->isEmpty())
            ->assertSee('Pilih permohonan');
    }
}
