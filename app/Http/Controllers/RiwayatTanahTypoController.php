<?php

namespace App\Http\Controllers;

use App\Services\GeminiTypoCheckerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * POST /api/riwayat-tanah/check-typo
 *
 * Memeriksa typo/ejaan bahasa Indonesia pada teks riwayat tanah via
 * GeminiTypoCheckerService. Controller sengaja tipis: validasi + delegasi.
 */
class RiwayatTanahTypoController extends Controller
{
    public function __invoke(Request $request, GeminiTypoCheckerService $checker): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $suggestions = $checker->check($validated['text']);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Gagal memeriksa typo: '.$e->getMessage(),
            ], 502);
        }

        return response()->json([
            'data' => $suggestions,
        ]);
    }
}
