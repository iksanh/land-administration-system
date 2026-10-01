<?php

namespace Tests\Feature;

use App\Livewire\BeritaAcara\ManageBeritaAcara;
use App\Models\BeritaAcaraPemeriksaan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RiwayatPenguasaan;
use App\Models\SkPanitia;
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
        $sk = SkPanitia::create(['nomor' => 'SK-BA/2025', 'is_active' => true]);
        $ketua = $sk->anggota()->create(['nama' => 'Ketua', 'peran' => 'KETUA', 'urutan' => 1]);
        $anggota = $sk->anggota()->create(['nama' => 'Anggota', 'peran' => 'ANGGOTA', 'urutan' => 2]);

        Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->assertSet('permohonan_id', $p->id)
            ->set('tgl_pemeriksaan', '2025-01-13')
            ->set('selectedPanitia', [$ketua->id, $anggota->id])
            ->call('save')
            ->assertHasNoErrors();

        $ba = BeritaAcaraPemeriksaan::where('permohonan_id', $p->id)->first();
        $this->assertNotNull($ba);
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

    public function test_riwayat_penguasaan_stored_once_per_permohonan(): void
    {
        $p = $this->permohonan();

        Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->call('openRiwayat', $p->id)
            ->set('riwayat_penguasaan', ['Poin awal.'])
            ->call('simpanRiwayat')
            ->call('openRiwayat', $p->id) // buka ulang -> edit
            ->set('riwayat_penguasaan', ['Poin diperbarui.', 'Poin kedua.'])
            ->call('simpanRiwayat')
            ->assertHasNoErrors();

        // Satu record riwayat per permohonan (updateOrCreate, tidak menggandakan).
        $this->assertSame(1, RiwayatPenguasaan::where('permohonan_id', $p->id)->count());
        $this->assertSame(
            ['Poin diperbarui.', 'Poin kedua.'],
            $p->fresh()->riwayatPenguasaan->poin,
        );
    }

    public function test_can_upload_and_remove_photo(): void
    {
        Storage::fake('public');
        $p = $this->permohonan();

        $component = Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->set('newPhotos', [UploadedFile::fake()->image('lapang.png', 1200, 900)])
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

    public function test_print_preview_modal_renders_document(): void
    {
        $p = $this->permohonan();
        $ba = BeritaAcaraPemeriksaan::create([
            'permohonan_id' => $p->id,
            'tgl_pemeriksaan' => '2025-01-13',
        ]);
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Dikuasai sejak 1996.']]);

        Livewire::test(ManageBeritaAcara::class)
            // Modal tertutup: dokumen belum dirender.
            ->assertSet('showPrint', false)
            ->assertDontSee('Pratinjau Berita Acara Pemeriksaan Lapang')
            ->call('openPrint', $ba->id)
            ->assertSet('showPrint', true)
            ->assertSet('printId', $ba->id)
            // Dokumen dirender inline di dalam modal (bukan tab baru).
            ->assertSee('Pratinjau Berita Acara Pemeriksaan Lapang')
            ->assertSee('Abdul Wahab Thaib')
            ->assertSee('Dikuasai sejak 1996.')
            ->call('closePrint')
            ->assertSet('showPrint', false)
            ->assertSet('printId', null);
    }

    public function test_print_page_renders(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'pba@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
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
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);
        $p = $this->permohonan();
        $ba = BeritaAcaraPemeriksaan::create([
            'permohonan_id' => $p->id,
            'tgl_pemeriksaan' => '2025-01-13',
        ]);
        RiwayatPenguasaan::create([
            'permohonan_id' => $p->id,
            'poin' => ['Dikuasai sejak 1996.'],
        ]);

        $res = $this->actingAs($user)->get(route('berita-acara.word', $ba->id));

        $res->assertOk()
            ->assertHeader('content-type', 'application/msword; charset=utf-8')
            ->assertSee('Abdul Wahab Thaib')
            ->assertSee('Dikuasai sejak 1996.');

        $this->assertStringContainsString('.doc', $res->headers->get('content-disposition'));
    }

    public function test_active_sk_is_prefilled_and_locked_after_save(): void
    {
        $p = $this->permohonan();
        $skLama = SkPanitia::create(['nomor' => 'SK-LAMA/2025', 'is_active' => true]);
        $ketua = $skLama->anggota()->create(['nama' => 'Ketua Lama', 'peran' => 'KETUA', 'urutan' => 1]);

        Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->assertSet('sk_panitia_id', $skLama->id)
            ->assertSet('selectedPanitia', [$ketua->id])
            ->call('save')
            ->assertHasNoErrors();

        // SK diganti setelah berita acara terbit.
        $skLama->update(['is_active' => false]);
        $skBaru = SkPanitia::create(['nomor' => 'SK-BARU/2026', 'is_active' => true]);
        $skBaru->anggota()->create(['nama' => 'Ketua Baru', 'peran' => 'KETUA', 'urutan' => 1]);

        $ba = BeritaAcaraPemeriksaan::where('permohonan_id', $p->id)->first();
        $this->assertSame($skLama->id, $ba->sk_panitia_id);
        $this->assertSame(['Ketua Lama'], $ba->panitia->pluck('nama')->all());
    }

    public function test_changing_sk_on_the_form_reloads_the_signers(): void
    {
        $p = $this->permohonan();
        $skA = SkPanitia::create(['nomor' => 'SK-A', 'is_active' => true]);
        $skA->anggota()->create(['nama' => 'Anggota A', 'peran' => 'KETUA', 'urutan' => 1]);
        $skB = SkPanitia::create(['nomor' => 'SK-B', 'is_active' => false]);
        $bAnggota = $skB->anggota()->create(['nama' => 'Anggota B', 'peran' => 'KETUA', 'urutan' => 1]);

        Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->set('sk_panitia_id', $skB->id)
            ->assertSet('selectedPanitia', [$bAnggota->id]);
    }

    public function test_riwayat_is_saved_by_its_own_action_not_by_saving_the_berita_acara(): void
    {
        $p = $this->permohonan();

        $component = Livewire::test(ManageBeritaAcara::class)
            ->call('createFor', $p->id)
            ->call('openRiwayat', $p->id)
            ->assertSet('showRiwayatModal', true)
            ->set('riwayat_penguasaan', ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.', '   ']);

        // Menyimpan berita acara TIDAK ikut menyimpan riwayat.
        $component->call('save')->assertHasNoErrors();
        $this->assertNull($p->fresh()->riwayatPenguasaan);

        // Riwayat punya aksi simpannya sendiri; poin kosong dibuang, urutan tetap.
        $component->call('simpanRiwayat')
            ->assertHasNoErrors()
            ->assertSet('showRiwayatModal', false);

        $this->assertSame(
            ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
            $p->fresh()->riwayatPenguasaan->poin,
        );
    }
}
