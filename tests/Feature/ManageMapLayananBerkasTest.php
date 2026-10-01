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

    private function urutanOf(MstLayanan $layanan): array
    {
        return MapLayananBerkas::where('layanan_id', $layanan->id)
            ->orderBy('urutan')->pluck('urutan', 'berkas_item_id')->all();
    }

    public function test_sort_moves_item_to_dropped_position_and_renumbers(): void
    {
        [$layanan, $ktp, $kk] = $this->makeData();
        $sppt = MstBerkasItem::create(['nama' => 'SPPT']);

        $c = Livewire::test(ManageMapLayananBerkas::class)->set('selectedLayanan', $layanan->id);
        $c->call('toggle', $ktp->id)->call('toggle', $kk->id)->call('toggle', $sppt->id);

        // Drag SPPT (last) to the top (0-based position 0).
        $c->call('sortBerkas', $sppt->id, 0);

        $this->assertSame([$sppt->id => 1, $ktp->id => 2, $kk->id => 3], $this->urutanOf($layanan));
    }

    public function test_move_berkas_swaps_with_neighbour_and_clamps_at_edges(): void
    {
        [$layanan, $ktp, $kk] = $this->makeData();

        $c = Livewire::test(ManageMapLayananBerkas::class)->set('selectedLayanan', $layanan->id);
        $c->call('toggle', $ktp->id)->call('toggle', $kk->id);

        $c->call('moveBerkas', $kk->id, -1);
        $this->assertSame([$kk->id => 1, $ktp->id => 2], $this->urutanOf($layanan));

        $c->call('moveBerkas', $kk->id, -1); // already first → no change
        $this->assertSame([$kk->id => 1, $ktp->id => 2], $this->urutanOf($layanan));
    }

    public function test_removing_a_mapping_closes_the_urutan_gap(): void
    {
        [$layanan, $ktp, $kk] = $this->makeData();
        $sppt = MstBerkasItem::create(['nama' => 'SPPT']);

        $c = Livewire::test(ManageMapLayananBerkas::class)->set('selectedLayanan', $layanan->id);
        $c->call('toggle', $ktp->id)->call('toggle', $kk->id)->call('toggle', $sppt->id);
        $c->call('toggle', $kk->id);

        $this->assertSame([$ktp->id => 1, $sppt->id => 2], $this->urutanOf($layanan));
    }
}
