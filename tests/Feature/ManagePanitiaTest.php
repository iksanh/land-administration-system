<?php

namespace Tests\Feature;

use App\Enums\PeranPanitiaEnum;
use App\Livewire\Panitia\ManagePanitia;
use App\Models\PanitiaPemeriksa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagePanitiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_panitia_member(): void
    {
        Livewire::test(ManagePanitia::class)
            ->set('nama', 'Yudhi Satria Pulo, S.H., M.H.')
            ->set('jabatan', 'Kepala Seksi Penetapan Hak')
            ->set('peran', 'KETUA')
            ->set('urutan', 1)
            ->call('save')
            ->assertHasNoErrors();

        $p = PanitiaPemeriksa::first();
        $this->assertNotNull($p);
        $this->assertSame(PeranPanitiaEnum::KETUA, $p->peran);
        $this->assertTrue($p->is_active);
    }

    public function test_requires_nama(): void
    {
        Livewire::test(ManagePanitia::class)
            ->set('nama', '')
            ->call('save')
            ->assertHasErrors(['nama']);
    }

    public function test_can_edit_member(): void
    {
        $p = PanitiaPemeriksa::create(['nama' => 'Lama', 'peran' => 'ANGGOTA']);

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
        $p = PanitiaPemeriksa::create(['nama' => 'Hapus', 'peran' => 'ANGGOTA']);

        Livewire::test(ManagePanitia::class)->call('delete', $p->id);

        $this->assertDatabaseMissing('panitia_pemeriksa', ['id' => $p->id]);
    }
}
