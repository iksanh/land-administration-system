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

    public function test_search_filters_berkas_by_name(): void
    {
        $layanan = MstLayanan::create(['kode' => 'LYN-1', 'nama' => 'Layanan 1']);
        $ktp = MstBerkasItem::create(['nama' => 'Fotokopi KTP']);
        $kk = MstBerkasItem::create(['nama' => 'Kartu Keluarga']);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $ktp->id, 'urutan' => 1]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $kk->id, 'urutan' => 2]);
        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-1', 'layanan_id' => $layanan->id]);

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->assertViewHas('berkasList', fn ($list) => $list->count() === 2)
            ->set('search', 'ktp') // case-insensitive
            ->assertViewHas('berkasList', fn ($list) => $list->count() === 1 && $list->first()->id === $ktp->id)
            ->assertViewHas('hasBerkas', true);
    }

    private function userWithRoles(array $roles): \App\Models\User
    {
        return \App\Models\User::create([
            'name' => 'User '.implode('-', $roles), 'email' => uniqid().'@app.com',
            'hashed_password' => \Illuminate\Support\Facades\Hash::make('x'),
            'roles' => $roles, 'is_active' => true,
        ]);
    }

    public function test_selesai_periksa_advances_stage_when_all_berkas_ok(): void
    {
        [$permohonan, $berkas] = $this->scenario();
        $permohonan->update(['status' => \App\Enums\PermohonanStatusEnum::PERIKSA_BERKAS_STAF]);
        $petugas = $this->userWithRoles(['petugas']);

        // Belum semua OK → ditolak.
        Livewire::actingAs($petugas)
            ->test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('selesaiPeriksa');
        $this->assertSame(\App\Enums\PermohonanStatusEnum::PERIKSA_BERKAS_STAF, $permohonan->refresh()->status);

        // Semua OK → maju ke Periksa Berkas (Korsub) + audit log otomatis.
        Livewire::actingAs($petugas)
            ->test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('setStatus', $berkas->id, 'OK')
            ->call('selesaiPeriksa');

        $this->assertSame(\App\Enums\PermohonanStatusEnum::PERIKSA_BERKAS_KORSUB, $permohonan->refresh()->status);

        $log = \App\Models\PermohonanAuditLog::where('permohonan_id', $permohonan->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(\App\Enums\PermohonanStatusEnum::PERIKSA_BERKAS_KORSUB, $log->status_baru);
        $this->assertSame($petugas->id, $log->petugas_id);
        $this->assertStringContainsString('Otomatis', $log->catatan);
    }

    public function test_selesai_periksa_respects_stage_role_gate(): void
    {
        [$permohonan, $berkas] = $this->scenario();
        $permohonan->update(['status' => \App\Enums\PermohonanStatusEnum::PERIKSA_BERKAS_KORSUB]);

        // Semua berkas OK, tapi tahap Korsub — petugas ditolak, koorsub boleh.
        \App\Models\PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $berkas->id,
            'status' => \App\Enums\PemeriksaanStatusEnum::OK,
        ]);

        Livewire::actingAs($this->userWithRoles(['petugas']))
            ->test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('selesaiPeriksa');
        $this->assertSame(\App\Enums\PermohonanStatusEnum::PERIKSA_BERKAS_KORSUB, $permohonan->refresh()->status);

        Livewire::actingAs($this->userWithRoles(['koorsub']))
            ->test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('selesaiPeriksa');
        $this->assertSame(\App\Enums\PermohonanStatusEnum::PROSES_DAFTAR, $permohonan->refresh()->status);
    }

    public function test_selesai_periksa_ignored_outside_periksa_stages(): void
    {
        [$permohonan, $berkas] = $this->scenario();
        $permohonan->update(['status' => \App\Enums\PermohonanStatusEnum::TERDAFTAR]);

        \App\Models\PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $berkas->id,
            'status' => \App\Enums\PemeriksaanStatusEnum::OK,
        ]);

        Livewire::actingAs($this->userWithRoles(['petugas', 'koorsub']))
            ->test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('selesaiPeriksa');

        $this->assertSame(\App\Enums\PermohonanStatusEnum::TERDAFTAR, $permohonan->refresh()->status);
    }

    public function test_query_param_preselects_permohonan(): void
    {
        [$permohonan] = $this->scenario();

        // Tautan langsung dari tombol aksi /permohonan.
        Livewire::withQueryParams(['permohonan' => $permohonan->id])
            ->test(ManagePemeriksaanBerkas::class)
            ->assertSet('selectedPermohonan', $permohonan->id)
            ->assertViewHas('berkasList', fn ($list) => $list->count() === 1);

        // Id tidak dikenal diabaikan.
        Livewire::withQueryParams(['permohonan' => 'bukan-id'])
            ->test(ManagePemeriksaanBerkas::class)
            ->assertSet('selectedPermohonan', '');
    }

    public function test_permohonan_combobox_filters_by_registrasi_and_pemohon(): void
    {
        $budi = \App\Models\Pemohon::create(['nama' => 'Budi Santoso', 'nik' => '7501010101010001']);
        $siti = \App\Models\Pemohon::create(['nama' => 'Siti Aminah', 'nik' => '7501010101010002']);
        $a = Permohonan::create(['nomor_registrasi' => 'REG-100', 'pemohon_id' => $budi->id]);
        $b = Permohonan::create(['nomor_registrasi' => 'REG-200', 'pemohon_id' => $siti->id]);

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 2)
            // Cari nama pemohon
            ->set('permohonanSearch', 'budi')
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 1 && $list->first()->id === $a->id)
            // Cari nomor registrasi
            ->set('permohonanSearch', 'REG-200')
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 1 && $list->first()->id === $b->id)
            // Cari NIK
            ->set('permohonanSearch', '0101010002')
            ->assertViewHas('permohonanList', fn ($list) => $list->count() === 1 && $list->first()->id === $b->id);
    }

    public function test_select_permohonan_via_combobox_clears_its_search(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('permohonanSearch', 'REG-1')
            ->call('selectPermohonan', $permohonan->id)
            ->assertSet('selectedPermohonan', $permohonan->id)
            ->assertSet('permohonanSearch', '')
            ->assertViewHas('berkasList', fn ($list) => $list->count() === 1);
    }

    public function test_clear_permohonan_resets_selection(): void
    {
        [$permohonan] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->call('selectPermohonan', $permohonan->id)
            ->call('clearPermohonan')
            ->assertSet('selectedPermohonan', '')
            ->assertViewHas('permohonan', null);
    }

    public function test_changing_permohonan_clears_search(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('search', 'apapun')
            ->set('selectedPermohonan', $permohonan->id)
            ->assertSet('search', '');
    }

    public function test_one_click_ok_creates_record_without_opening_editor(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('setStatus', $berkas->id, 'OK')
            ->assertHasNoErrors()
            // OK tak perlu catatan — editor tetap tertutup.
            ->assertSet('editingBerkasId', null);

        $row = PemeriksaanBerkas::where('permohonan_id', $permohonan->id)->where('berkas_item_id', $berkas->id)->first();
        $this->assertSame(PemeriksaanStatusEnum::OK, $row->status);
        $this->assertNull($row->catatan);
    }

    public function test_revisi_opens_catatan_editor_and_saves_custom_note(): void
    {
        [$permohonan, $berkas] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            // Satu klik REVISI menyimpan status dan langsung membuka editor catatan.
            ->call('setStatus', $berkas->id, 'REVISI')
            ->assertSet('editingBerkasId', $berkas->id)
            ->set('customCatatan', 'KTP tidak terbaca')
            ->call('saveCatatan')
            ->assertHasNoErrors()
            ->assertSet('editingBerkasId', null);

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
            ->call('setStatus', $berkas->id, 'OK')
            ->call('openCatatan', $berkas->id)
            ->set('selectedCatatanIds', [$mc->id])
            ->call('saveCatatan');

        $row = PemeriksaanBerkas::where('berkas_item_id', $berkas->id)->first();
        $this->assertSame($mc->id, $row->catatan[0]['id']);
        $this->assertFalse($row->catatan[0]['is_custom']);
    }

    public function test_setting_pending_removes_the_record(): void
    {
        [$permohonan, $berkas] = $this->scenario();
        PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $berkas->id, 'status' => 'OK',
        ]);

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('setStatus', $berkas->id, 'PENDING');

        $this->assertSame(0, PemeriksaanBerkas::where('permohonan_id', $permohonan->id)->count());
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
        $c->call('setStatus', $berkas->id, 'OK');
        $c->call('setStatus', $berkas->id, 'TOLAK');

        $this->assertSame(1, PemeriksaanBerkas::where('permohonan_id', $permohonan->id)->count());
        $this->assertSame(PemeriksaanStatusEnum::TOLAK, PemeriksaanBerkas::first()->status);
    }
}
