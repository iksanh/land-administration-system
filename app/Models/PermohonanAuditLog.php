<?php

namespace App\Models;

use App\Enums\PermohonanStatusEnum;
use Illuminate\Database\Eloquent\Model;

class PermohonanAuditLog extends Model
{
    protected $table = 'permohonan_audit_log';

    // Source table uses an auto-increment BigInteger PK (not a UUID).
    protected $keyType = 'int';

    public $incrementing = true;

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'permohonan_id',
        'status_sebelumnya',
        'status_baru',
        'petugas_id',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status_sebelumnya' => PermohonanStatusEnum::class,
            'status_baru' => PermohonanStatusEnum::class,
            'created_at' => 'datetime',
        ];
    }

    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }
}
