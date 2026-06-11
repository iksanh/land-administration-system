<?php

namespace Tests\Feature;

use App\Livewire\Berkas\ManageMapLayananBerkas;
use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstLayanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageMapLayananBerkasTest extends TestCase
{
    use RefreshDatabase;

    private function makeData(): array
    {
        return [
            MstLayanan::create(['kode' => 'LYN-1', 'nama' => 'Layanan 1']),
            MstBerkasItem::create(['nama' => 'KTP']),
            MstBerkasItem::create(['nama' => 'KK']),
        ];
    }

    public function test_toggle_adds_mapping_with_urutan_one(): void
    {
        [$layanan, $ktp] = $this->makeData();

        Livewire::test(ManageMapLayananBerkas::class)
            ->set('selectedLayanan', $layanan->id)
            ->call('toggle', $ktp->id);

        $this->assertDatabaseHas('map_layanan_berkas', [
            'layanan_id' => $layanan->id, 'berkas_item_id' => $ktp->id, 'urutan' => 1,
        ]);
    }

    public function test_second_mapping_gets_next_urutan(): void
    {
        [$layanan, $ktp, $kk] = $this->makeData();

        $c = Livewire::test(ManageMapLayananBerkas::class)->set('selectedLayanan', $layanan->id);
        $c->call('toggle', $ktp->id);
        $c->call('toggle', $kk->id);

        $this->assertDatabaseHas('map_layanan_berkas', ['berkas_item_id' => $kk->id, 'urutan' => 2]);
        $this->assertSame(2, MapLayananBerkas::where('layanan_id', $layanan->id)->count());
    }

    public function test_toggle_again_removes_mapping(): void
    {
        [$layanan, $ktp] = $this->makeData();

        $c = Livewire::test(ManageMapLayananBerkas::class)->set('selectedLayanan', $layanan->id);
        $c->call('toggle', $ktp->id);
        $c->call('toggle', $ktp->id);

        $this->assertSame(0, MapLayananBerkas::where('layanan_id', $layanan->id)->count());
    }

    public function test_update_urutan_changes_value(): void
    {
        [$layanan, $ktp] = $this->makeData();

        $c = Livewire::test(ManageMapLayananBerkas::class)->set('selectedLayanan', $layanan->id);
        $c->call('toggle', $ktp->id);
        $c->call('updateUrutan', $ktp->id, 5);

        $this->assertDatabaseHas('map_layanan_berkas', [
            'layanan_id' => $layanan->id, 'berkas_item_id' => $ktp->id, 'urutan' => 5,
        ]);
    }
}
