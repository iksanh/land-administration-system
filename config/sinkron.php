<?php

/*
 * API sinkron untuk aplikasi BA Risalah (SIPANTAS).
 *
 * SIPANTAS menarik data pemohon + tanah dari sini berdasarkan NIB supaya
 * petugas tidak mengetik ulang. Panggilannya server-ke-server, jadi dijaga
 * kunci API, bukan sesi login.
 *
 * Kunci utama dikelola admin di menu Pengaturan → Kunci API (/kunci-api,
 * tabel `api_kunci`). `SINKRON_API_KEY` hanya cadangan lama — boleh kosong.
 * Tidak ada kunci aktif sama sekali = API mati (503).
 */
return [
    'api_key' => env('SINKRON_API_KEY'),
];
