<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefKecamatan extends Model
{
    protected $table = 'ref_kecamatan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'kabupaten_id', 'nama'];

    public function kabupaten()
    {
        return $this->belongsTo(RefKabupaten::class, 'kabupaten_id');
    }

    public function desas()
    {
        return $this->hasMany(RefDesa::class, 'kecamatan_id');
    }
}
