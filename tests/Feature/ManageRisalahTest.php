<?php

namespace Tests\Feature;

use App\Livewire\Risalah\ManageRisalah;
use App\Models\PanitiaPemeriksa;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefKepalaDesa;
use App\Models\RefProvinsi;
use App\Models\RisalahPanitiaA;
use App\Models\RiwayatPenguasaan;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManageRisalahTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(): Permohonan
    {
        $pemohon = Pemohon::create(['nik' => '7503010101010003', 'nama' => 'Abdul Wahab Thaib']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 5401]);

        return Permohonan::create([
            'nomor_registrasi' => 'REG-RIS-1',
            'pemohon_id' => $pemohon->id,
            'tanah_id' => $tanah->id,
        ]);
    }

    public function test_create_for_prefills_defaults_and_active_panitia(): void
    {
        $p = $this->permohonan();
        $ketua = PanitiaPemeriksa::create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1, 'is_active' => true]);
        PanitiaPemeriksa::create(['nama' => 'Nonaktif', 'peran' => 'ANGGOTA', 'urutan' => 3, 'is_active' => false]);

        $component = Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            ->assertSet('permohonan_id', $p->id)
            ->assertSet('showForm', true)
            // Hanya panitia aktif yang dipra-pilih.
            ->assertSet('selectedPanitia', [$ketua->id]);

        // Dasar hukum standar terisi otomatis.
        $this->assertNotEmpty($component->get('dasar_hukum'));
    }

    public function test_can_create_risalah_with_panitia_and_pendapat(): void
    {
        $p = $this->permohonan();
        $ketua = PanitiaPemeriksa::create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1]);
        $anggota = PanitiaPemeriksa::create(['nama' => 'Anggota', 'peran' => 'ANGGOTA', 'urutan' => 2]);

        Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            ->set('nomor_risalah', '45/2025')
            ->set('data_pendukung', ['Asli surat permohonan.', ''])
            ->set('selectedPanitia', [$ketua->id, $anggota->id])
            ->set("pendapat.{$ketua->id}", 'Setuju dikabulkan.')
            ->call('save')
            ->assertHasNoErrors();

        $r = RisalahPanitiaA::where('permohonan_id', $p->id)->first();
        $this->assertNotNull($r);
        $this->assertSame('45/2025', $r->nomor_risalah);
        // Baris kosong dibuang.
        $this->assertSame(['Asli surat permohonan.'], $r->data_pendukung);
        $this->assertCount(2, $r->panitia);
        $this->assertSame('Setuju dikabulkan.', $r->panitia->firstWhere('id', $ketua->id)->pivot->pendapat);
    }

    public function test_riwayat_penguasaan_is_read_only_reference_from_berita_acara(): void
    {
        $p = $this->permohonan();
        RiwayatPenguasaan::create([
            'permohonan_id' => $p->id,
            'poin' => ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
        ]);

        Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            // Riwayat dimuat read-only dari record Berita Acara.
            ->assertSet('riwayat_penguasaan', ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'])
            // Modal detail dapat dibuka & ditutup.
            ->assertSet('showRiwayatModal', false)
            ->call('showRiwayatDetail')
            ->assertSet('showRiwayatModal', true)
            ->call('closeRiwayatModal')
            ->assertSet('showRiwayatModal', false)
            ->call('save')
            ->assertHasNoErrors();

        // Menyimpan Risalah TIDAK mengubah riwayat penguasaan (sumber = Berita Acara).
        $this->assertSame(
            ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
            $p->refresh()->riwayatPenguasaan->poin,
        );
    }

    public function test_one_risalah_per_permohonan(): void
    {
        $p = $this->permohonan();

        Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            ->set('nomor_risalah', 'RIS-1')
            ->call('save')
            ->call('createFor', $p->id) // membuka yang sudah ada -> edit
            ->set('nomor_risalah', 'RIS-1-REV')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, RisalahPanitiaA::where('permohonan_id', $p->id)->count());
        $this->assertSame('RIS-1-REV', RisalahPanitiaA::first()->nomor_risalah);
    }

    public function test_can_delete_risalah(): void
    {
        $p = $this->permohonan();
        $r = RisalahPanitiaA::create(['permohonan_id' => $p->id]);

        Livewire::test(ManageRisalah::class)->call('delete', $r->id);

        $this->assertDatabaseMissing('risalah_panitia_a', ['id' => $r->id]);
    }

    public function test_print_page_renders(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'ris-print@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);
        $p = $this->permohonan();
        $r = RisalahPanitiaA::create(['permohonan_id' => $p->id, 'tgl_risalah' => '2025-01-13']);

        $this->actingAs($user)
            ->get(route('risalah.print', $r->id))
            ->assertOk()
            ->assertSee('Risalah Panitia Pemeriksaan Tanah')
            ->assertSee('Abdul Wahab Thaib');
    }

    public function test_print_includes_active_kepala_desa_of_tanah_desa(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'ris-kades@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);

        RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);
        RefKabupaten::create(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'BONE BOLANGO']);
        RefKecamatan::create(['id' => '750301', 'kabupaten_id' => '7503', 'nama' => 'KABILA']);
        $desa = RefDesa::create(['id' => '7503012001', 'kecamatan_id' => '750301', 'nama' => 'OLUHUTA']);
        RefKepalaDesa::create(['desa_id' => $desa->id, 'nama' => 'Yusuf Kades Aktif', 'is_active' => true]);
        RefKepalaDesa::create(['desa_id' => $desa->id, 'nama' => 'Rahman Kades Lama', 'is_active' => false]);

        $pemohon = Pemohon::create(['nik' => '7503010101010009', 'nama' => 'Abdul Wahab Thaib']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 5401, 'desa_id' => $desa->id]);
        $p = Permohonan::create([
            'nomor_registrasi' => 'REG-KADES-1', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id,
        ]);
        $r = RisalahPanitiaA::create(['permohonan_id' => $p->id, 'tgl_risalah' => '2025-01-13']);

        $this->actingAs($user)
            ->get(route('risalah.print', $r->id))
            ->assertOk()
            ->assertSee('Yusuf Kades Aktif')
            ->assertDontSee('Rahman Kades Lama');
    }

    public function test_word_download_returns_doc_attachment(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'ris-word@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);
        $p = $this->permohonan();
        $r = RisalahPanitiaA::create(['permohonan_id' => $p->id, 'tgl_risalah' => '2025-01-13']);
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Dikuasai sejak 1996.']]);

        $res = $this->actingAs($user)->get(route('risalah.word', $r->id));

        $res->assertOk()
            ->assertHeader('content-type', 'application/msword; charset=utf-8')
            ->assertSee('Abdul Wahab Thaib')
            ->assertSee('Dikuasai sejak 1996.');

        $this->assertStringContainsString('.doc', $res->headers->get('content-disposition'));
    }
}
