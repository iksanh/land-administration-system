<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MstLayanan extends Model
{
    use HasUuids;

    protected $table = 'mst_layanan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['kode', 'nama', 'deskripsi', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function berkasItems()
    {
        return $this->hasMany(MapLayananBerkas::class, 'layanan_id');
    }
}
