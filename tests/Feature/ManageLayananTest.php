<?php

namespace Tests\Feature;

use App\Livewire\Layanan\ManageLayanan;
use App\Models\MstLayanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageLayananTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_layanan(): void
    {
        Livewire::test(ManageLayanan::class)
            ->set('kode', 'LYN-001')
            ->set('nama', 'Sertifikasi Hak Milik')
            ->set('deskripsi', 'Penerbitan sertifikat')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mst_layanan', ['kode' => 'LYN-001', 'nama' => 'Sertifikasi Hak Milik', 'is_active' => true]);
    }

    public function test_kode_must_be_unique_on_create(): void
    {
        MstLayanan::create(['kode' => 'LYN-001', 'nama' => 'Existing']);

        Livewire::test(ManageLayanan::class)
            ->set('kode', 'LYN-001')->set('nama', 'Lain')
            ->call('save')
            ->assertHasErrors(['kode' => 'unique']);
    }

    public function test_requires_kode_and_nama(): void
    {
        Livewire::test(ManageLayanan::class)
            ->set('kode', '')->set('nama', '')
            ->call('save')
            ->assertHasErrors(['kode', 'nama']);
    }

    public function test_can_edit_layanan_and_keep_its_own_kode(): void
    {
        $layanan = MstLayanan::create(['kode' => 'LYN-009', 'nama' => 'Lama']);

        Livewire::test(ManageLayanan::class)
            ->call('edit', $layanan->id)
            ->assertSet('kode', 'LYN-009')
            ->set('nama', 'Baru')
            ->call('save') // same kode must NOT trip the unique rule
            ->assertHasNoErrors();

        $this->assertSame('Baru', $layanan->refresh()->nama);
    }

    public function test_can_delete_layanan(): void
    {
        $layanan = MstLayanan::create(['kode' => 'LYN-DEL', 'nama' => 'Hapus']);

        Livewire::test(ManageLayanan::class)->call('delete', $layanan->id);

        $this->assertDatabaseMissing('mst_layanan', ['id' => $layanan->id]);
    }
}
