<?php

namespace Tests\Feature;

use App\Livewire\BeritaAcara\ManageBeritaAcara;
use App\Models\BeritaAcaraPemeriksaan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefKepalaDesa;
use App\Models\RefProvinsi;
use App\Models\RiwayatPenguasaan;
use App\Models\SkPanitia;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BeritaAcaraFormatTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(string $email): User
    {
        return User::create([
            'name' => 'Petugas', 'email' => $email,
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);
    }

    /**
     * Format cetak mengikuti dokumen resmi
     * docs/BERITA ACARA PEMERIKSAAN LAPANG OLEH.pdf.
     */
    public function test_print_follows_official_berita_acara_wording(): void
    {
        $user = $this->petugas('ba-format@app.com');

        RefProvinsi::create(['id' => '75', 'nama' => 'Gorontalo']);
        RefKabupaten::create(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'Bone Bolango']);
        RefKecamatan::create(['id' => '750301', 'kabupaten_id' => '7503', 'nama' => 'Kabila']);
        $desa = RefDesa::create([
            'id' => '7503012001', 'kecamatan_id' => '750301',
            'nama' => 'Tumbihe', 'jenis' => 'KELURAHAN',
        ]);
        RefKepalaDesa::create([
            'desa_id' => $desa->id, 'nama' => 'TAUHID F. MASSA, SE.',
            'nip' => '198009262006041013', 'is_active' => true,
        ]);

        $sk = SkPanitia::create(['nomor' => 'SK-1/2026', 'is_active' => true]);
        $a1 = $sk->anggota()->create([
            'nama' => 'SILVA R. UNO, S.H.', 'nip' => '19820405 200312 2 002',
            'jabatan' => 'Kepala Seksi Penetapan Hak dan Pendaftaran Kantor Pertanahan Kabupaten Bone Bolango',
            'peran' => 'KETUA', 'urutan' => 1,
        ]);
        $a2 = $sk->anggota()->create([
            'nama' => 'SRI BINTANG PAMUNGKASLARA, S.Si', 'nip' => '19940830 201903 1 001',
            'jabatan' => 'Penata Pertanahan Pertama Kantor Pertanahan Kabupaten Bone Bolango',
            'peran' => 'SEKRETARIS', 'urutan' => 4,
        ]);

        $pemohon = Pemohon::create([
            'nik' => '7503010101010021', 'nama' => 'MEGAWATI WANTOGIA', 'jenis_kelamin' => 'P',
        ]);
        $tanah = Tanah::create([
            'pemohon_id' => $pemohon->id, 'desa_id' => $desa->id,
            'luas' => 349, 'nomor_pbt' => '36/2025', 'tanggal_pbt' => '2025-08-25', 'nib' => '00571',
            'penggunaan_tanah' => 'Tanah Pekarangan', 'rencana_penggunaan_rtrw' => 'Non Pertanian',
            'tgl_peta_analisis' => '2025-09-08', 'rtrw_kawasan' => 'Kawasan Permukiman Perkotaan',
            'kesesuaian_penggunaan_tanah' => 'Sesuai',
            'pejabat_peta_analisis' => 'Ichsandy Masloman, S.H.', 'nip_peta_analisis' => '197506212002121004',
            'batas_utara' => 'M.336/Tumbihe, Lukman Yasin', 'batas_timur' => 'M.622/Tumbihe',
            'batas_selatan' => 'Jalan', 'batas_barat' => 'Harun Saman',
        ]);
        $p = Permohonan::create([
            'nomor_registrasi' => 'REG-BA-FMT', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id,
        ]);
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => [
            'Bahwa sebelumnya bidang tanah yang dimohon dikuasai oleh Alm. Karnain Yusuf sejak tahun 1960 berdasarkan bukaan lahan sendiri tanpa surat',
            'Bahwa selanjutnya bidang tanah yang dimohon dikuasai oleh Buyung Yusuf sejak tahun 1998 berdasarkan hibah tanpa surat',
            'Bahwa bidang tanah yang dimohon sampai dengan saat ini dikuasai terus menerus oleh pemohon, belum bersertipikat dan tidak dalam sengketa',
        ]]);

        $ba = BeritaAcaraPemeriksaan::create([
            'permohonan_id' => $p->id,
            'tgl_pemeriksaan' => '2026-05-25',
            'perda_rtrw' => ManageBeritaAcara::DEFAULT_PERDA,
        ]);
        $ba->panitia()->sync([$a1->id => ['urutan' => 0], $a2->id => ['urutan' => 1]]);

        $html = $this->actingAs($user)->get(route('berita-acara.print', $ba->id))->assertOk()->getContent();
        $teks = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));

        // Kelurahan disebut sesuai jenisnya, pemimpinnya "Lurah" tanpa koma.
        $this->assertStringContainsString('terletak di Kelurahan Tumbihe, Kecamatan Kabila', $teks);
        $this->assertStringContainsString('Lurah Tumbihe sebagai Anggota', $teks);

        // Sapaan mengikuti jenis kelamin pemohon.
        $this->assertStringContainsString('atas permohonan dari Sdri. MEGAWATI WANTOGIA', $teks);

        // 1.a dibuka kalimat "dikuasai oleh ... dengan riwayat;" lalu butir ber-';'
        // dan butir terakhir ber-'.'.
        $this->assertStringContainsString('dikuasai oleh MEGAWATI WANTOGIA dengan riwayat;', $teks);
        $this->assertStringContainsString('bukaan lahan sendiri tanpa surat;', $teks);
        $this->assertStringContainsString('belum bersertipikat dan tidak dalam sengketa.', $teks);

        // 1.b memuat tema peta analisis + penanda tangannya + kawasan RTRW.
        $this->assertStringContainsString('Tema Kesesuaian Penggunaan Tanah Terhadap RTRW', $teks);
        $this->assertStringContainsString('Ichsandy Masloman, S.H. NIP. 197506212002121004', $teks);
        $this->assertStringContainsString('berada dalam Kawasan Permukiman Perkotaan', $teks);

        // 1.c kalimat baku terbentuk otomatis dari data tanah.
        $this->assertStringContainsString(
            'keadaan tanah saat ini (existing land use) sesuai hasil tinjauan kami di lapangan adalah Tanah Pekarangan yang akan digunakan untuk Non Pertanian.',
            $teks,
        );

        // 2. batas: ';' di tiap baris, '.' di baris terakhir.
        $this->assertStringContainsString('berbatasan dengan M.622/Tumbihe;', $teks);
        $this->assertStringContainsString('berbatasan dengan Harun Saman.', $teks);

        // 3 & 4 memakai bunyi dokumen resmi.
        $this->assertStringContainsString('mengajukan keberatan atau merasa keberatan terhadap Permohonan Hak dimaksud;', $teks);
        $this->assertStringContainsString('Lampiran Dokumentasi Pemeriksaan Lapang;', $teks);
    }

    public function test_desa_default_still_prints_desa_and_kepala_desa(): void
    {
        $user = $this->petugas('ba-desa@app.com');

        RefProvinsi::create(['id' => '75', 'nama' => 'Gorontalo']);
        RefKabupaten::create(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'Bone Bolango']);
        RefKecamatan::create(['id' => '750302', 'kabupaten_id' => '7503', 'nama' => 'Suwawa Timur']);
        // Tanpa menyebut `jenis` — kolomnya default DESA, jadi data lama tetap benar.
        $desa = RefDesa::create(['id' => '7503022001', 'kecamatan_id' => '750302', 'nama' => 'Poduwoma']);
        RefKepalaDesa::create(['desa_id' => $desa->id, 'nama' => 'Agus Salim Ishak', 'is_active' => true]);

        $pemohon = Pemohon::create(['nik' => '7503010101010022', 'nama' => 'Suhariyaman Pateda', 'jenis_kelamin' => 'L']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'desa_id' => $desa->id, 'luas' => 2726]);
        $p = Permohonan::create([
            'nomor_registrasi' => 'REG-BA-DESA', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id,
        ]);
        $ba = BeritaAcaraPemeriksaan::create(['permohonan_id' => $p->id, 'tgl_pemeriksaan' => '2026-05-25']);

        $html = $this->actingAs($user)->get(route('berita-acara.print', $ba->id))->assertOk()->getContent();
        $teks = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));

        $this->assertStringContainsString('terletak di Desa Poduwoma, Kecamatan Suwawa Timur', $teks);
        $this->assertStringContainsString('Kepala Desa Poduwoma sebagai Anggota', $teks);
        $this->assertStringContainsString('atas permohonan dari Sdr. Suhariyaman Pateda', $teks);
    }

    /**
     * Lembar tanda tangan panitia tidak lagi dicetak (diedarkan & dipindai
     * terpisah), dan tiap lampiran mengisi satu halaman penuh dengan ukuran yang
     * dihitung dari dimensi asli berkasnya.
     */
    public function test_lampiran_fills_one_page_each_and_signature_block_is_gone(): void
    {
        Storage::fake('public');

        $user = $this->petugas('ba-lampiran@app.com');

        $sk = SkPanitia::create(['nomor' => 'SK-L/2026', 'is_active' => true]);
        $ketua = $sk->anggota()->create(['nama' => 'SILVA R. UNO, S.H.', 'peran' => 'KETUA', 'urutan' => 1]);

        $pemohon = Pemohon::create(['nik' => '7503010101010023', 'nama' => 'Megawati Wantogia', 'jenis_kelamin' => 'P']);
        $tanah = Tanah::create(['pemohon_id' => $pemohon->id, 'luas' => 349]);
        $p = Permohonan::create([
            'nomor_registrasi' => 'REG-BA-LMP', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id,
        ]);
        $ba = BeritaAcaraPemeriksaan::create(['permohonan_id' => $p->id, 'tgl_pemeriksaan' => '2026-05-25']);
        $ba->panitia()->sync([$ketua->id => ['urutan' => 0]]);

        // Lanskap 1200x900 -> dibatasi lebar kotak (15,5 cm).
        $lanskap = UploadedFile::fake()->image('lapang.png', 1200, 900)->store('berita-acara', 'public');
        // Potret 600x1600 -> dibatasi tinggi kotak, lebar mengikuti rasio.
        $potret = UploadedFile::fake()->image('ttd.png', 600, 1600)->store('berita-acara', 'public');
        $ba->lampiran()->create(['path' => $lanskap, 'urutan' => 1]);
        $ba->lampiran()->create(['path' => $potret, 'urutan' => 2, 'keterangan' => 'Lembar tanda tangan']);

        $html = $this->actingAs($user)->get(route('berita-acara.print', $ba->id))->assertOk()->getContent();
        $teks = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));

        // Nama panitia muncul sekali saja (daftar pembuka) — blok tanda tangan hilang.
        $this->assertSame(1, substr_count($teks, 'SILVA R. UNO, S.H.'));

        // Tiap lampiran memulai halaman baru.
        $this->assertSame(2, substr_count($html, 'page-break-before:always'));

        // Ukuran dihitung dari dimensi asli, rasio dipertahankan.
        $this->assertStringContainsString('width:15.5cm;', $html);   // lanskap: mentok lebar
        $this->assertStringContainsString('height:11.63cm;', $html);
        $this->assertStringContainsString('width:8.55cm;', $html);   // potret: mentok tinggi
        $this->assertStringContainsString('height:22.8cm;', $html);  // 24cm - 1,2cm untuk keterangan

        $this->assertStringContainsString('Lembar tanda tangan', $teks);
    }
}
