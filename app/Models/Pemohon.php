<?php

namespace App\Models;

use App\Enums\GenderEnum;
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
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pekerjaan',
        'alamat_detail',
        'desa_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'jenis_kelamin' => GenderEnum::class,
            'created_at' => 'datetime',
        ];
    }

    public function desa()
    {
        return $this->belongsTo(RefDesa::class, 'desa_id');
    }
}
