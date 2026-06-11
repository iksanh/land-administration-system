<?php

namespace Tests\Feature;

use App\Livewire\Catatan\ManageMstCatatan;
use App\Models\MstBerkasItem;
use App\Models\MstCatatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageMstCatatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_global_catatan(): void
    {
        Livewire::test(ManageMstCatatan::class)
            ->set('teks', 'Cek kelengkapan tanda tangan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mst_catatan', [
            'teks' => 'Cek kelengkapan tanda tangan', 'berkas_item_id' => null, 'is_active' => true,
        ]);
    }

    public function test_can_create_berkas_specific_catatan(): void
    {
        $berkas = MstBerkasItem::create(['nama' => 'KTP']);

        Livewire::test(ManageMstCatatan::class)
            ->set('teks', 'Cek keterbacaan')
            ->set('berkas_item_id', $berkas->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mst_catatan', ['teks' => 'Cek keterbacaan', 'berkas_item_id' => $berkas->id]);
    }

    public function test_teks_is_required(): void
    {
        Livewire::test(ManageMstCatatan::class)
            ->set('teks', '')
            ->call('save')
            ->assertHasErrors(['teks']);
    }

    public function test_search_filters_the_list(): void
    {
        MstCatatan::create(['teks' => 'Cek materai']);
        MstCatatan::create(['teks' => 'Cek tanda tangan']);

        Livewire::test(ManageMstCatatan::class)
            ->set('search', 'materai')
            ->assertViewHas('catatanList', fn ($list) => $list->count() === 1 && $list->first()->teks === 'Cek materai');
    }

    public function test_can_edit_and_delete(): void
    {
        $c = MstCatatan::create(['teks' => 'Lama']);

        Livewire::test(ManageMstCatatan::class)
            ->call('edit', $c->id)
            ->set('teks', 'Baru')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Baru', $c->refresh()->teks);

        Livewire::test(ManageMstCatatan::class)->call('delete', $c->id);
        $this->assertDatabaseMissing('mst_catatan', ['id' => $c->id]);
    }
}
