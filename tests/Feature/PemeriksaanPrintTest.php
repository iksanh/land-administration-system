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
}
