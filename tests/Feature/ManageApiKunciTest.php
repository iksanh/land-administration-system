<?php

namespace Tests\Feature;

use App\Livewire\ApiKunci\ManageApiKunci;
use App\Models\ApiKunci;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManageApiKunciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sinkron.api_key' => '']);
    }

    private function user(array $roles, string $email): User
    {
        return User::create([
            'name' => 'U '.$email, 'email' => $email,
            'hashed_password' => Hash::make('x'), 'roles' => $roles, 'is_active' => true,
        ]);
    }

    private function panggil(?string $kunci)
    {
        $h = $kunci === null ? [] : ['Authorization' => 'Bearer '.$kunci];

        return $this->getJson('/api/sinkron/tanah?nib=00571', $h);
    }

    public function test_halaman_hanya_untuk_admin(): void
    {
        $this->actingAs($this->user(['petugas'], 'p@app.com'))->get('/kunci-api')->assertStatus(403);
        $this->actingAs($this->user(['admin'], 'a@app.com'))->get('/kunci-api')->assertOk();
    }

    public function test_aksi_livewire_ditolak_untuk_non_admin(): void
    {
        Livewire::actingAs($this->user(['petugas', 'koorsub'], 'p@app.com'))
            ->test(ManageApiKunci::class)
            ->assertForbidden();

        $this->assertSame(0, ApiKunci::count());
    }

    public function test_admin_membuat_kunci_hanya_hash_yang_disimpan(): void
    {
        $admin = $this->user(['admin'], 'a@app.com');

        $c = Livewire::actingAs($admin)->test(ManageApiKunci::class)
            ->set('nama', 'SIPANTAS Produksi')
            ->call('create')
            ->assertHasNoErrors();

        $kunci = $c->get('kunciBaru');
        $this->assertNotEmpty($kunci);

        $row = ApiKunci::sole();
        $this->assertSame('SIPANTAS Produksi', $row->nama);
        $this->assertSame(hash('sha256', $kunci), $row->kunci_hash);
        $this->assertNotSame($kunci, $row->kunci_hash);
        $this->assertSame($admin->id, $row->dibuat_oleh);

        $c->call('tutupKunciBaru')->assertSet('kunciBaru', null);
    }

    public function test_nama_wajib(): void
    {
        Livewire::actingAs($this->user(['admin'], 'a@app.com'))
            ->test(ManageApiKunci::class)
            ->set('nama', '')->call('create')
            ->assertHasErrors(['nama']);
    }

    public function test_api_menerima_kunci_db_dan_menolak_saat_nonaktif_atau_dihapus(): void
    {
        $this->panggil('apa-saja')->assertStatus(503);

        [$row, $kunci] = ApiKunci::terbitkan('SIPANTAS');

        $this->panggil(null)->assertStatus(401);
        $this->panggil('salah')->assertStatus(401);
        $this->panggil($kunci)->assertOk();
        $this->getJson('/api/sinkron/tanah?nib=00571', ['X-Api-Key' => $kunci])->assertOk();
        $this->assertNotNull($row->fresh()->terakhir_dipakai_at);

        $admin = $this->user(['admin'], 'a@app.com');
        Livewire::actingAs($admin)->test(ManageApiKunci::class)->call('toggleActive', $row->id);
        $this->assertFalse($row->fresh()->is_active);
        $this->panggil($kunci)->assertStatus(503);

        // Kunci lain aktif → kunci nonaktif tetap ditolak 401.
        ApiKunci::terbitkan('Lain');
        $this->panggil($kunci)->assertStatus(401);

        Livewire::actingAs($admin)->test(ManageApiKunci::class)->call('delete', $row->id);
        $this->assertNull($row->fresh());
    }

    public function test_kunci_env_tetap_diterima_sebagai_cadangan(): void
    {
        config(['sinkron.api_key' => 'kunci-env-lama']);
        ApiKunci::terbitkan('SIPANTAS');

        $this->panggil('kunci-env-lama')->assertOk();
    }
}
