<?php

namespace Tests\Feature;

use App\Livewire\Berkas\ManageBerkasItem;
use App\Models\MstBerkasItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageBerkasItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_root_berkas(): void
    {
        Livewire::test(ManageBerkasItem::class)
            ->set('nama', 'KTP Pemohon')
            ->set('is_mandatory', true)
            ->set('catatan', 'Cek keterbacaan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mst_berkas_item', [
            'nama' => 'KTP Pemohon', 'is_mandatory' => true, 'catatan' => 'Cek keterbacaan', 'parent_id' => null,
        ]);
    }

    public function test_can_create_child_berkas_under_parent(): void
    {
        $parent = MstBerkasItem::create(['nama' => 'Surat Tanah']);

        Livewire::test(ManageBerkasItem::class)
            ->set('nama', 'Sub Surat')
            ->set('parent_id', $parent->id)
            ->call('save')
            ->assertHasNoErrors();

        $child = MstBerkasItem::where('nama', 'Sub Surat')->first();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertTrue($parent->subBerkas->contains('id', $child->id));
    }

    public function test_nama_is_required(): void
    {
        Livewire::test(ManageBerkasItem::class)
            ->set('nama', '')
            ->call('save')
            ->assertHasErrors(['nama']);
    }

    public function test_item_cannot_be_its_own_parent(): void
    {
        $item = MstBerkasItem::create(['nama' => 'Mandiri']);

        Livewire::test(ManageBerkasItem::class)
            ->call('edit', $item->id)
            ->set('parent_id', $item->id)
            ->call('save')
            ->assertHasErrors(['parent_id']);
    }

    public function test_deleting_parent_cascades_to_children(): void
    {
        $parent = MstBerkasItem::create(['nama' => 'Induk']);
        $child = MstBerkasItem::create(['nama' => 'Anak', 'parent_id' => $parent->id]);

        Livewire::test(ManageBerkasItem::class)->call('delete', $parent->id);

        $this->assertDatabaseMissing('mst_berkas_item', ['id' => $parent->id]);
        $this->assertDatabaseMissing('mst_berkas_item', ['id' => $child->id]);
    }
}
