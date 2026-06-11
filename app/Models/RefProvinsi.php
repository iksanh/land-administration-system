<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefProvinsi extends Model
{
    protected $table = 'ref_provinsi';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'nama'];

    public function kabupatens()
    {
        return $this->hasMany(RefKabupaten::class, 'provinsi_id');
    }
}
