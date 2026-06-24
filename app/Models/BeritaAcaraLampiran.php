<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BeritaAcaraLampiran extends Model
{
    use HasUuids;

    protected $table = 'berita_acara_lampiran';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'berita_acara_id',
        'path',
        'keterangan',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcaraPemeriksaan::class, 'berita_acara_id');
    }
}
