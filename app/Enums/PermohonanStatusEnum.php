<?php

namespace App\Enums;

enum PermohonanStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case VERIFIKASI_BERKAS = 'VERIFIKASI_BERKAS';
    case PENGUKURAN = 'PENGUKURAN';
    case PANITIA = 'PANITIA';
    case SK_TERBIT = 'SK_TERBIT';
    case SELESAI = 'SELESAI';
    case DITOLAK = 'DITOLAK';
}
