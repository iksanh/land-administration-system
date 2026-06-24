<?php

namespace App\Enums;

enum PeranPanitiaEnum: string
{
    case KETUA = 'KETUA';
    case ANGGOTA = 'ANGGOTA';
    case SEKRETARIS = 'SEKRETARIS';
    case KEPALA_DESA = 'KEPALA_DESA';

    /** Label singkat untuk UI manajemen. */
    public function label(): string
    {
        return match ($this) {
            self::KETUA => 'Ketua',
            self::ANGGOTA => 'Anggota',
            self::SEKRETARIS => 'Sekretaris',
            self::KEPALA_DESA => 'Kepala Desa',
        };
    }

    /** Frasa peran lengkap sebagaimana tercetak pada Berita Acara. */
    public function frasa(): string
    {
        return match ($this) {
            self::KETUA => 'sebagai Ketua merangkap Anggota',
            self::ANGGOTA => 'sebagai Anggota',
            self::SEKRETARIS => 'sebagai Sekretaris merangkap Anggota',
            self::KEPALA_DESA => 'sebagai Anggota',
        };
    }
}
