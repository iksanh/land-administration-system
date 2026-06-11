<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUuids;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    // Source table has created_at but no updated_at.
    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'hashed_password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'hashed_password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The source schema stores the bcrypt hash in `hashed_password`,
     * not Laravel's default `password` column.
     */
    public function getAuthPassword(): string
    {
        return $this->hashed_password;
    }
}
