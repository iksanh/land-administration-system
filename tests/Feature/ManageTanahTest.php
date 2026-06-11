<?php

namespace Tests\Feature;

use App\Livewire\Tanah\ManageTanah;
use App\Models\Pemohon;
use App\Models\Tanah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageTanahTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tanah_linked_to_pemohon(): void
    {
        $pemohon = Pemohon::create(['nik' => '7503010101010001', 'nama' => 'Budi']);

        Livewire::test(ManageTanah::class)
            ->set('pemohon_id', $pemohon->id)
            ->set('luas', '123.45')
            ->set('luas_surat', '120')
            ->set('penggunaan_tanah', 'Pertanian')
            ->set('nib', '12.34.05.06.00789')
            ->set('nomor_pbt', 'PBT-2026-001')
            ->set('tanggal_pbt', '2026-05-01')
            ->set('tgl_peta_analisis', '2026-05-10')
            ->set('rencana_penggunaan_rtrw', 'Kawasan Permukiman')
            ->set('kesesuaian_penggunaan_tanah', 'Sesuai')
            ->set('penggunaan_tanah_sk', 'Permukiman')
            ->call('save')
            ->assertHasNoErrors();

        $tanah = Tanah::first();
        $this->assertSame($pemohon->id, $tanah->pemohon_id);
        $this->assertSame('123.45', $tanah->luas);
        $this->assertSame('12.34.05.06.00789', $tanah->nib);
        $this->assertSame('PBT-2026-001', $tanah->nomor_pbt);
        $this->assertSame('2026-05-01', $tanah->tanggal_pbt->format('Y-m-d'));
        $this->assertSame('2026-05-10', $tanah->tgl_peta_analisis->format('Y-m-d'));
        $this->assertSame('Kawasan Permukiman', $tanah->rencana_penggunaan_rtrw);
        $this->assertSame('Sesuai', $tanah->kesesuaian_penggunaan_tanah);
        $this->assertSame('Permukiman', $tanah->penggunaan_tanah_sk);
    }

    public function test_luas_must_be_greater_than_zero(): void
    {
        Livewire::test(ManageTanah::class)
            ->set('luas', '0')
            ->set('luas_surat', '-5')
            ->call('save')
            ->assertHasErrors(['luas', 'luas_surat']);
    }

    public function test_unknown_pemohon_is_rejected(): void
    {
        Livewire::test(ManageTanah::class)
            ->set('pemohon_id', '00000000-0000-0000-0000-000000000000')
            ->set('luas', '10')
            ->call('save')
            ->assertHasErrors(['pemohon_id']);
    }

    public function test_can_edit_and_delete_tanah(): void
    {
        $tanah = Tanah::create(['luas' => 50, 'penggunaan_tanah' => 'Lama']);

        Livewire::test(ManageTanah::class)
            ->call('edit', $tanah->id)
            ->set('penggunaan_tanah', 'Pemukiman')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Pemukiman', $tanah->refresh()->penggunaan_tanah);

        Livewire::test(ManageTanah::class)->call('delete', $tanah->id);
        $this->assertDatabaseMissing('tanah', ['id' => $tanah->id]);
    }
}
