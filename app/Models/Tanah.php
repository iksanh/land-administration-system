<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tanah extends Model
{
    use HasUuids;

    protected $table = 'tanah';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'pemohon_id',
        'desa_id',
        'luas',
        'luas_surat',
        'penggunaan_tanah',
        'nomor_pbt',
        'tanggal_pbt',
        'nib',
        'tgl_peta_analisis',
        'rencana_penggunaan_rtrw',
        'kesesuaian_penggunaan_tanah',
        'penggunaan_tanah_sk',
        'batas_utara',
        'batas_timur',
        'batas_selatan',
        'batas_barat',
    ];

    protected function casts(): array
    {
        return [
            'luas' => 'decimal:2',
            'luas_surat' => 'decimal:2',
            'tanggal_pbt' => 'date',
            'tgl_peta_analisis' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function pemohon()
    {
        return $this->belongsTo(Pemohon::class, 'pemohon_id');
    }

    public function desa()
    {
        return $this->belongsTo(RefDesa::class, 'desa_id');
    }
}
