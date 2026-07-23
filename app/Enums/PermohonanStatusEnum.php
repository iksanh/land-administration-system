<?php

namespace App\Enums;

/**
 * Alur status berkas mengikuti tahapan kantor (docs/gambar status berkas.png).
 * Nilai disimpan sebagai string (maks 30 karakter — lihat kolom
 * permohonan.status / permohonan_audit_log.status_baru).
 *
 * DITOLAK dipertahankan sebagai status terminal di luar alur normal agar
 * permohonan tetap bisa ditolak dan data lama tidak kehilangan makna.
 */
enum PermohonanStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case PERIKSA_BERKAS_STAF = 'PERIKSA_BERKAS_STAF';
    case PERIKSA_BERKAS_KORSUB = 'PERIKSA_BERKAS_KORSUB';
    case PROSES_DAFTAR = 'PROSES_DAFTAR';
    case TERDAFTAR = 'TERDAFTAR';
    case KONSEP_RPD_BA_SK_STAF = 'KONSEP_RPD_BA_SK_STAF';
    case PERIKSA_KONSEP_KORSUB = 'PERIKSA_KONSEP_KORSUB';
    case TURUN_PANITIA = 'TURUN_PANITIA';
    case SU_EL = 'SU_EL';
    case BOOKING_NOMOR_RISALAH = 'BOOKING_NOMOR_RISALAH';
    case TTD_RISALAH = 'TTD_RISALAH';
    case KONSEP_SK = 'KONSEP_SK';
    case PERSETUJUAN_KONSEP_SK = 'PERSETUJUAN_KONSEP_SK';
    case CETAK_PARAF_SK = 'CETAK_PARAF_SK';
    case TTD_SK = 'TTD_SK';
    case LOKET_PENYERAHAN = 'LOKET_PENYERAHAN';
    case DITOLAK = 'DITOLAK';

    /**
     * Urutan alur normal (tanpa DITOLAK — status terminal di luar alur).
     *
     * @return list<self>
     */
    public static function flow(): array
    {
        return array_values(array_filter(self::cases(), fn (self $c) => $c !== self::DITOLAK));
    }

    /** Posisi 0-based dalam alur normal; null untuk DITOLAK. */
    public function stepIndex(): ?int
    {
        $i = array_search($this, self::flow(), true);

        return $i === false ? null : $i;
    }

    /** Tahap berikutnya dalam alur; null di tahap akhir atau DITOLAK. */
    public function next(): ?self
    {
        $i = $this->stepIndex();

        return $i === null ? null : (self::flow()[$i + 1] ?? null);
    }

    /** Satu tahap sebelumnya; null di tahap awal atau DITOLAK. */
    public function prev(): ?self
    {
        $i = $this->stepIndex();

        return $i === null || $i === 0 ? null : self::flow()[$i - 1];
    }

    /**
     * Role yang berwenang memproses (maju/mundur/tolak) permohonan yang
     * sedang berada di tahap ini. Admin selalu boleh (dicek di pemanggil).
     * Tahap yang menyebut Korsub/Kasi/Kakan dipegang koorsub; tahap
     * staf/loket/lapangan dipegang petugas. DITOLAK hanya bisa dibuka
     * kembali oleh koorsub (atau admin).
     *
     * @return list<string> nilai UserRoleEnum
     */
    public function allowedRoles(): array
    {
        return match ($this) {
            self::PERIKSA_BERKAS_KORSUB,
            self::PERIKSA_KONSEP_KORSUB,
            self::TTD_RISALAH,
            self::KONSEP_SK,
            self::PERSETUJUAN_KONSEP_SK,
            self::CETAK_PARAF_SK,
            self::TTD_SK,
            self::DITOLAK => [UserRoleEnum::KOORSUB->value],
            default => [UserRoleEnum::PETUGAS->value],
        };
    }

    /** Label role berwenang untuk pesan/tampilan, mis. "Koorsub". */
    public function allowedRoleLabels(): string
    {
        return collect($this->allowedRoles())
            ->map(fn (string $r) => UserRoleEnum::from($r)->label())
            ->join(', ');
    }

    /** Nama tahapan yang tampil ke pengguna. */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Pra-Daftar',
            self::PERIKSA_BERKAS_STAF => 'Periksa Berkas (Staf Korsub)',
            self::PERIKSA_BERKAS_KORSUB => 'Periksa Berkas (Korsub)',
            self::PROSES_DAFTAR => 'Proses Daftar (Loket)',
            self::TERDAFTAR => 'Terdaftar',
            self::KONSEP_RPD_BA_SK_STAF => 'Konsep RPD & BA & SK (Staf Korsub)',
            self::PERIKSA_KONSEP_KORSUB => 'Periksa Berkas & Konsep RPD & BA & SK (Korsub)',
            self::TURUN_PANITIA => 'Turun Panitia',
            self::SU_EL => 'SU-el (Pengukuran)',
            self::BOOKING_NOMOR_RISALAH => 'Booking Nomor Risalah',
            self::TTD_RISALAH => 'Penandatanganan Risalah (Korsub-Kasi P2-Kasi 1-Kasi 2)',
            self::KONSEP_SK => 'Konsep SK (Paraf Korsub & Kasi 2)',
            self::PERSETUJUAN_KONSEP_SK => 'Persetujuan Konsep SK (Kakan)',
            self::CETAK_PARAF_SK => 'Cetak & Paraf SK (Korsub & Kasi 2)',
            self::TTD_SK => 'Penandatanganan SK (Kakan)',
            self::LOKET_PENYERAHAN => 'Loket Penyerahan',
            self::DITOLAK => 'Ditolak',
        };
    }

    /**
     * Kelas badge bergaya Ant Design — satu warna per fase alur sehingga
     * tahapan terbaca sekilas: abu (pra-daftar) → biru (periksa berkas) →
     * cyan (pendaftaran) → gold (konsep) → ungu (lapangan) → magenta
     * (risalah) → volcano (SK) → hijau (penyerahan); merah untuk ditolak.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-600 border-gray-200',
            self::PERIKSA_BERKAS_STAF,
            self::PERIKSA_BERKAS_KORSUB => 'bg-[#e6f4ff] text-[#1677ff] border-[#91caff]',
            self::PROSES_DAFTAR,
            self::TERDAFTAR => 'bg-[#e6fffb] text-[#08979c] border-[#87e8de]',
            self::KONSEP_RPD_BA_SK_STAF,
            self::PERIKSA_KONSEP_KORSUB => 'bg-[#fffbe6] text-[#d48806] border-[#ffe58f]',
            self::TURUN_PANITIA,
            self::SU_EL => 'bg-[#f9f0ff] text-[#722ed1] border-[#d3adf7]',
            self::BOOKING_NOMOR_RISALAH,
            self::TTD_RISALAH => 'bg-[#fff0f6] text-[#c41d7f] border-[#ffadd2]',
            self::KONSEP_SK,
            self::PERSETUJUAN_KONSEP_SK,
            self::CETAK_PARAF_SK,
            self::TTD_SK => 'bg-[#fff2e8] text-[#d4380d] border-[#ffbb96]',
            self::LOKET_PENYERAHAN => 'bg-[#f6ffed] text-[#389e0d] border-[#b7eb8f]',
            self::DITOLAK => 'bg-[#fff1f0] text-[#cf1322] border-[#ffa39e]',
        };
    }
}
