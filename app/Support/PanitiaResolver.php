<?php

namespace App\Support;

use App\Enums\PeranPanitiaEnum;
use App\Models\PanitiaPemeriksa;
use App\Models\Permohonan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Sumber tunggal untuk menyusun daftar penandatangan dokumen (Berita Acara &
 * Risalah): anggota panitia master (dengan pivot) DITAMBAH kepala desa AKTIF
 * dari desa lokasi tanah permohonan.
 *
 * Kepala desa dibungkus sebagai instance PanitiaPemeriksa non-persisted agar
 * tampil seragam di template (punya ->nama, ->nip, ->jabatan, ->peran, dan
 * ->pivot->pendapat = null) tanpa perlu mengubah view.
 */
class PanitiaResolver
{
    /**
     * @param  Collection<int, PanitiaPemeriksa>  $panitia  anggota panitia (dengan pivot)
     */
    public static function withKepalaDesa(Collection $panitia, ?Permohonan $permohonan): Collection
    {
        // Lokasi tanah menentukan kepala desa; fallback ke desa domisili pemohon
        // (mengikuti pola pemilihan desa pada template dokumen).
        $desa = $permohonan?->tanah?->desa ?? $permohonan?->pemohon?->desa;

        if (! $desa) {
            return $panitia;
        }

        $kepalaDesa = $desa->kepalaDesaAktif->map(function ($kd) {
            $p = new PanitiaPemeriksa([
                'nama' => $kd->nama,
                'nip' => $kd->nip,
                'jabatan' => $kd->jabatan ?: 'Kepala Desa',
                'peran' => PeranPanitiaEnum::KEPALA_DESA->value,
                'urutan' => 900 + (int) $kd->urutan,
                'is_active' => true,
            ]);
            // Pivot kosong agar $anggota->pivot->pendapat aman (null) di template.
            $p->setRelation('pivot', new Pivot);

            return $p;
        });

        return $panitia->concat($kepalaDesa)->values();
    }
}
