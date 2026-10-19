<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga API sinkron: kunci bersama lewat `Authorization: Bearer <kunci>`
 * (atau `X-Api-Key`). Dibandingkan dengan hash_equals supaya waktu
 * pembandingannya tidak membocorkan isi kunci.
 */
class CekKunciSinkron
{
    public function handle(Request $request, Closure $next): Response
    {
        $harap = (string) config('sinkron.api_key');
        if ($harap === '') {
            return response()->json(['message' => 'API sinkron belum dikonfigurasi (SINKRON_API_KEY kosong).'], 503);
        }

        $kirim = (string) ($request->bearerToken() ?? $request->header('X-Api-Key', ''));
        if ($kirim === '' || ! hash_equals($harap, $kirim)) {
            return response()->json(['message' => 'Kunci API tidak sah.'], 401);
        }

        return $next($request);
    }
}
