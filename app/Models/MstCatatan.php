<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MstCatatan extends Model
{
    use HasUuids;

    protected $table = 'mst_catatan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = ['teks', 'berkas_item_id', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function berkasItem()
    {
        return $this->belongsTo(MstBerkasItem::class, 'berkas_item_id');
    }
}
