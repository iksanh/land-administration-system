<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefDesa extends Model
{
    protected $table = 'ref_desa';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'kecamatan_id', 'nama', 'nama_kepala_desa'];

    public function kecamatan()
    {
        return $this->belongsTo(RefKecamatan::class, 'kecamatan_id');
    }
}
