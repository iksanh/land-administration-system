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

    /**
     * Frasa peran pada daftar panitia pembuka Risalah (docs/RISALAH.pdf).
     * Beda tipis dari Berita Acara: kepala desa bukan anggota tetap panitia,
     * jadi pada risalah disebut "ditunjuk sebagai".
     */
    public function frasaRisalah(): string
    {
        return match ($this) {
            self::KETUA => 'sebagai Ketua merangkap Anggota',
            self::ANGGOTA => 'sebagai Anggota',
            self::SEKRETARIS => 'sebagai Sekretaris merangkap Anggota',
            self::KEPALA_DESA => 'ditunjuk sebagai Anggota',
        };
    }

    /**
     * Frasa peran pada bagian IX "Pendapat Anggota Panitia" Risalah — di sana
     * kata "Panitia" ikut disebut dan sekretaris pun memakai "ditunjuk".
     */
    public function frasaPendapatRisalah(): string
    {
        return match ($this) {
            self::KETUA => 'sebagai Ketua Panitia Merangkap Anggota',
            self::ANGGOTA => 'sebagai Anggota Panitia',
            self::SEKRETARIS => 'ditunjuk sebagai Sekretaris Merangkap Anggota',
            self::KEPALA_DESA => 'ditunjuk sebagai Anggota Panitia',
        };
    }

    /**
     * Urutan tampil pada bagian IX Risalah: ketua, para anggota, kepala desa,
     * lalu sekretaris di posisi terakhir — mengikuti dokumen resmi, yang berbeda
     * dari urutan tanda tangan (memakai kolom `urutan`).
     */
    public function prioritasPendapat(): int
    {
        return match ($this) {
            self::KETUA => 1,
            self::ANGGOTA => 2,
            self::KEPALA_DESA => 3,
            self::SEKRETARIS => 4,
        };
    }
}
