<?php

namespace Tests\Feature;

use App\Livewire\Pemeriksaan\ManagePemeriksaanBerkas;
use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstLayanan;
use App\Models\PemeriksaanBerkasFile;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PemeriksaanBerkasUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{Permohonan, MstBerkasItem, MstBerkasItem}
     */
    private function scenario(): array
    {
        $layanan = MstLayanan::create(['kode' => 'SRT-UP', 'nama' => 'Sertifikasi']);

        $sppf = MstBerkasItem::create(['nama' => 'Surat Pernyataan Penguasaan Fisik', 'kode' => 'SPPF']);
        $ktp = MstBerkasItem::create(['nama' => 'KTP Pemohon', 'kode' => 'KTP']);

        foreach ([$sppf, $ktp] as $i => $b) {
            MapLayananBerkas::create(['layanan_id' => $layanan->id, 'berkas_item_id' => $b->id, 'urutan' => $i + 1]);
        }

        $pemohon = Pemohon::create(['nik' => '7503010101010009', 'nama' => 'Pak Budi']);
        $permohonan = Permohonan::create([
            'nomor_registrasi' => 'REG-UP-1',
            'pemohon_id' => $pemohon->id,
            'layanan_id' => $layanan->id,
        ]);

        return [$permohonan, $sppf, $ktp];
    }

    public function test_upload_matches_files_to_berkas_by_kode_and_stores_them(): void
    {
        Storage::fake('local');
        [$permohonan, $sppf, $ktp] = $this->scenario();
        $user = User::create(['name' => 'Staf', 'email' => 's@app.com', 'hashed_password' => 'x', 'roles' => ['petugas'], 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('openUpload')
            ->set('uploads', [
                UploadedFile::fake()->create('SPPF-pak-budi.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('KTP.png'),
            ])
            // Auto-match: kedua file tertebak dari kode di nama.
            ->assertSet('uploadMap.0', $sppf->id)
            ->assertSet('uploadMap.1', $ktp->id)
            ->call('saveUploads')
            ->assertHasNoErrors();

        $this->assertSame(2, PemeriksaanBerkasFile::where('permohonan_id', $permohonan->id)->count());

        $sppfFile = PemeriksaanBerkasFile::where('berkas_item_id', $sppf->id)->first();
        $this->assertNotNull($sppfFile);
        $this->assertSame('SPPF-pak-budi.pdf', $sppfFile->nama_asli);
        $this->assertSame($user->id, $sppfFile->uploaded_by);
        Storage::disk('local')->assertExists($sppfFile->file_path);
    }

    public function test_unmapped_file_is_skipped_not_saved(): void
    {
        Storage::fake('local');
        [$permohonan] = $this->scenario();

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('openUpload')
            ->set('uploads', [UploadedFile::fake()->create('scan001.pdf', 80, 'application/pdf')])
            // Nama file tak mengandung kode apa pun -> tebakan kosong.
            ->assertSet('uploadMap.0', '')
            ->call('saveUploads')
            ->assertHasNoErrors();

        $this->assertSame(0, PemeriksaanBerkasFile::where('permohonan_id', $permohonan->id)->count());
    }

    public function test_delete_file_removes_from_disk_and_db(): void
    {
        Storage::fake('local');
        [$permohonan, $sppf] = $this->scenario();

        $path = UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')->store('pemeriksaan/'.$permohonan->id, 'local');
        $file = PemeriksaanBerkasFile::create([
            'permohonan_id' => $permohonan->id,
            'berkas_item_id' => $sppf->id,
            'file_path' => $path,
            'nama_asli' => 'x.pdf',
        ]);
        Storage::disk('local')->assertExists($path);

        Livewire::test(ManagePemeriksaanBerkas::class)
            ->set('selectedPermohonan', $permohonan->id)
            ->call('deleteFile', $file->id);

        $this->assertNull(PemeriksaanBerkasFile::find($file->id));
        Storage::disk('local')->assertMissing($path);
    }
}
