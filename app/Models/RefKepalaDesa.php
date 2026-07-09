<?php

namespace App\Models;

use App\Enums\PeranPanitiaEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Kepala desa (banyak per desa). Yang `is_active` otomatis ditarik sebagai
 * penandatangan Panitia (peran KEPALA_DESA) pada Berita Acara & Risalah —
 * lihat App\Support\PanitiaResolver.
 */
class RefKepalaDesa extends Model
{
    use HasUuids;

    protected $table = 'ref_kepala_desa';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'desa_id',
        'nama',
        'nip',
        'jabatan',
        'periode',
        'is_active',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'urutan' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function desa()
    {
        return $this->belongsTo(RefDesa::class, 'desa_id');
    }

    /** Peran tetap sebagai Kepala Desa saat dirender bersama panitia. */
    public function peran(): PeranPanitiaEnum
    {
        return PeranPanitiaEnum::KEPALA_DESA;
    }
}
