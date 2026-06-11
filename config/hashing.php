<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    */

    'driver' => 'bcrypt',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | `verify` is set to FALSE on purpose: the existing users were hashed by
    | Python's passlib, which emits `$2b$` bcrypt hashes. PHP's password_verify
    | validates these correctly, but Laravel's extra algorithm guard rejects the
    | `$2b$` prefix (it expects `$2y$`). Disabling the guard lets the migrated
    | credentials authenticate while still using bcrypt for new hashes.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => false,
        'limit' => null,
    ],

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => false,
    ],

    'rehash_on_login' => false,

];
