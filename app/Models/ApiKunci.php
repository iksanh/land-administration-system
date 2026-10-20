<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Kunci API sinkron yang dikelola admin (/kunci-api). Kunci asli tidak pernah
 * disimpan — hanya hash SHA-256-nya. Dicek oleh middleware CekKunciSinkron.
 */
class ApiKunci extends Model
{
    use HasUuids;

    protected $table = 'api_kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nama',
        'kunci_hash',
        'awalan',
        'is_active',
        'terakhir_dipakai_at',
        'dibuat_oleh',
    ];

    protected $hidden = ['kunci_hash'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'terakhir_dipakai_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public static function hash(string $kunci): string
    {
        return hash('sha256', $kunci);
    }

    /**
     * Buat kunci baru. Mengembalikan [model, kunci asli] — kunci asli hanya
     * tersedia di sini, jadi tampilkan sekali ke admin lalu buang.
     *
     * @return array{0: self, 1: string}
     */
    public static function terbitkan(string $nama, ?string $userId = null): array
    {
        $kunci = 'skn_'.Str::random(48);

        $model = static::create([
            'nama' => $nama,
            'kunci_hash' => static::hash($kunci),
            'awalan' => substr($kunci, 0, 10),
            'is_active' => true,
            'dibuat_oleh' => $userId,
        ]);

        return [$model, $kunci];
    }

    /** Kunci aktif yang cocok dengan kunci kiriman, atau null. */
    public static function cocokkan(string $kunci): ?self
    {
        if ($kunci === '') {
            return null;
        }

        return static::where('kunci_hash', static::hash($kunci))
            ->where('is_active', true)
            ->first();
    }
}
