<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SK pembentukan Panitia Pemeriksa Tanah "A". Memayungi satu susunan anggota;
 * hanya satu SK yang aktif pada satu waktu (dijaga oleh ManagePanitia::activateSk).
 * SK aktif dipakai otomatis oleh Berita Acara & Risalah — lihat PanitiaResolver.
 */
class SkPanitia extends Model
{
    use HasUuids;

    protected $table = 'sk_panitia';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'nomor',
        'tanggal',
        'tentang',
        'berlaku_mulai',
        'berlaku_sampai',
        'is_active',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function anggota()
    {
        return $this->hasMany(PanitiaPemeriksa::class, 'sk_panitia_id')
            ->orderBy('urutan')->orderBy('nama');
    }

    /** Anggota yang berstatus aktif saja — yang dipakai sebagai penandatangan. */
    public function anggotaAktif()
    {
        return $this->anggota()->where('is_active', true);
    }

    /** Label ringkas untuk dropdown & badge: "134/SK-75.03/V/2025 — 12 Mei 2025". */
    public function label(): string
    {
        return $this->tanggal
            ? $this->nomor.' — '.$this->tanggal->translatedFormat('d F Y')
            : $this->nomor;
    }
}
