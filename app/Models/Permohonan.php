<?php

namespace App\Models;

use App\Enums\PermohonanStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Permohonan extends Model
{
    use HasUuids;

    protected $table = 'permohonan';

    public $incrementing = false;

    protected $keyType = 'string';

    // Source table has both created_at and updated_at.
    public $timestamps = true;

    protected $fillable = [
        'nomor_registrasi',
        'pemohon_id',
        'tanah_id',
        'layanan_id',
        'status',
        'tgl_pendaftaran',
    ];

    protected $attributes = ['status' => PermohonanStatusEnum::DRAFT->value];

    protected function casts(): array
    {
        return [
            'status' => PermohonanStatusEnum::class,
            'tgl_pendaftaran' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function pemohon()
    {
        return $this->belongsTo(Pemohon::class, 'pemohon_id');
    }

    public function tanah()
    {
        return $this->belongsTo(Tanah::class, 'tanah_id');
    }

    public function layanan()
    {
        return $this->belongsTo(MstLayanan::class, 'layanan_id');
    }

    public function riwayatPenguasaan()
    {
        return $this->hasOne(RiwayatPenguasaan::class, 'permohonan_id');
    }
}
