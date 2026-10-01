<?php

namespace App\Models;

use App\Enums\JenisDesaEnum;
use Illuminate\Database\Eloquent\Model;

class RefDesa extends Model
{
    protected $table = 'ref_desa';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'kecamatan_id', 'nama', 'jenis', 'nama_kepala_desa'];

    protected function casts(): array
    {
        return [
            'jenis' => JenisDesaEnum::class,
        ];
    }

    /** "Desa" atau "Kelurahan" — sebutan yang tercetak pada dokumen. */
    public function sebutan(): string
    {
        return ($this->jenis ?? JenisDesaEnum::DESA)->sebutan();
    }

    /** "Kepala Desa" atau "Lurah". */
    public function sebutanKepala(): string
    {
        return ($this->jenis ?? JenisDesaEnum::DESA)->sebutanKepala();
    }

    /** "Desa PODUWOMA" / "Kelurahan TUMBIHE". */
    public function namaLengkap(): string
    {
        return $this->sebutan().' '.$this->nama;
    }

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
