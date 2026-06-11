<?php

namespace Tests\Feature;

use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstLayanan;
use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PemeriksaanPrintTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Petugas', 'email' => 'p@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);
    }

    public function test_print_route_requires_authentication(): void
    {
        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-1']);

        $this->get(route('pemeriksaan.print', $permohonan))->assertRedirect('/login');
    }

    public function test_print_sheet_groups_parents_and_children_with_catatan(): void
    {
        $layanan = MstLayanan::create(['kode' => 'LYN-1', 'nama' => 'SERTIFIKASI HAK MILIK']);
        $parent = MstBerkasItem::create(['nama' => 'Formulir Permohonan']);
        $child = MstBerkasItem::create(['nama' => 'Surat Pernyataan', 'parent_id' => $parent->id]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $parent->id, 'urutan' => 1]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $child->id, 'urutan' => 2]);

        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-1', 'layanan_id' => $layanan->id]);

        PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $parent->id,
            'status' => 'REVISI', 'catatan' => [['id' => null, 'teks' => 'Tanggal belum diisi', 'is_custom' => true]],
        ]);
        // Child has no catatan -> should render "OK".
        PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $child->id, 'status' => 'OK',
        ]);

        $res = $this->actingAs($this->user())->get(route('pemeriksaan.print', $permohonan));

        $res->assertOk()
            ->assertSee('SERTIFIKASI HAK MILIK')   // layanan title
            ->assertSee('Formulir Permohonan')      // parent
            ->assertSee('a. Surat Pernyataan')      // child lettered
            ->assertSee('1.')                       // parent numbered
            ->assertSee('Tanggal belum diisi')      // parent catatan
            ->assertSee('OK');                       // child default catatan
    }

    public function test_print_sheet_lists_mapped_berkas_without_an_inspection_record(): void
    {
        // Regression: a berkas added to the layanan mapping must still appear on
        // the sheet even when it has no PemeriksaanBerkas row yet, and a child
        // must not be dropped just because its parent hasn't been inspected.
        $layanan = MstLayanan::create(['kode' => 'LYN-2', 'nama' => 'PENGUKURAN']);
        $parent = MstBerkasItem::create(['nama' => 'Formulir Permohonan']);
        $child = MstBerkasItem::create(['nama' => 'Surat Pernyataan', 'parent_id' => $parent->id]);
        $extra = MstBerkasItem::create(['nama' => 'KTP Pemohon']);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $parent->id, 'urutan' => 1]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $child->id, 'urutan' => 2]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $extra->id, 'urutan' => 3]);

        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-2', 'layanan_id' => $layanan->id]);

        // Only the child is inspected; parent + extra have no PemeriksaanBerkas row.
        PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id, 'berkas_item_id' => $child->id,
            'status' => 'REVISI', 'catatan' => [['id' => null, 'teks' => 'Belum ditandatangani', 'is_custom' => true]],
        ]);

        $res = $this->actingAs($this->user())->get(route('pemeriksaan.print', $permohonan));

        $res->assertOk()
            ->assertSee('Formulir Permohonan')      // un-inspected parent still listed
            ->assertSee('a. Surat Pernyataan')      // inspected child nested under it
            ->assertSee('Belum ditandatangani')     // child catatan
            ->assertSee('KTP Pemohon');             // un-inspected, newly-mapped berkas listed
    }

    public function test_non_ok_status_without_catatan_is_not_printed_as_ok(): void
    {
        // Regression: a berkas marked PENDING/REVISI/TOLAK with no note must not
        // print "OK" — the sheet must reflect its real status.
        $layanan = MstLayanan::create(['kode' => 'LYN-3', 'nama' => 'PENDAFTARAN']);
        $revisi = MstBerkasItem::create(['nama' => 'Berkas Revisi']);
        $tolak = MstBerkasItem::create(['nama' => 'Berkas Tolak']);
        $lengkap = MstBerkasItem::create(['nama' => 'Berkas Lengkap']);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $revisi->id, 'urutan' => 1]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $tolak->id, 'urutan' => 2]);
        MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $lengkap->id, 'urutan' => 3]);

        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-3', 'layanan_id' => $layanan->id]);

        // No catatan on any — status alone drives the printed text.
        PemeriksaanBerkas::create(['permohonan_id' => $permohonan->id, 'berkas_item_id' => $revisi->id, 'status' => 'REVISI']);
        PemeriksaanBerkas::create(['permohonan_id' => $permohonan->id, 'berkas_item_id' => $tolak->id, 'status' => 'TOLAK']);
        PemeriksaanBerkas::create(['permohonan_id' => $permohonan->id, 'berkas_item_id' => $lengkap->id, 'status' => 'OK']);

        $res = $this->actingAs($this->user())->get(route('pemeriksaan.print', $permohonan));

        $res->assertOk()
            ->assertSee('REVISI')   // REVISI, no note
            ->assertSee('TOLAK')    // TOLAK, no note
            ->assertSee('OK');      // genuine OK still prints "OK"
    }
}
