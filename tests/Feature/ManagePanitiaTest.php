<?php

namespace Tests\Feature;

use App\Enums\PeranPanitiaEnum;
use App\Livewire\Panitia\ManagePanitia;
use App\Models\PanitiaPemeriksa;
use App\Models\SkPanitia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagePanitiaTest extends TestCase
{
    use RefreshDatabase;

    private function sk(array $attr = []): SkPanitia
    {
        return SkPanitia::create($attr + ['nomor' => 'SK-1/2025', 'is_active' => true]);
    }

    public function test_can_create_sk_and_first_one_is_active(): void
    {
        Livewire::test(ManagePanitia::class)
            ->call('newSk')
            ->set('sk_nomor', '134/SK-75.03/V/2025')
            ->set('sk_tanggal', '2025-05-12')
            ->call('saveSk')
            ->assertHasNoErrors();

        $sk = SkPanitia::first();
        $this->assertNotNull($sk);
        $this->assertTrue($sk->is_active);
    }

    public function test_sk_requires_nomor(): void
    {
        Livewire::test(ManagePanitia::class)
            ->call('newSk')
            ->set('sk_nomor', '')
            ->call('saveSk')
            ->assertHasErrors(['sk_nomor']);
    }

    public function test_can_create_panitia_member_under_selected_sk(): void
    {
        $sk = $this->sk();

        Livewire::test(ManagePanitia::class)
            ->assertSet('skId', $sk->id) // SK aktif terpilih otomatis
            ->set('nama', 'Yudhi Satria Pulo, S.H., M.H.')
            ->set('jabatan', 'Kepala Seksi Penetapan Hak')
            ->set('peran', 'KETUA')
            ->set('urutan', 1)
            ->call('save')
            ->assertHasNoErrors();

        $p = PanitiaPemeriksa::first();
        $this->assertNotNull($p);
        $this->assertSame($sk->id, $p->sk_panitia_id);
        $this->assertSame(PeranPanitiaEnum::KETUA, $p->peran);
        $this->assertTrue($p->is_active);
    }

    public function test_member_requires_an_sk(): void
    {
        Livewire::test(ManagePanitia::class)
            ->set('nama', 'Tanpa SK')
            ->call('save')
            ->assertHasErrors(['skId']);

        $this->assertSame(0, PanitiaPemeriksa::count());
    }

    public function test_requires_nama(): void
    {
        $this->sk();

        Livewire::test(ManagePanitia::class)
            ->set('nama', '')
            ->call('save')
            ->assertHasErrors(['nama']);
    }

    public function test_can_edit_member(): void
    {
        $p = $this->sk()->anggota()->create(['nama' => 'Lama', 'peran' => 'ANGGOTA']);

        Livewire::test(ManagePanitia::class)
            ->call('edit', $p->id)
            ->assertSet('nama', 'Lama')
            ->set('nama', 'Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Baru', $p->refresh()->nama);
    }

    public function test_can_delete_member(): void
    {
        $p = $this->sk()->anggota()->create(['nama' => 'Hapus', 'peran' => 'ANGGOTA']);

        Livewire::test(ManagePanitia::class)->call('delete', $p->id);

        $this->assertDatabaseMissing('panitia_pemeriksa', ['id' => $p->id]);
    }

    public function test_activating_an_sk_deactivates_the_others(): void
    {
        $lama = $this->sk();
        $baru = SkPanitia::create(['nomor' => 'SK-2/2026', 'is_active' => false]);

        Livewire::test(ManagePanitia::class)
            ->call('activateSk', $baru->id)
            ->assertSet('skId', $baru->id);

        $this->assertFalse($lama->refresh()->is_active);
        $this->assertTrue($baru->refresh()->is_active);
    }

    public function test_duplicate_sk_copies_members_but_stays_inactive(): void
    {
        $sk = $this->sk();
        $sk->anggota()->create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1]);
        $sk->anggota()->create(['nama' => 'Anggota', 'peran' => 'ANGGOTA', 'urutan' => 2]);

        Livewire::test(ManagePanitia::class)->call('duplicateSk', $sk->id);

        $salinan = SkPanitia::where('nomor', $sk->nomor.' (salinan)')->first();
        $this->assertNotNull($salinan);
        $this->assertFalse($salinan->is_active);
        $this->assertCount(2, $salinan->anggota);
        // SK sumber tetap yang aktif sampai salinan diaktifkan manual.
        $this->assertTrue($sk->refresh()->is_active);
    }
}
