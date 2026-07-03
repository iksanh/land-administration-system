<?php

namespace App\Enums;

/**
 * How a pemohon files the permit: on their own behalf (diri sendiri) or
 * through an authorised proxy (dikuasakan — surat kuasa data is then required).
 */
enum JenisPemohonEnum: string
{
    case DIRI_SENDIRI = 'diri_sendiri';
    case DIKUASAKAN = 'dikuasakan';

    public function label(): string
    {
        return match ($this) {
            self::DIRI_SENDIRI => 'Atas Nama Diri Sendiri',
            self::DIKUASAKAN => 'Dikuasakan',
        };
    }
}
