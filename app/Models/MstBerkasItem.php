<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MstBerkasItem extends Model
{
    use HasUuids;

    protected $table = 'mst_berkas_item';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['nama', 'is_mandatory', 'catatan', 'parent_id'];

    protected $attributes = ['is_mandatory' => true];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(MstBerkasItem::class, 'parent_id');
    }

    public function subBerkas()
    {
        return $this->hasMany(MstBerkasItem::class, 'parent_id');
    }

    public function layananItems()
    {
        return $this->hasMany(MapLayananBerkas::class, 'berkas_item_id');
    }

    public function catatanList()
    {
        return $this->hasMany(MstCatatan::class, 'berkas_item_id');
    }
}
