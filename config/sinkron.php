<?php

/*
 * API sinkron untuk aplikasi BA Risalah (SIPANTAS).
 *
 * SIPANTAS menarik data pemohon + tanah dari sini berdasarkan NIB supaya
 * petugas tidak mengetik ulang. Panggilannya server-ke-server, jadi dijaga
 * satu kunci bersama, bukan sesi login. Kosong = API mati (503).
 *
 * Buat kunci:  php -r "echo bin2hex(random_bytes(32));"
 */
return [
    'api_key' => env('SINKRON_API_KEY'),
];
