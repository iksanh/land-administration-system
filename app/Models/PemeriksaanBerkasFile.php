<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * File lampiran hasil unggah untuk pemeriksaan berkas (1 berkas : banyak file).
 * Diikat ke (permohonan_id, berkas_item_id) agar hidup independen dari baris
 * pemeriksaan_berkas (yang bisa dihapus saat status kembali PENDING).
 * Tabel hanya punya created_at (tanpa updated_at).
 */
class PemeriksaanBerkasFile extends Model
{
    use HasUuids;

    protected $table = 'pemeriksaan_berkas_file';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'permohonan_id',
        'berkas_item_id',
        'file_path',
        'nama_asli',
        'ukuran',
        'mime',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
            'created_at' => 'datetime',
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

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
