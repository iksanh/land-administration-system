<?php

namespace App\Enums;

/**
 * Role sistem. Satu user bisa memegang beberapa role sekaligus
 * (kolom users.roles, JSON array of string).
 *  - admin   : kelola user, wilayah, dan data master.
 *  - petugas : staf pelaksana (Staf Korsub) — input & proses harian.
 *  - koorsub : Koordinator Substansi — pemeriksa/verifikator tahapan Korsub.
 */
enum UserRoleEnum: string
{
    case ADMIN = 'admin';
    case PETUGAS = 'petugas';
    case KOORSUB = 'koorsub';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::PETUGAS => 'Petugas (Staf)',
            self::KOORSUB => 'Koorsub',
        };
    }

    /** Deskripsi singkat untuk form pemilihan role. */
    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Kelola user, wilayah, dan data master',
            self::PETUGAS => 'Staf pelaksana — input & proses harian',
            self::KOORSUB => 'Koordinator Substansi — verifikasi tahapan Korsub',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ADMIN => 'bg-[#f9f0ff] text-[#722ed1] border-[#d3adf7]',
            self::PETUGAS => 'bg-[#e6f4ff] text-[#1677ff] border-[#91caff]',
            self::KOORSUB => 'bg-[#fff7e6] text-[#d46b08] border-[#ffd591]',
        };
    }
}
