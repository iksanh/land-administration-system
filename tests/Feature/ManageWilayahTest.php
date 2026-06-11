<?php

namespace Tests\Feature;

use App\Livewire\Wilayah\ManageWilayah;
use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefProvinsi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageWilayahTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_provinsi(): void
    {
        Livewire::test(ManageWilayah::class)
            ->set('provId', '75')
            ->set('provNama', 'GORONTALO')
            ->call('addProvinsi')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ref_provinsi', ['id' => '75', 'nama' => 'GORONTALO']);
    }

    public function test_provinsi_id_must_be_unique(): void
    {
        RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);

        Livewire::test(ManageWilayah::class)
            ->set('provId', '75')->set('provNama', 'Lain')
            ->call('addProvinsi')
            ->assertHasErrors(['provId' => 'unique']);
    }

    public function test_selecting_provinsi_filters_kabupaten_list(): void
    {
        $prov = RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);
        $other = RefProvinsi::create(['id' => '11', 'nama' => 'ACEH']);
        RefKabupaten::create(['id' => '7503', 'provinsi_id' => $prov->id, 'nama' => 'BONE BOLANGO']);
        RefKabupaten::create(['id' => '1101', 'provinsi_id' => $other->id, 'nama' => 'ACEH SELATAN']);

        Livewire::test(ManageWilayah::class)
            ->call('selectProvinsi', '75')
            ->assertViewHas('kabupatenList', fn ($list) => $list->count() === 1 && $list->first()->nama === 'BONE BOLANGO');
    }

    public function test_add_kabupaten_links_to_selected_provinsi(): void
    {
        RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);

        Livewire::test(ManageWilayah::class)
            ->call('selectProvinsi', '75')
            ->set('kabId', '7503')
            ->set('kabNama', 'BONE BOLANGO')
            ->call('addKabupaten')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ref_kabupaten', ['id' => '7503', 'provinsi_id' => '75', 'nama' => 'BONE BOLANGO']);
    }

    public function test_full_drilldown_to_desa(): void
    {
        RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);
        RefKabupaten::create(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'BONE BOLANGO']);
        RefKecamatan::create(['id' => '750301', 'kabupaten_id' => '7503', 'nama' => 'KABILA']);

        Livewire::test(ManageWilayah::class)
            ->call('selectProvinsi', '75')
            ->call('selectKabupaten', '7503')
            ->call('selectKecamatan', '750301')
            ->set('desaId', '7503012001')
            ->set('desaNama', 'OLUHUTA')
            ->set('desaKepala', 'Pak Kades')
            ->call('addDesa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ref_desa', [
            'id' => '7503012001', 'kecamatan_id' => '750301', 'nama' => 'OLUHUTA', 'nama_kepala_desa' => 'Pak Kades',
        ]);
        $this->assertSame(1, RefDesa::where('kecamatan_id', '750301')->count());
    }
}
