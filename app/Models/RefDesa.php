<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefDesa extends Model
{
    protected $table = 'ref_desa';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'kecamatan_id', 'nama', 'nama_kepala_desa'];

    public function kecamatan()
    {
        return $this->belongsTo(RefKecamatan::class, 'kecamatan_id');
    }

    /** Seluruh kepala desa (aktif & non-aktif), aktif dulu lalu urutan. */
    public function kepalaDesa()
    {
        return $this->hasMany(RefKepalaDesa::class, 'desa_id')
            ->orderByDesc('is_active')->orderBy('urutan')->orderBy('nama');
    }

    /** Hanya kepala desa aktif — ditarik sebagai penandatangan BA & Risalah. */
    public function kepalaDesaAktif()
    {
        return $this->hasMany(RefKepalaDesa::class, 'desa_id')
            ->where('is_active', true)->orderBy('urutan')->orderBy('nama');
    }
}
