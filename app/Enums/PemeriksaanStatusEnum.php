<?php

namespace App\Enums;

enum PemeriksaanStatusEnum: string
{
    case PENDING = 'PENDING';
    case OK = 'OK';
    case REVISI = 'REVISI';
    case TOLAK = 'TOLAK';
}
