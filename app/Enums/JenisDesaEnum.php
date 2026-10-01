<?php

namespace App\Enums;

/**
 * Jenis wilayah setingkat desa. Menentukan sebutan yang tercetak pada dokumen
 * ("Desa Poduwoma" vs "Kelurahan Tumbihe") sekaligus sebutan pemimpinnya
 * ("Kepala Desa" vs "Lurah").
 */
enum JenisDesaEnum: string
{
    case DESA = 'DESA';
    case KELURAHAN = 'KELURAHAN';

    /** Sebutan wilayah sebagaimana tercetak: "Desa" / "Kelurahan". */
    public function sebutan(): string
    {
        return match ($this) {
            self::DESA => 'Desa',
            self::KELURAHAN => 'Kelurahan',
        };
    }

    /** Sebutan jabatan pemimpin wilayah: "Kepala Desa" / "Lurah". */
    public function sebutanKepala(): string
    {
        return match ($this) {
            self::DESA => 'Kepala Desa',
            self::KELURAHAN => 'Lurah',
        };
    }

    public function label(): string
    {
        return $this->sebutan();
    }
}
