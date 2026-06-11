<?php

namespace Tests\Feature;

use App\Enums\PemeriksaanStatusEnum;
use App\Livewire\Pemeriksaan\ManagePemeriksaanBerkas;
use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstCatatan;
use App\Models\MstLayanan;
use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagePemeriksaanBerkasTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $layanan = MstLayanan::create(['kode' => 'LYN-1', 'nama' => 'Layanan 1']);
        $berkas = MstBerkasItem::create(['nama' => 'KTP']);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $berkas->id, 'urutan' => 1]);
        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-1', 'layanan_id' => $layanan->id]);

        return [$permohonan, $berkas];
    }

    public function test_checklist_comes_from_the_layanan_mapping(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->assertViewHas('berkasList', fn ($list) => $list->count() === 1 && $list->first()->id === $berkas->id);
    }

    public function test_save_creates_pemeriksaan_with_status_and_custom_catatan(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('startPeriksa', $berkas->id)
            ->set('formStatus', 'REVISI')
            ->set('customCatatan', 'KTP tidak terbaca')
            ->call('savePeriksa')
            ->assertHasNoErrors();

        $row = PemeriksaanBerkas::where('permohonan_id', $permohonan->id)->where('berkas_item_id', $berkas->id)->first();
        $this->assertSame(PemeriksaanStatusEnum::REVISI, $row->status);
        $this->assertIsArray($row->catatan);
        $this->assertSame('KTP tidak terbaca', $row->catatan[0]['teks']);
        $this->assertTrue($row->catatan[0]['is_custom']);
    }

    public function test_catalog_catatan_is_stored_with_its_id(): void
    {
        [$permohonan, $berkas] = $this->scenario();
        $mc = MstCatatan::create(['teks' => 'Cek materai', 'berkas_item_id' => $berkas->id]);

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('startPeriksa', $berkas->id)
            ->set('formStatus', 'OK')
            ->set('selectedCatatanIds', [$mc->id])
            ->call('savePeriksa');

        $row = PemeriksaanBerkas::where('berkas_item_id', $berkas->id)->first();
        $this->assertSame($mc->id, $row->catatan[0]['id']);
        $this->assertFalse($row->catatan[0]['is_custom']);
    }

    public function test_print_preview_modal_opens_and_shows_the_sheet(): void
    {
        [$permohonan, $berkas] = $this->scenario();
        PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $berkas->id, 'status' => 'OK',
        ]);

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->assertDontSee('Pratinjau Lembar Pemeriksaan')
            ->call('openPrint')
            ->assertSet('showPrint', true)
            ->assertSee('Pratinjau Lembar Pemeriksaan')
            ->assertSee('KTP')
            ->call('closePrint')
            ->assertSet('showPrint', false);
    }

    public function test_re_examining_updates_the_same_row_no_duplicate(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        $c = Livewire::test(ManagePemeriksaanBerkas::class)->set('selectedPermohonan', $permohonan->id);
        $c->call('startPeriksa', $berkas->id)->set('formStatus', 'OK')->call('savePeriksa');
        $c->call('startPeriksa', $berkas->id)->set('formStatus', 'TOLAK')->call('savePeriksa');

        $this->assertSame(1, PemeriksaanBerkas::where('permohonan_id', $permohonan->id)->count());
        $this->assertSame(PemeriksaanStatusEnum::TOLAK, PemeriksaanBerkas::first()->status);
    }
}
