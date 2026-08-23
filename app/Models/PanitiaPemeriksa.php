<?php

namespace App\Models;

use App\Enums\PeranPanitiaEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PanitiaPemeriksa extends Model
{
    use HasUuids;

    protected $table = 'panitia_pemeriksa';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'sk_panitia_id',
        'nama',
        'nip',
        'jabatan',
        'peran',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'peran' => PeranPanitiaEnum::class,
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function sk()
    {
        return $this->belongsTo(SkPanitia::class, 'sk_panitia_id');
    }
}
