<?php

namespace Tests\Feature;

use App\Livewire\Risalah\ManageRisalah;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefKepalaDesa;
use App\Models\RefProvinsi;
use App\Models\RisalahPanitiaA;
use App\Models\RiwayatPenguasaan;
use App\Models\SkPanitia;
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
        $sk = SkPanitia::create(['nomor' => '134/SK-75.03/V/2025', 'tanggal' => '2025-05-12', 'is_active' => true]);
        $ketua = $sk->anggota()->create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1, 'is_active' => true]);
        $sk->anggota()->create(['nama' => 'Nonaktif', 'peran' => 'ANGGOTA', 'urutan' => 3, 'is_active' => false]);

        $component = Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            ->assertSet('permohonan_id', $p->id)
            ->assertSet('showForm', true)
            // Hanya panitia aktif di bawah SK aktif yang dipra-pilih, dan nomor
            // SK ikut terisi dari SK yang sama.
            ->assertSet('sk_panitia_id', $sk->id)
            ->assertSet('nomor_sk_panitia', '134/SK-75.03/V/2025')
            ->assertSet('tgl_sk_panitia', '2025-05-12')
            ->assertSet('selectedPanitia', [$ketua->id]);

        // Dasar hukum standar terisi otomatis.
        $this->assertNotEmpty($component->get('dasar_hukum'));
    }

    public function test_can_create_risalah_with_panitia_and_pendapat(): void
    {
        $p = $this->permohonan();
        $sk = SkPanitia::create(['nomor' => 'SK-RIS/2025', 'is_active' => true]);
        $ketua = $sk->anggota()->create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1]);
        $anggota = $sk->anggota()->create(['nama' => 'Anggota', 'peran' => 'ANGGOTA', 'urutan' => 2]);

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

    public function test_riwayat_penguasaan_is_shared_data_saved_by_its_own_action(): void
    {
        $p = $this->permohonan();
        RiwayatPenguasaan::create([
            'permohonan_id' => $p->id,
            'poin' => ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
        ]);

        Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            // Riwayat dimuat dari record bersama milik permohonan.
            ->assertSet('riwayat_penguasaan', ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'])
            // Editor modal dapat dibuka & ditutup.
            ->assertSet('showRiwayatModal', false)
            ->call('openRiwayat', $p->id)
            ->assertSet('showRiwayatModal', true)
            ->call('closeRiwayat')
            ->assertSet('showRiwayatModal', false)
            // Mengubah teks lalu menyimpan RISALAH tidak menyentuh riwayat.
            ->set('riwayat_penguasaan', ['Diubah tanpa disimpan.'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
            $p->refresh()->riwayatPenguasaan->poin,
        );
    }

    public function test_riwayat_can_be_edited_from_risalah_through_its_own_modal(): void
    {
        $p = $this->permohonan();
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Poin lama.']]);

        Livewire::test(ManageRisalah::class)
            ->call('createFor', $p->id)
            ->call('openRiwayat', $p->id)
            ->set('riwayat_penguasaan', ['Poin baru.', '  '])
            ->call('simpanRiwayat')
            ->assertHasNoErrors()
            ->assertSet('showRiwayatModal', false);

        $this->assertSame(['Poin baru.'], $p->refresh()->riwayatPenguasaan->poin);
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

    public function test_print_preview_modal_renders_document(): void
    {
        $p = $this->permohonan();
        $r = RisalahPanitiaA::create(['permohonan_id' => $p->id, 'tgl_risalah' => '2025-01-13']);
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Dikuasai sejak 1996.']]);

        Livewire::test(ManageRisalah::class)
            // Modal tertutup: dokumen belum dirender.
            ->assertSet('showPrint', false)
            ->assertDontSee('Pratinjau Risalah Panitia Pemeriksaan Tanah')
            ->call('openPrint', $r->id)
            ->assertSet('showPrint', true)
            ->assertSet('printId', $r->id)
            // Dokumen dirender inline di dalam modal (bukan tab baru).
            ->assertSee('Pratinjau Risalah Panitia Pemeriksaan Tanah')
            ->assertSee('Abdul Wahab Thaib')
            ->assertSee('Dikuasai sejak 1996.')
            ->call('closePrint')
            ->assertSet('showPrint', false)
            ->assertSet('printId', null);
    }

    public function test_print_page_renders(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'ris-print@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
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
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
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
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
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

    /**
     * Format cetak mengikuti dokumen resmi docs/RISALAH.pdf: kepala desa disebut
     * "ditunjuk sebagai", bagian IX memakai frasa ber-"Panitia" dengan urutan
     * ketua → anggota → kepala desa → sekretaris, dan ditutup paragraf baku.
     */
    public function test_print_follows_official_risalah_wording(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'ris-format@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);

        RefProvinsi::create(['id' => '75', 'nama' => 'GORONTALO']);
        RefKabupaten::create(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'BONE BOLANGO']);
        RefKecamatan::create(['id' => '750301', 'kabupaten_id' => '7503', 'nama' => 'SUWAWA TIMUR']);
        $desa = RefDesa::create(['id' => '7503012001', 'kecamatan_id' => '750301', 'nama' => 'PODUWOMA']);
        RefKepalaDesa::create(['desa_id' => $desa->id, 'nama' => 'Agus Salim Ishak', 'is_active' => true]);

        $sk = SkPanitia::create([
            'nomor' => '134/SK-75.03/V/2025',
            'tanggal' => '2025-05-27',
            'tentang' => 'Revisi Ke-I Susunan Tim Panitia Pemeriksaan Tanah "A" Tahun 2025',
            'is_active' => true,
        ]);
        $ketua = $sk->anggota()->create(['nama' => 'Silva R. Uno', 'peran' => 'KETUA', 'urutan' => 1]);
        $sekretaris = $sk->anggota()->create(['nama' => 'Vivi Oktaviani', 'peran' => 'SEKRETARIS', 'urutan' => 4]);

        $pemohon = Pemohon::create(['nik' => '7503010101010011', 'nama' => 'Suhariyaman Pateda']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 2726, 'desa_id' => $desa->id]);
        $p = Permohonan::create([
            'nomor_registrasi' => 'REG-FMT-1', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id,
        ]);
        $r = RisalahPanitiaA::create([
            'permohonan_id' => $p->id,
            'tgl_risalah' => '2026-01-05',
            'nomor_sk_panitia' => $sk->nomor,
            'tgl_sk_panitia' => $sk->tanggal,
            'sk_panitia_id' => $sk->id,
        ]);
        $r->panitia()->sync([
            $ketua->id => ['urutan' => 0],
            $sekretaris->id => ['urutan' => 1],
        ]);

        $html = $this->actingAs($user)->get(route('risalah.print', $r->id))->assertOk()->getContent();

        // Kepala desa: jabatan menyebut nama desa, tanpa koma sebelum "ditunjuk".
        $this->assertStringContainsString('Kepala Desa PODUWOMA ditunjuk sebagai Anggota', $html);
        // Bagian IX memakai frasa ber-"Panitia".
        $this->assertStringContainsString('sebagai Ketua Panitia Merangkap Anggota', $html);
        $this->assertStringContainsString('ditunjuk sebagai Sekretaris Merangkap Anggota', $html);
        // Paragraf penutup baku setelah pendapat anggota.
        $this->assertStringContainsString('batal demi hukum', $html);
        // Judul bagian I ikut menyebut nama pemohon.
        $this->assertMatchesRegularExpression('/URAIAN MENGENAI PEMOHON.{0,40}Suhariyaman Pateda/s', $html);
        // Klausa "tentang" pada butir SK diambil dari SK di Config Panitia.
        $this->assertStringContainsString('Revisi Ke-I Susunan Tim Panitia', $html);

        // Urutan bagian IX: kepala desa sebelum sekretaris (berbeda dari urutan
        // tanda tangan yang mengikuti kolom `urutan`).
        $ix = substr($html, strpos($html, 'PENDAPAT ANGGOTA PANITIA'));
        $this->assertLessThan(
            strpos($ix, 'Vivi Oktaviani'),
            strpos($ix, 'Agus Salim Ishak'),
        );
    }
}
