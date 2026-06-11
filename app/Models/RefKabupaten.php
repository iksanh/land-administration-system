<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefKabupaten extends Model
{
    protected $table = 'ref_kabupaten';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'provinsi_id', 'nama'];

    public function provinsi()
    {
        return $this->belongsTo(RefProvinsi::class, 'provinsi_id');
    }

    public function kecamatans()
    {
        return $this->hasMany(RefKecamatan::class, 'kabupaten_id');
    }
}
