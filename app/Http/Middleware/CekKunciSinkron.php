<?php

namespace App\Http\Middleware;

use App\Models\ApiKunci;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga API sinkron: kunci lewat `Authorization: Bearer <kunci>`
 * (atau `X-Api-Key`).
 *
 * Sumber kunci utama adalah tabel `api_kunci` (dikelola admin di /kunci-api);
 * yang disimpan hanya hash SHA-256, jadi pencocokan dilakukan lewat hash.
 * `SINKRON_API_KEY` di .env masih diterima sebagai cadangan lama supaya
 * deploy yang sudah jalan tidak putus — kosongkan setelah pindah ke DB.
 */
class CekKunciSinkron
{
    public function handle(Request $request, Closure $next): Response
    {
        $kunciEnv = (string) config('sinkron.api_key');
        $adaKunciDb = ApiKunci::where('is_active', true)->exists();

        if (! $adaKunciDb && $kunciEnv === '') {
            return response()->json(['message' => 'API sinkron belum dikonfigurasi (belum ada kunci API aktif).'], 503);
        }

        $kirim = (string) ($request->bearerToken() ?? $request->header('X-Api-Key', ''));
        if ($kirim === '') {
            return response()->json(['message' => 'Kunci API tidak sah.'], 401);
        }

        if ($adaKunciDb && ($kunci = ApiKunci::cocokkan($kirim))) {
            // Cukup presisi per menit; hindari tulis DB di setiap panggilan.
            if (! $kunci->terakhir_dipakai_at || $kunci->terakhir_dipakai_at->lt(now()->subMinute())) {
                $kunci->forceFill(['terakhir_dipakai_at' => now()])->saveQuietly();
            }

            return $next($request);
        }

        if ($kunciEnv !== '' && hash_equals($kunciEnv, $kirim)) {
            return $next($request);
        }

        return response()->json(['message' => 'Kunci API tidak sah.'], 401);
    }
}
