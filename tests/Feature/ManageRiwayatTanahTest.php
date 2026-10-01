<?php

namespace Tests\Feature;

use App\Livewire\RiwayatTanah\ManageRiwayatTanah;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RiwayatPenguasaan;
use App\Models\Tanah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageRiwayatTanahTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(string $nomor = 'REG-RT-1'): Permohonan
    {
        $pemohon = Pemohon::create(['nik' => '7503010101010004', 'nama' => 'Abdul Wahab Thaib']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 5401]);

        return Permohonan::create([
            'nomor_registrasi' => $nomor,
            'pemohon_id' => $pemohon->id,
            'tanah_id' => $tanah->id,
        ]);
    }

    public function test_can_save_riwayat_without_creating_a_berita_acara(): void
    {
        $p = $this->permohonan();

        Livewire::test(ManageRiwayatTanah::class)
            ->call('editFor', $p->id)
            ->assertSet('showForm', true)
            ->set('riwayat_penguasaan', ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.', '   '])
            ->call('save')
            ->assertHasNoErrors();

        // Poin kosong dibuang, urutan dipertahankan.
        $this->assertSame(
            ['Dikuasai Rasid Nusi sejak 1996.', 'Dijual ke Abdul Wahab 2003.'],
            $p->fresh()->riwayatPenguasaan->poin,
        );
        // Tidak ada berita acara yang perlu dibuat lebih dulu.
        $this->assertDatabaseCount('berita_acara_pemeriksaan', 0);
    }

    public function test_requires_a_permohonan(): void
    {
        Livewire::test(ManageRiwayatTanah::class)
            ->set('riwayat_penguasaan', ['Sebuah poin.'])
            ->call('save')
            ->assertHasErrors(['permohonan_id']);
    }

    public function test_edit_for_loads_existing_points(): void
    {
        $p = $this->permohonan();
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Poin A.', 'Poin B.']]);

        Livewire::test(ManageRiwayatTanah::class)
            ->call('editFor', $p->id)
            ->assertSet('riwayat_penguasaan', ['Poin A.', 'Poin B.']);
    }

    public function test_query_string_permohonan_opens_the_editor(): void
    {
        $p = $this->permohonan();
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Poin A.']]);

        Livewire::withQueryParams(['permohonan' => $p->id])
            ->test(ManageRiwayatTanah::class)
            ->assertSet('showForm', true)
            ->assertSet('permohonan_id', $p->id)
            ->assertSet('riwayat_penguasaan', ['Poin A.']);
    }

    public function test_can_clear_riwayat(): void
    {
        $p = $this->permohonan();
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Poin A.']]);

        Livewire::test(ManageRiwayatTanah::class)->call('clear', $p->id);

        $this->assertNull($p->fresh()->riwayatPenguasaan);
    }

    public function test_filter_shows_only_permohonan_without_riwayat(): void
    {
        $terisi = $this->permohonan('REG-RT-ISI');
        RiwayatPenguasaan::create(['permohonan_id' => $terisi->id, 'poin' => ['Poin A.']]);

        $kosong = Permohonan::create([
            'nomor_registrasi' => 'REG-RT-KOSONG',
            'pemohon_id' => $terisi->pemohon_id,
            'tanah_id' => $terisi->tanah_id,
        ]);
        // Record ada tapi poin null — tetap dihitung "belum diisi".
        RiwayatPenguasaan::create(['permohonan_id' => $kosong->id, 'poin' => null]);

        Livewire::test(ManageRiwayatTanah::class)
            ->set('hanyaBelumDiisi', true)
            ->assertSee('REG-RT-KOSONG')
            ->assertDontSee('REG-RT-ISI');
    }
}
