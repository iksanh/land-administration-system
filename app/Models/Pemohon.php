<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\JenisPemohonEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Pemohon extends Model
{
    use HasUuids;

    protected $table = 'pemohon';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'nik',
        'nama',
        'jenis_pemohon',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pekerjaan',
        'alamat_detail',
        'desa_id',
        'kuasa_nama',
        'kuasa_nik',
        'kuasa_pekerjaan',
        'kuasa_no_hp',
        'kuasa_alamat',
        'kuasa_hubungan',
        'kuasa_no_surat',
        'kuasa_tanggal_surat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'jenis_kelamin' => GenderEnum::class,
            'jenis_pemohon' => JenisPemohonEnum::class,
            'kuasa_tanggal_surat' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function desa()
    {
        return $this->belongsTo(RefDesa::class, 'desa_id');
    }
}
