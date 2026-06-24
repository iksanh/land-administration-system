<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BeritaAcaraPemeriksaan extends Model
{
    use HasUuids;

    protected $table = 'berita_acara_pemeriksaan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'permohonan_id',
        'nomor_ba',
        'tgl_pemeriksaan',
        'riwayat_penguasaan',
        'keadaan_tanah',
        'catatan_keberatan',
        'perda_rtrw',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pemeriksaan' => 'date',
            'riwayat_penguasaan' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function panitia()
    {
        return $this->belongsToMany(PanitiaPemeriksa::class, 'berita_acara_panitia', 'berita_acara_id', 'panitia_id')
            ->withPivot('urutan')
            ->orderBy('berita_acara_panitia.urutan');
    }

    public function lampiran()
    {
        return $this->hasMany(BeritaAcaraLampiran::class, 'berita_acara_id')->orderBy('urutan');
    }
}
