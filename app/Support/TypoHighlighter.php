<?php

namespace App\Support;

/**
 * Logika murni untuk menandai (highlight) & menerapkan koreksi typo pada sebuah
 * teks berdasar temuan GeminiTypoCheckerService. Dipakai bersama oleh halaman
 * Cek Typo (App\Livewire\RiwayatTanah\CheckTypo) dan form Berita Acara
 * (riwayat penguasaan) agar aturan penandaan tidak terduplikasi.
 *
 * Setiap "finding" berbentuk:
 *   ['original' => string, 'suggestion' => string, 'reason' => string, 'accepted' => bool]
 */
class TypoHighlighter
{
    /**
     * Pecah teks menjadi segmen: potongan teks biasa atau typo (dengan index
     * findings-nya). Cocok berdasar substring persis; frasa terpanjang menang
     * agar tidak tumpang tindih dengan kata di dalamnya.
     *
     * @param  array<int, array{original: string, suggestion: string, reason: string, accepted: bool}>  $findings
     * @return array<int, array<string, mixed>>
     */
    public static function segments(string $text, array $findings): array
    {
        $len = strlen($text);
        $segments = [];
        $buffer = '';
        $i = 0;

        while ($i < $len) {
            $matchIdx = null;
            $matchLen = 0;

            foreach ($findings as $idx => $f) {
                $ol = strlen($f['original']);
                if ($ol > 0 && $ol > $matchLen && substr($text, $i, $ol) === $f['original']) {
                    $matchIdx = $idx;
                    $matchLen = $ol;
                }
            }

            if ($matchIdx !== null) {
                if ($buffer !== '') {
                    $segments[] = ['type' => 'text', 'content' => $buffer];
                    $buffer = '';
                }
                $segments[] = [
                    'type' => 'typo',
                    'index' => $matchIdx,
                    'original' => $findings[$matchIdx]['original'],
                    'suggestion' => $findings[$matchIdx]['suggestion'],
                    'reason' => $findings[$matchIdx]['reason'],
                    'accepted' => $findings[$matchIdx]['accepted'],
                ];
                $i += $matchLen;
            } else {
                $buffer .= $text[$i];
                $i++;
            }
        }

        if ($buffer !== '') {
            $segments[] = ['type' => 'text', 'content' => $buffer];
        }

        return $segments;
    }

    /**
     * Teks hasil koreksi: temuan yang accepted diganti suggestion, sisanya utuh.
     *
     * @param  array<int, array{original: string, suggestion: string, reason: string, accepted: bool}>  $findings
     */
    public static function apply(string $text, array $findings): string
    {
        $out = '';
        foreach (self::segments($text, $findings) as $seg) {
            if ($seg['type'] === 'typo') {
                $out .= $seg['accepted'] ? $seg['suggestion'] : $seg['original'];
            } else {
                $out .= $seg['content'];
            }
        }

        return $out;
    }
}
