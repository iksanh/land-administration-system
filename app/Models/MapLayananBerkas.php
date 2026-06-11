<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Junction table with a COMPOSITE primary key (layanan_id, berkas_item_id).
 * Eloquent has no first-class composite-key support, so single-key helpers
 * like find()/save-by-id do not work — look rows up with explicit where()s,
 * e.g. MapLayananBerkas::where('layanan_id', $a)->where('berkas_item_id', $b).
 */
class MapLayananBerkas extends Model
{
    protected $table = 'map_layanan_berkas';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['layanan_id', 'berkas_item_id', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function layanan()
    {
        return $this->belongsTo(MstLayanan::class, 'layanan_id');
    }

    public function berkasItem()
    {
        return $this->belongsTo(MstBerkasItem::class, 'berkas_item_id');
    }
}
