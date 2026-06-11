<?php

namespace Tests\Feature;

use App\Livewire\Pemohon\ManagePemohon;
use App\Livewire\Tanah\ManageTanah;
use App\Models\Pemohon;
use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefProvinsi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WilayahPickerTest extends TestCase
{
    use RefreshDatabase;

    private function wilayah(): array
    {
        $prov = RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);
        $other = RefProvinsi::create(['id' => '11', 'nama' => 'ACEH']);
        $kab = RefKabupaten::create(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'BONE BOLANGO']);
        RefKabupaten::create(['id' => '1101', 'provinsi_id' => '11', 'nama' => 'ACEH SELATAN']);
        $kec = RefKecamatan::create(['id' => '750301', 'kabupaten_id' => '7503', 'nama' => 'KABILA']);
        $desa = RefDesa::create(['id' => '7503012001', 'kecamatan_id' => '750301', 'nama' => 'OLUHUTA']);

        return compact('prov', 'kab', 'kec', 'desa');
    }

    public function test_cascade_filters_each_level_and_creates_pemohon(): void
    {
        ['desa' => $desa] = $this->wilayah();

        Livewire::test(ManagePemohon::class)
            ->set('wProvinsi', '75')
            ->assertViewHas('kabupatenList', fn ($l) => $l->count() === 1 && $l->first()->id === '7503')
            ->set('wKabupaten', '7503')
            ->assertViewHas('kecamatanList', fn ($l) => $l->count() === 1 && $l->first()->id === '750301')
            ->set('wKecamatan', '750301')
            ->assertViewHas('desaList', fn ($l) => $l->count() === 1 && $l->first()->id === $desa->id)
            ->set('desa_id', $desa->id)
            ->set('nik', '7503010101010001')
            ->set('nama', 'Budi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pemohon', ['nik' => '7503010101010001', 'desa_id' => $desa->id]);
    }

    public function test_changing_provinsi_clears_downstream_selection(): void
    {
        $this->wilayah();

        Livewire::test(ManagePemohon::class)
            ->set('wProvinsi', '75')
            ->set('wKabupaten', '7503')
            ->set('wKecamatan', '750301')
            ->set('desa_id', '7503012001')
            ->set('wProvinsi', '11') // switch province
            ->assertSet('wKabupaten', '')
            ->assertSet('wKecamatan', '')
            ->assertSet('desa_id', '');
    }

    public function test_editing_pemohon_backfills_the_cascade(): void
    {
        ['desa' => $desa] = $this->wilayah();
        $pemohon = Pemohon::create(['nik' => '7503010101010009', 'nama' => 'Sri', 'desa_id' => $desa->id]);

        Livewire::test(ManagePemohon::class)
            ->call('edit', $pemohon->id)
            ->assertSet('wProvinsi', '75')
            ->assertSet('wKabupaten', '7503')
            ->assertSet('wKecamatan', '750301')
            ->assertSet('desa_id', $desa->id);
    }

    public function test_tanah_cascade_backfills_on_edit(): void
    {
        ['desa' => $desa] = $this->wilayah();
        $tanah = \App\Models\Tanah::create(['desa_id' => $desa->id, 'luas' => 100]);

        Livewire::test(ManageTanah::class)
            ->call('edit', $tanah->id)
            ->assertSet('wProvinsi', '75')
            ->assertSet('wKabupaten', '7503')
            ->assertSet('wKecamatan', '750301')
            ->assertSet('desa_id', $desa->id);
    }
}
