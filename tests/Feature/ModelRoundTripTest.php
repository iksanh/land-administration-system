<?php

namespace Tests\Feature;

use App\Enums\GenderEnum;
use App\Enums\PemeriksaanStatusEnum;
use App\Enums\PermohonanStatusEnum;
use App\Models\MapLayananBerkas;
use App\Models\MstBerkasItem;
use App\Models\MstCatatan;
use App\Models\MstLayanan;
use App\Models\Pemohon;
use App\Models\PemeriksaanBerkas;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Models\RefDesa;
use App\Models\RefKabupaten;
use App\Models\RefKecamatan;
use App\Models\RefProvinsi;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelRoundTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_wilayah_chain_persists_with_string_keys_and_relationships(): void
    {
        $prov = RefProvinsi::create(['id' => '11', 'nama' => 'ACEH']);
        $kab = RefKabupaten::create(['id' => '1101', 'provinsi_id' => '11', 'nama' => 'KAB A']);
        $kec = RefKecamatan::create(['id' => '110101', 'kabupaten_id' => '1101', 'nama' => 'KEC A']);
        $desa = RefDesa::create(['id' => '1101012001', 'kecamatan_id' => '110101', 'nama' => 'DESA A', 'nama_kepala_desa' => 'Pak Kades']);

        $this->assertSame('11', $prov->fresh()->id);
        $this->assertSame('ACEH', $kab->provinsi->nama);
        $this->assertSame('DESA A', $kec->desas->first()->nama);
        $this->assertSame('KEC A', $desa->kecamatan->nama);
    }

    public function test_user_uses_uuid_and_hidden_hashed_password(): void
    {
        $user = User::create([
            'name' => 'Petugas Satu',
            'email' => 'petugas@app.com',
            'hashed_password' => 'x', // raw; hashing is exercised in Phase 2
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $fresh = $user->fresh();
        $this->assertTrue(Str::isUuid($fresh->id));
        $this->assertIsBool($fresh->is_active);
        $this->assertTrue($fresh->is_active);
        $this->assertArrayNotHasKey('hashed_password', $fresh->toArray());
        $this->assertNotNull($fresh->created_at);
    }

    public function test_layanan_berkas_self_reference_mapping_and_catatan(): void
    {
        $layanan = MstLayanan::create(['kode' => 'SRT-001', 'nama' => 'Sertifikasi']);
        $parent = MstBerkasItem::create(['nama' => 'Surat Tanah']);
        $child = MstBerkasItem::create(['nama' => 'Sub Surat', 'parent_id' => $parent->id]);

        MapLayananBerkas::create([
            'layanan_id' => $layanan->id,
            'berkas_item_id' => $parent->id,
            'urutan' => 1,
        ]);
        $catatan = MstCatatan::create(['teks' => 'Periksa materai', 'berkas_item_id' => $parent->id]);

        $this->assertTrue(Str::isUuid($layanan->id));
        $this->assertTrue($layanan->fresh()->is_active); // default true
        $this->assertSame('Surat Tanah', $child->parent->nama);
        $this->assertTrue($parent->subBerkas->contains('id', $child->id));

        $map = MapLayananBerkas::where('layanan_id', $layanan->id)
            ->where('berkas_item_id', $parent->id)
            ->first();
        $this->assertSame(1, $map->urutan);
        $this->assertSame('Sertifikasi', $map->layanan->nama);
        $this->assertSame('Periksa materai', $parent->catatanList->first()->teks);
        $this->assertSame('Surat Tanah', $catatan->berkasItem->nama);
    }

    public function test_pemohon_casts_gender_enum_and_date(): void
    {
        $pemohon = Pemohon::create([
            'nik' => '1101010101010001',
            'nama' => 'Budi',
            'tanggal_lahir' => '1990-05-17',
            'jenis_kelamin' => GenderEnum::L,
        ]);

        $fresh = $pemohon->fresh();
        $this->assertInstanceOf(GenderEnum::class, $fresh->jenis_kelamin);
        $this->assertSame(GenderEnum::L, $fresh->jenis_kelamin);
        $this->assertSame('1990-05-17', $fresh->tanggal_lahir->format('Y-m-d'));
    }

    public function test_tanah_casts_decimal_with_two_places(): void
    {
        $pemohon = Pemohon::create(['nik' => '1101010101010002', 'nama' => 'Sri']);
        $tanah = Tanah::create([
            'pemohon_id' => $pemohon->id,
            'luas' => 123.4,
            'luas_surat' => 200,
            'penggunaan_tanah' => 'Pertanian',
        ]);

        $fresh = $tanah->fresh();
        $this->assertSame('123.40', $fresh->luas);
        $this->assertSame('Sri', $fresh->pemohon->nama);
    }

    public function test_permohonan_defaults_to_draft_enum_status(): void
    {
        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-0001']);

        $fresh = $permohonan->fresh();
        $this->assertInstanceOf(PermohonanStatusEnum::class, $fresh->status);
        $this->assertSame(PermohonanStatusEnum::DRAFT, $fresh->status);
        $this->assertNotNull($fresh->created_at);
        $this->assertNotNull($fresh->updated_at);
    }

    public function test_pemeriksaan_berkas_casts_jsonb_catatan_and_status(): void
    {
        $berkas = MstBerkasItem::create(['nama' => 'KTP']);
        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-0002']);

        $catatan = [
            ['id' => 'abc', 'teks' => 'KTP tidak terbaca', 'is_custom' => false],
            ['id' => null, 'teks' => 'Catatan tambahan', 'is_custom' => true],
        ];
        $pemeriksaan = PemeriksaanBerkas::create([
            'permohonan_id' => $permohonan->id,
            'berkas_item_id' => $berkas->id,
            'status' => PemeriksaanStatusEnum::REVISI,
            'catatan' => $catatan,
        ]);

        $fresh = $pemeriksaan->fresh();
        $this->assertSame(PemeriksaanStatusEnum::REVISI, $fresh->status);
        $this->assertIsArray($fresh->catatan);
        $this->assertSame('KTP tidak terbaca', $fresh->catatan[0]['teks']);
        $this->assertTrue($fresh->catatan[1]['is_custom']);
    }

    public function test_audit_log_uses_bigint_pk_and_status_enums(): void
    {
        $permohonan = Permohonan::create(['nomor_registrasi' => 'REG-0003']);
        $log = PermohonanAuditLog::create([
            'permohonan_id' => $permohonan->id,
            'status_sebelumnya' => PermohonanStatusEnum::DRAFT,
            'status_baru' => PermohonanStatusEnum::SUBMITTED,
            'catatan' => 'Diajukan pemohon',
        ]);

        $fresh = $log->fresh();
        $this->assertIsInt($fresh->id);
        $this->assertSame(PermohonanStatusEnum::DRAFT, $fresh->status_sebelumnya);
        $this->assertSame(PermohonanStatusEnum::SUBMITTED, $fresh->status_baru);
        $this->assertSame('REG-0003', $fresh->permohonan->nomor_registrasi);
    }
}
