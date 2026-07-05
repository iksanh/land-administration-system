<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Memeriksa typo / ejaan bahasa Indonesia pada teks riwayat tanah lewat
 * Gemini API (gemini-2.5-flash). Controller cukup memanggil check() dan
 * menerima array saran; seluruh logika HTTP, prompt, dan cache ada di sini.
 */
class GeminiTypoCheckerService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /** Cache selama 24 jam — teks yang sama tidak dikirim ulang ke Gemini. */
    private const CACHE_TTL = 86400;

    private const SYSTEM_INSTRUCTION = <<<'TXT'
Anda adalah pemeriksa ejaan bahasa Indonesia untuk dokumen riwayat tanah.
Tugas Anda: temukan kata yang salah ketik (typo) atau salah ejaan pada teks yang diberikan.

Aturan:
- JANGAN mengubah makna kalimat. Hanya perbaiki ejaan/salah ketik.
- Istilah resmi pertanahan (ATR/BPN) berikut BUKAN typo dan TIDAK BOLEH diubah,
  apa pun ejaan atau kapitalisasinya:
  sertipikat, SHM, HGB, HGU, HP, HM, girik, letter C, roya, PTSL, NIB, balik nama,
  turun waris, akta jual beli, PPAT, warkah, buku tanah.
- PENTING: kata "sertipikat" adalah ejaan resmi ATR/BPN. JANGAN PERNAH menyarankan
  penggantian "sertipikat" menjadi "sertifikat". Perlakukan "sertipikat" sebagai benar.
- Abaikan nama orang, nama tempat, dan angka.
- Hanya laporkan kata yang benar-benar salah. Jika tidak ada typo, kembalikan array kosong.
- Untuk setiap temuan: "original" = kata/frasa asli, "suggestion" = perbaikannya,
  "reason" = alasan singkat dalam bahasa Indonesia.
TXT;

    /**
     * @return array<int, array{original: string, suggestion: string, reason: string}>
     */
    public function check(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        // Sertakan hash system instruction pada key agar perubahan prompt
        // otomatis membatalkan cache lama (mencegah saran usang, mis. sertipikat).
        $cacheKey = 'gemini_typo_'.hash('sha256', self::SYSTEM_INSTRUCTION."\0".$text);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => $this->requestGemini($text),
        );
    }

    /**
     * @return array<int, array{original: string, suggestion: string, reason: string}>
     */
    private function requestGemini(string $text): array
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY belum dikonfigurasi.');
        }

        $endpoint = sprintf(self::ENDPOINT, config('services.gemini.model'));

        $response = Http::timeout(30)
            ->withQueryParameters(['key' => $apiKey])
            ->acceptJson()
            ->post($endpoint, [
                'system_instruction' => [
                    'parts' => [['text' => self::SYSTEM_INSTRUCTION]],
                ],
                'contents' => [
                    ['parts' => [['text' => $text]]],
                ],
                'generationConfig' => [
                    'temperature' => 0,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'ARRAY',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'original' => ['type' => 'STRING'],
                                'suggestion' => ['type' => 'STRING'],
                                'reason' => ['type' => 'STRING'],
                            ],
                            'required' => ['original', 'suggestion', 'reason'],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal menghubungi Gemini API (HTTP '.$response->status().').'
            );
        }

        $raw = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($raw)) {
            throw new RuntimeException('Respons Gemini API tidak valid.');
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Respons Gemini API bukan JSON yang valid.');
        }

        return $this->normalize($decoded);
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return array<int, array{original: string, suggestion: string, reason: string}>
     */
    private function normalize(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $result[] = [
                'original' => (string) ($row['original'] ?? ''),
                'suggestion' => (string) ($row['suggestion'] ?? ''),
                'reason' => (string) ($row['reason'] ?? ''),
            ];
        }

        return $result;
    }
}
