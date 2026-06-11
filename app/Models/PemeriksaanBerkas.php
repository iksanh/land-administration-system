<?php

namespace App\Models;

use App\Enums\PemeriksaanStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanBerkas extends Model
{
    use HasUuids;

    protected $table = 'pemeriksaan_berkas';

    public $incrementing = false;

    protected $keyType = 'string';

    // Source table has updated_at but no created_at.
    public $timestamps = true;

    const CREATED_AT = null;

    protected $fillable = [
        'permohonan_id',
        'berkas_item_id',
        'petugas_id',
        'status',
        'catatan',
        'file_path',
    ];

    protected $attributes = ['status' => PemeriksaanStatusEnum::PENDING->value];

    protected function casts(): array
    {
        return [
            'status' => PemeriksaanStatusEnum::class,
            // JSON column: array of {id, teks, is_custom}
            'catatan' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function berkasItem()
    {
        return $this->belongsTo(MstBerkasItem::class, 'berkas_item_id');
    }
}
