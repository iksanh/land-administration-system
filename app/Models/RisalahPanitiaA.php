<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Risalah Panitia Pemeriksaan Tanah "A" (1:1 per permohonan). Data teknis
 * (pemohon, tanah, riwayat penguasaan) diambil dari relasi permohonan; tabel
 * ini menyimpan field khusus risalah. `data_pendukung` & `dasar_hukum` adalah
 * daftar string terurut (cast 'array').
 */
class RisalahPanitiaA extends Model
{
    use HasUuids;

    protected $table = 'risalah_panitia_a';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'permohonan_id',
        'nomor_risalah',
        'tgl_risalah',
        'jenis_hak',
        'jangka_waktu',
        'nomor_sk_panitia',
        'tgl_sk_panitia',
        'rtrw_kawasan',
        'perda_rtrw',
        'tgl_bap',
        'data_pendukung',
        'dasar_hukum',
        'kesimpulan_tambahan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_risalah' => 'date',
            'tgl_sk_panitia' => 'date',
            'tgl_bap' => 'date',
            'data_pendukung' => 'array',
            'dasar_hukum' => 'array',
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
        return $this->belongsToMany(PanitiaPemeriksa::class, 'risalah_panitia', 'risalah_id', 'panitia_id')
            ->withPivot('urutan', 'pendapat')
            ->orderBy('risalah_panitia.urutan');
    }
}
