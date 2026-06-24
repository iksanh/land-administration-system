<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Mengeja bilangan dan tanggal dalam Bahasa Indonesia untuk kalimat pembuka
 * Berita Acara, mis. "Senin, tanggal tiga belas bulan Januari tahun dua ribu
 * dua puluh lima".
 */
class Terbilang
{
    public static function make(int $n): string
    {
        $n = abs($n);
        $angka = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($n < 12) {
            return $angka[$n];
        }
        if ($n < 20) {
            return self::make($n - 10).' belas';
        }
        if ($n < 100) {
            return trim(self::make(intdiv($n, 10)).' puluh '.self::make($n % 10));
        }
        if ($n < 200) {
            return trim('seratus '.self::make($n % 100));
        }
        if ($n < 1000) {
            return trim(self::make(intdiv($n, 100)).' ratus '.self::make($n % 100));
        }
        if ($n < 2000) {
            return trim('seribu '.self::make($n % 1000));
        }
        if ($n < 1_000_000) {
            return trim(self::make(intdiv($n, 1000)).' ribu '.self::make($n % 1000));
        }
        if ($n < 1_000_000_000) {
            return trim(self::make(intdiv($n, 1_000_000)).' juta '.self::make($n % 1_000_000));
        }

        return (string) $n;
    }

    /**
     * "Senin, tanggal tiga belas bulan Januari tahun dua ribu dua puluh lima".
     */
    public static function tanggal(CarbonInterface $date): string
    {
        $hari = $date->locale('id')->translatedFormat('l');
        $bulan = $date->locale('id')->translatedFormat('F');

        return sprintf(
            '%s, tanggal %s bulan %s tahun %s',
            $hari,
            self::make((int) $date->format('j')),
            $bulan,
            self::make((int) $date->format('Y')),
        );
    }
}
