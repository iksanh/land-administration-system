<?php

namespace Tests\Feature;

use App\Enums\GenderEnum;
use App\Enums\JenisPemohonEnum;
use App\Livewire\Pemohon\ManagePemohon;
use App\Models\Pemohon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagePemohonTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pemohon_with_gender_enum(): void
    {
        Livewire::test(ManagePemohon::class)
            ->set('nik', '7503010101010001')
            ->set('nama', 'Budi Santoso')
            ->set('jenis_kelamin', 'L')
            ->set('tanggal_lahir', '1990-05-17')
            ->call('save')
            ->assertHasNoErrors();

        $pemohon = Pemohon::where('nik', '7503010101010001')->first();
        $this->assertNotNull($pemohon);
        $this->assertSame(GenderEnum::L, $pemohon->jenis_kelamin);
        $this->assertSame('1990-05-17', $pemohon->tanggal_lahir->format('Y-m-d'));
    }

    public function test_nik_must_be_unique(): void
    {
        Pemohon::create(['nik' => '7503010101010001', 'nama' => 'Ada']);

        Livewire::test(ManagePemohon::class)
            ->set('nik', '7503010101010001')->set('nama', 'Lain')
            ->call('save')
            ->assertHasErrors(['nik' => 'unique']);
    }

    public function test_nik_and_nama_required(): void
    {
        Livewire::test(ManagePemohon::class)
            ->set('nik', '')->set('nama', '')
            ->call('save')
            ->assertHasErrors(['nik', 'nama']);
    }

    public function test_invalid_gender_is_rejected(): void
    {
        Livewire::test(ManagePemohon::class)
            ->set('nik', '7503010101010002')->set('nama', 'X')->set('jenis_kelamin', 'Z')
            ->call('save')
            ->assertHasErrors(['jenis_kelamin']);
    }

    public function test_dikuasakan_requires_penerima_kuasa_fields(): void
    {
        Livewire::test(ManagePemohon::class)
            ->set('nik', '7503010101010010')->set('nama', 'Pemberi Kuasa')
            ->set('jenis_pemohon', 'dikuasakan')
            ->call('save')
            ->assertHasErrors(['kuasa_nama', 'kuasa_nik']);
    }

    public function test_can_create_pemohon_dikuasakan(): void
    {
        Livewire::test(ManagePemohon::class)
            ->set('nik', '7503010101010011')->set('nama', 'Pemberi Kuasa')
            ->set('jenis_pemohon', 'dikuasakan')
            ->set('kuasa_nama', 'Penerima Kuasa')
            ->set('kuasa_nik', '7503010101010012')
            ->set('kuasa_hubungan', 'Anak')
            ->call('save')
            ->assertHasNoErrors();

        $p = Pemohon::where('nik', '7503010101010011')->first();
        $this->assertSame(JenisPemohonEnum::DIKUASAKAN, $p->jenis_pemohon);
        $this->assertSame('Penerima Kuasa', $p->kuasa_nama);
        $this->assertSame('Anak', $p->kuasa_hubungan);
    }

    public function test_switching_back_to_diri_sendiri_clears_kuasa_data(): void
    {
        $p = Pemohon::create([
            'nik' => '7503010101010013', 'nama' => 'X',
            'jenis_pemohon' => 'dikuasakan', 'kuasa_nama' => 'Lama', 'kuasa_nik' => '9',
        ]);

        Livewire::test(ManagePemohon::class)
            ->call('edit', $p->id)
            ->set('jenis_pemohon', 'diri_sendiri')
            ->call('save')
            ->assertHasNoErrors();

        $p->refresh();
        $this->assertNull($p->kuasa_nama);
        $this->assertNull($p->kuasa_nik);
    }

    public function test_can_edit_and_delete_pemohon(): void
    {
        $p = Pemohon::create(['nik' => '7503010101010003', 'nama' => 'Lama']);

        Livewire::test(ManagePemohon::class)
            ->call('edit', $p->id)
            ->assertSet('nik', '7503010101010003')
            ->set('nama', 'Baru')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Baru', $p->refresh()->nama);

        Livewire::test(ManagePemohon::class)->call('delete', $p->id);
        $this->assertDatabaseMissing('pemohon', ['id' => $p->id]);
    }
}
