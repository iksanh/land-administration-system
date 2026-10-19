<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RefDesa;
use App\Models\Tanah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/sinkron/tanah?nib=...
 *
 * Dipakai aplikasi BA Risalah (SIPANTAS) untuk menarik data tanah, pemohon,
 * permohonan terakhir, dan riwayat penguasaan berdasarkan NIB.
 *
 * NIB dicocokkan sesudah dibuang semua yang bukan angka, karena penulisannya
 * berbeda antar aplikasi: di sini sering hanya nomor urutnya ("00571"),
 * sedangkan di SIPANTAS lengkap ("30.05.11.07.00571"). Selain sama persis,
 * diterima juga bila yang satu akhiran yang lain — asal bagian pendeknya
 * paling sedikit 5 angka, supaya "123" tidak mencocokkan apa saja. Karena
 * cocok-akhiran bisa lebih dari satu (nomor urut sama di desa berbeda),
 * hasilnya selalu daftar; pemanggil yang memilih.
 *
 * Kolom yang dikirim disebut satu per satu — tidak ada toArray() utuh,
 * supaya kolom baru (mis. data sensitif) tidak ikut bocor diam-diam.
 */
class SinkronTanahController extends Controller
{
    private const MIN_AKHIRAN = 5;

    private const MAKS_HASIL = 20;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nib' => ['required', 'string', 'max:100'],
        ]);

        $q = preg_replace('/\D/', '', $data['nib']);
        if (strlen($q) < 3) {
            return response()->json(['message' => 'NIB harus memuat paling sedikit 3 angka.'], 422);
        }

        // Normalisasi di sisi SQL: buang titik, spasi, dan tanda hubung.
        $n = "REPLACE(REPLACE(REPLACE(nib, '.', ''), ' ', ''), '-', '')";

        $tanah = Tanah::query()
            ->whereNotNull('nib')
            ->where(function ($w) use ($n, $q) {
                $w->whereRaw("$n = ?", [$q])
                    ->orWhereRaw("(CHAR_LENGTH($n) >= ? AND ? LIKE CONCAT('%', $n))", [self::MIN_AKHIRAN, $q]);
                if (strlen($q) >= self::MIN_AKHIRAN) {
                    $w->orWhereRaw("$n LIKE ?", ['%'.$q]);
                }
            })
            ->with(['desa.kecamatan.kabupaten.provinsi', 'pemohon.desa.kecamatan.kabupaten.provinsi'])
            ->orderByDesc('created_at')
            ->limit(self::MAKS_HASIL)
            ->get();

        // Permohonan terakhir per tanah, sekali kueri untuk semuanya.
        $permohonan = Permohonan::query()
            ->whereIn('tanah_id', $tanah->pluck('id'))
            ->with(['layanan', 'riwayatPenguasaan', 'pemohon.desa.kecamatan.kabupaten.provinsi'])
            ->orderByDesc('created_at')
            ->get()
            ->unique('tanah_id')
            ->keyBy('tanah_id');

        $hasil = $tanah->map(function (Tanah $t) use ($q, $permohonan) {
            $p = $permohonan->get($t->id);
            // Pemohon pada permohonan lebih tepat daripada pemilik catatan tanah.
            $pemohon = $p?->pemohon ?? $t->pemohon;
            $nibAngka = preg_replace('/\D/', '', (string) $t->nib);

            return [
                'cocok' => $nibAngka === $q ? 'persis' : 'sebagian',
                'tanah' => $this->tanah($t),
                'pemohon' => $pemohon ? $this->pemohon($pemohon) : null,
                'permohonan' => $p ? $this->permohonan($p) : null,
                'riwayat_penguasaan' => array_values(array_filter(
                    (array) ($p?->riwayatPenguasaan?->poin ?? []),
                    fn ($x) => is_string($x) && trim($x) !== ''
                )),
            ];
        })->sortBy(fn ($x) => $x['cocok'] === 'persis' ? 0 : 1)->values();

        return response()->json(['data' => $hasil]);
    }

    private function tanah(Tanah $t): array
    {
        return [
            'id' => $t->id,
            'nib' => $t->nib,
            'nomor_pbt' => $t->nomor_pbt,
            'tanggal_pbt' => $t->tanggal_pbt?->format('Y-m-d'),
            'luas' => $t->luas !== null ? (float) $t->luas : null,
            'luas_surat' => $t->luas_surat !== null ? (float) $t->luas_surat : null,
            'penggunaan_tanah' => $t->penggunaan_tanah,
            'rencana_penggunaan_rtrw' => $t->rencana_penggunaan_rtrw,
            'penggunaan_tanah_sk' => $t->penggunaan_tanah_sk,
            'rtrw_kawasan' => $t->rtrw_kawasan,
            'kesesuaian_penggunaan_tanah' => $t->kesesuaian_penggunaan_tanah,
            'tgl_peta_analisis' => $t->tgl_peta_analisis?->format('Y-m-d'),
            'pejabat_peta_analisis' => $t->pejabat_peta_analisis,
            'nip_peta_analisis' => $t->nip_peta_analisis,
            'batas_utara' => $t->batas_utara,
            'batas_timur' => $t->batas_timur,
            'batas_selatan' => $t->batas_selatan,
            'batas_barat' => $t->batas_barat,
            'desa' => $this->wilayah($t->desa),
        ];
    }

    private function pemohon(Pemohon $p): array
    {
        return [
            'id' => $p->id,
            'nik' => $p->nik,
            'nama' => $p->nama,
            'jenis_pemohon' => $p->jenis_pemohon?->value,
            'tempat_lahir' => $p->tempat_lahir,
            'tanggal_lahir' => $p->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin' => $p->jenis_kelamin?->value,
            'pekerjaan' => $p->pekerjaan,
            'alamat_detail' => $p->alamat_detail,
            'desa' => $this->wilayah($p->desa),
            'kuasa' => $p->kuasa_nama ? [
                'nama' => $p->kuasa_nama,
                'nik' => $p->kuasa_nik,
                'pekerjaan' => $p->kuasa_pekerjaan,
                'no_hp' => $p->kuasa_no_hp,
                'alamat' => $p->kuasa_alamat,
                'hubungan' => $p->kuasa_hubungan,
                'no_surat' => $p->kuasa_no_surat,
                'tanggal_surat' => $p->kuasa_tanggal_surat?->format('Y-m-d'),
            ] : null,
        ];
    }

    private function permohonan(Permohonan $p): array
    {
        return [
            'id' => $p->id,
            'nomor_registrasi' => $p->nomor_registrasi,
            'nomor_berkas' => $p->nomor_berkas,
            'tahun_berkas' => $p->tahun_berkas,
            'tanggal_daftar_kkp' => $p->tanggal_daftar_kkp?->format('Y-m-d'),
            'tgl_pendaftaran' => $p->tgl_pendaftaran?->format('Y-m-d'),
            'status' => $p->status?->value,
            'layanan' => $p->layanan ? ['kode' => $p->layanan->kode, 'nama' => $p->layanan->nama] : null,
        ];
    }

    /** Desa beserta rantai wilayahnya; kode Kemendagri ikut supaya penerima bisa mencocokkan. */
    private function wilayah(?RefDesa $d): ?array
    {
        if (! $d) {
            return null;
        }
        $kec = $d->kecamatan;
        $kab = $kec?->kabupaten;
        $prov = $kab?->provinsi;

        return [
            'id' => $d->id,
            'nama' => $d->nama,
            'jenis' => $d->jenis?->value,
            'kecamatan' => $kec ? ['id' => $kec->id, 'nama' => $kec->nama] : null,
            'kabupaten' => $kab ? ['id' => $kab->id, 'nama' => $kab->nama] : null,
            'provinsi' => $prov ? ['id' => $prov->id, 'nama' => $prov->nama] : null,
        ];
    }
}
