<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat penguasaan tanah untuk sebuah permohonan (1:1). `poin` adalah daftar
 * string terurut, dipakai bersama oleh Berita Acara, Risalah, dan SK lewat
 * trait App\Livewire\Concerns\WithRiwayatPenguasaan.
 */
class RiwayatPenguasaan extends Model
{
    use HasUuids;

    protected $table = 'riwayat_penguasaan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'permohonan_id',
        'poin',
    ];

    protected function casts(): array
    {
        return [
            'poin' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }
}
