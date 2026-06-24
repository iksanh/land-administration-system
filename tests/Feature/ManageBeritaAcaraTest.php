<?php

namespace Tests\Feature;

use App\Livewire\BeritaAcara\ManageBeritaAcara;
use App\Models\BeritaAcaraPemeriksaan;
use App\Models\PanitiaPemeriksa;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManageBeritaAcaraTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(): Permohonan
    {
        $pemohon = Pemohon::create(['nik' => '7503010101010003', 'nama' => 'Abdul Wahab Thaib']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 5401]);

        return Permohonan::create([
            'nomor_registrasi' => 'REG-BA-1',
            'pemohon_id' => $pemohon->id,
            'tanah_id' => $tanah->id,
        ]);
    }

    public function test_can_create_berita_acara_with_panitia(): void
    {
        $p = $this->permohonan();
        $ketua = PanitiaPemeriksa::create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1]);
        $anggota = PanitiaPemeriksa::create(['nama' => 'Anggota', 'peran' => 'ANGGOTA', 'urutan' => 2]);

        Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->assertSet('permohonan_id', $p->id)
            ->set('tgl_pemeriksaan', '2025-01-13')
            ->set('riwayat_penguasaan', ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.', '   '])
            ->set('selectedPanitia', [$ketua->id, $anggota->id])
            ->call('save')
            ->assertHasNoErrors();

        $ba = BeritaAcaraPemeriksaan::where('permohonan_id', $p->id)->first();
        $this->assertNotNull($ba);
        // Poin kosong dibuang, urutan dipertahankan.
        $this->assertSame(
            ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
            $ba->riwayat_penguasaan,
        );
        $this->assertCount(2, $ba->panitia);
    }

    public function test_one_berita_acara_per_permohonan(): void
    {
        $p = $this->permohonan();

        Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->set('nomor_ba', 'BA-1')
            ->call('save')
            ->call('createFor', $p->id) // membuka yang sudah ada -> edit
            ->set('nomor_ba', 'BA-1-REV')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, BeritaAcaraPemeriksaan::where('permohonan_id', $p->id)->count());
        $this->assertSame('BA-1-REV', BeritaAcaraPemeriksaan::first()->nomor_ba);
    }

    public function test_can_upload_and_remove_photo(): void
    {
        Storage::fake('public');
        $p = $this->permohonan();

        $component = Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->set('newPhotos', [UploadedFile::fake()->image('lapang.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $ba = BeritaAcaraPemeriksaan::where('permohonan_id', $p->id)->first();
        $this->assertCount(1, $ba->lampiran);
        Storage::disk('public')->assertExists($ba->lampiran->first()->path);

        $lampiranId = $ba->lampiran->first()->id;
        $path = $ba->lampiran->first()->path;
        $component->call('removeLampiran', $lampiranId);

        $this->assertCount(0, $ba->refresh()->lampiran);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_can_delete_berita_acara(): void
    {
        $p = $this->permohonan();
        $ba = BeritaAcaraPemeriksaan::create(['permohonan_id' => $p->id]);

        Livewire::test(ManageBeritaAcara::class)->call('delete', $ba->id);

        $this->assertDatabaseMissing('berita_acara_pemeriksaan', ['id' => $ba->id]);
    }

    public function test_print_page_renders(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'pba@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);
        $p = $this->permohonan();
        $ba = BeritaAcaraPemeriksaan::create([
            'permohonan_id' => $p->id,
            'tgl_pemeriksaan' => '2025-01-13',
        ]);

        $this->actingAs($user)
            ->get(route('berita-acara.print', $ba->id))
            ->assertOk()
            ->assertSee('Berita Acara Pemeriksaan Lapang')
            ->assertSee('Abdul Wahab Thaib');
    }

    public function test_word_download_returns_doc_attachment(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'pword@app.com',
            'hashed_password' => Hash::make('x'), 'role' => 'petugas', 'is_active' => true,
        ]);
        $p = $this->permohonan();
        $ba = BeritaAcaraPemeriksaan::create([
            'permohonan_id' => $p->id,
            'tgl_pemeriksaan' => '2025-01-13',
            'riwayat_penguasaan' => ['Dikuasai sejak 1996.'],
        ]);

        $res = $this->actingAs($user)->get(route('berita-acara.word', $ba->id));

        $res->assertOk()
            ->assertHeader('content-type', 'application/msword; charset=utf-8')
            ->assertSee('Abdul Wahab Thaib')
            ->assertSee('Dikuasai sejak 1996.');

        $this->assertStringContainsString('.doc', $res->headers->get('content-disposition'));
    }
}
