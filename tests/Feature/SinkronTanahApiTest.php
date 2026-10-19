<?php

namespace Tests\Feature;

use App\Models\MstLayanan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RiwayatPenguasaan;
use App\Models\Tanah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SinkronTanahApiTest extends TestCase
{
    use RefreshDatabase;

    private const KUNCI = 'kunci-uji-sinkron';

    protected function setUp(): void
    {
        parent::setUp();
        config(['sinkron.api_key' => self::KUNCI]);

        DB::table('ref_provinsi')->insert(['id' => '75', 'nama' => 'GORONTALO']);
        DB::table('ref_kabupaten')->insert(['id' => '7503', 'provinsi_id' => '75', 'nama' => 'BONE BOLANGO']);
        DB::table('ref_kecamatan')->insert(['id' => '750302', 'kabupaten_id' => '7503', 'nama' => 'Kabila']);
        DB::table('ref_desa')->insert(['id' => '7503021010', 'kecamatan_id' => '750302', 'nama' => 'Tumbihe', 'jenis' => 'KELURAHAN']);
    }

    private function panggil(string $nib, ?string $kunci = self::KUNCI)
    {
        $h = $kunci === null ? [] : ['Authorization' => 'Bearer '.$kunci];

        return $this->getJson('/api/sinkron/tanah?nib='.urlencode($nib), $h);
    }

    private function buatData(string $nib = '00571'): Tanah
    {
        $pemohon = Pemohon::create([
            'nik' => '7503135301850001', 'nama' => 'MEGAWATI WANTOGIA', 'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Gorontalo', 'tanggal_lahir' => '1986-01-13', 'desa_id' => '7503021010',
            'jenis_pemohon' => 'dikuasakan', 'kuasa_nama' => 'Saprina', 'kuasa_nik' => '7505031605910001',
        ]);
        $tanah = Tanah::create([
            'pemohon_id' => $pemohon->id, 'desa_id' => '7503021010', 'nib' => $nib,
            'luas' => 349, 'luas_surat' => 342, 'nomor_pbt' => '36/2025', 'tanggal_pbt' => '2025-08-25',
            'rencana_penggunaan_rtrw' => 'Non Pertanian', 'kesesuaian_penggunaan_tanah' => 'Sesuai',
        ]);
        $layanan = MstLayanan::create(['kode' => 'HM Perorangan', 'nama' => 'HAK MILIK PERORANGAN']);
        $p = Permohonan::create([
            'nomor_registrasi' => 'REG-001', 'pemohon_id' => $pemohon->id, 'tanah_id' => $tanah->id,
            'layanan_id' => $layanan->id, 'tgl_pendaftaran' => '2026-06-10',
        ]);
        RiwayatPenguasaan::create(['permohonan_id' => $p->id, 'poin' => ['Bahwa satu;', '  ', 'Bahwa dua;']]);

        return $tanah;
    }

    public function test_tanpa_kunci_ditolak(): void
    {
        $this->panggil('00571', null)->assertStatus(401);
        $this->panggil('00571', 'salah')->assertStatus(401);
    }

    public function test_kunci_belum_diatur_503(): void
    {
        config(['sinkron.api_key' => '']);
        $this->panggil('00571')->assertStatus(503);
    }

    public function test_nib_wajib_dan_minimal_tiga_angka(): void
    {
        $this->getJson('/api/sinkron/tanah', ['Authorization' => 'Bearer '.self::KUNCI])->assertStatus(422);
        $this->panggil('a.b')->assertStatus(422);
    }

    public function test_cocok_persis_mengembalikan_data_lengkap(): void
    {
        $this->buatData();

        $r = $this->panggil('00571')->assertOk();
        $r->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cocok', 'persis')
            ->assertJsonPath('data.0.tanah.nomor_pbt', '36/2025')
            ->assertJsonPath('data.0.tanah.luas', 349)
            ->assertJsonPath('data.0.tanah.desa.kecamatan.id', '750302')
            ->assertJsonPath('data.0.tanah.desa.jenis', 'KELURAHAN')
            ->assertJsonPath('data.0.pemohon.nik', '7503135301850001')
            ->assertJsonPath('data.0.pemohon.jenis_kelamin', 'P')
            ->assertJsonPath('data.0.pemohon.kuasa.nama', 'Saprina')
            ->assertJsonPath('data.0.permohonan.layanan.kode', 'HM Perorangan')
            ->assertJsonPath('data.0.riwayat_penguasaan', ['Bahwa satu;', 'Bahwa dua;']);
    }

    public function test_nib_lengkap_cocok_dengan_nomor_urut_pendek(): void
    {
        $this->buatData('00571');

        $this->panggil('30.05.02.10.00571')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cocok', 'sebagian');
    }

    public function test_nomor_pendek_kurang_dari_lima_angka_tidak_cocok_akhiran(): void
    {
        $this->buatData('123');

        $this->panggil('30.05.02.10.00123')->assertOk()->assertJsonCount(0, 'data');
        $this->panggil('123')->assertOk()->assertJsonCount(1, 'data');
    }
}
