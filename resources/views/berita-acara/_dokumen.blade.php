@php
    use App\Support\Terbilang;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    // $mode: 'print' (gambar via URL) atau 'word' (gambar di-embed base64 agar
    // berkas .doc berdiri sendiri). Tata letak memakai <table> + inline style
    // supaya konsisten di browser/PDF maupun Microsoft Word (Word tak mendukung flexbox).
    $mode = $mode ?? 'print';

    $p = $ba->permohonan;
    $t = $p?->tanah;
    $pemohon = $p?->pemohon;
    $desa = $t?->desa;
    $kec = $desa?->kecamatan;
    $kab = $kec?->kabupaten;
    $prov = $kab?->provinsi;

    $tglPeriksa = $ba->tgl_pemeriksaan;
    $luasInt = $t && $t->luas !== null ? (int) $t->luas : null;
    $luasFmt = $t && $t->luas !== null ? rtrim(rtrim(number_format($t->luas, 2, ',', '.'), '0'), ',') : null;
    $poinRiwayat = array_values(array_filter($ba->permohonan?->riwayatPenguasaan?->poin ?? []));

    // Sebutan wilayah mengikuti jenisnya: "Desa Poduwoma" / "Kelurahan Tumbihe".
    $letakSingkat = ($desa?->namaLengkap() ?? 'Desa …')
        .', Kecamatan '.($kec?->nama ?? '…')
        .', Kabupaten '.($kab?->nama ?? '…')
        .', Provinsi '.($prov?->nama ?? '…');

    // Sapaan pemohon mengikuti jenis kelamin (dokumen resmi memakai Sdr./Sdri.).
    $sapaan = $pemohon?->jenis_kelamin?->sapaan() ?? 'Sdr./Sdri.';

    $penggunaanSekarang = $t?->penggunaan_tanah ?: '…';
    $rencanaPenggunaan = $t?->rencana_penggunaan_rtrw ?: '…';
    $kawasanRtrw = $t?->rtrw_kawasan ?: '…';

    $batas = [
        'Utara' => $t?->batas_utara,
        'Timur' => $t?->batas_timur,
        'Selatan' => $t?->batas_selatan,
        'Barat' => $t?->batas_barat,
    ];

    // Butir bernomor pada dokumen diakhiri ';' kecuali yang terakhir '.'.
    $akhiri = fn (string $teks, bool $terakhir) => rtrim(trim($teks), '.;').($terakhir ? '.' : ';');

    // Penanda tangan Peta Analisis: nama & NIP opsional, klausa jabatannya tetap.
    $penandatanganAnalisis = $t?->pejabat_peta_analisis
        ? ' '.$t->pejabat_peta_analisis.($t->nip_peta_analisis ? ' NIP. '.$t->nip_peta_analisis : '')
        : '';

    $keadaanBaku = 'Bahwa keadaan tanah saat ini (existing land use) sesuai hasil tinjauan kami di lapangan adalah '
        .$penggunaanSekarang.' yang akan digunakan untuk '.$rencanaPenggunaan.'.';

    // Kotak cetak satu halaman A4 dikurangi margin @page (lihat berita-acara/print.blade.php:
    // 21cm - 3cm - 2.5cm lebar, 29.7cm - 5cm tinggi), disisakan sedikit untuk keterangan.
    $kotakLebar = 15.5;
    $kotakTinggi = 24.0;

    /**
     * Ukuran cetak sebuah lampiran dalam cm: diperbesar/diperkecil sampai pas di
     * dalam kotak halaman tanpa mengubah rasio. Dihitung dari dimensi asli berkas
     * supaya tidak bergantung pada object-fit (yang diabaikan Word).
     */
    $fitGambar = function (string $path, bool $adaKeterangan = false) use ($kotakLebar, $kotakTinggi): array {
        $tinggiMaks = $kotakTinggi - ($adaKeterangan ? 1.2 : 0);

        try {
            $ukuran = @getimagesize(Storage::disk('public')->path($path));
        } catch (\Throwable $e) {
            $ukuran = false;
        }

        if (! $ukuran || $ukuran[0] < 1 || $ukuran[1] < 1) {
            // Dimensi tak terbaca — pakai lebar penuh, tinggi mengikuti rasio asli.
            return [$kotakLebar, null];
        }

        $skala = min($kotakLebar / $ukuran[0], $tinggiMaks / $ukuran[1]);

        return [round($ukuran[0] * $skala, 2), round($ukuran[1] * $skala, 2)];
    };

    $imgSrc = function (string $path) use ($mode): string {
        if ($mode === 'word') {
            try {
                $bin = Storage::disk('public')->get($path);
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'jpeg';
                $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

                return 'data:'.$mime.';base64,'.base64_encode($bin);
            } catch (\Throwable $e) {
                return '';
            }
        }

        return Storage::disk('public')->url($path);
    };

    // Style helper supaya ringkas.
    $cell = 'vertical-align:top;';
    $num = 'width:24px;vertical-align:top;';
    $sub = 'width:24px;vertical-align:top;text-align:right;padding-right:4px;';
@endphp

<div style="font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.5;color:#000;text-align:justify;">

    <p style="text-align:center;font-weight:bold;text-transform:uppercase;font-size:13pt;margin:0;line-height:1.4;">
        Berita Acara Pemeriksaan Lapang<br>oleh Anggota Panitia Pemeriksa Tanah A
    </p>
    @if ($ba->nomor_ba)
        <p style="text-align:center;font-weight:bold;margin:0 0 16px;">Nomor: {{ $ba->nomor_ba }}</p>
    @else
        <div style="height:14px"></div>
    @endif

    <p style="margin:0 0 8px;">
        Pada hari ini
        @if ($tglPeriksa)
            {{ Terbilang::tanggal($tglPeriksa) }} ({{ $tglPeriksa->format('d/m/Y') }}),
        @else
            …………………………………,
        @endif
        kami yang bertandatangan di bawah ini :
    </p>

    {{-- Anggota panitia --}}
    @forelse ($ba->panitia as $i => $anggota)
        <table style="width:100%;border-collapse:collapse;margin:4px 0 10px;">
            <tr>
                <td style="width:24px;{{ $cell }}">{{ $i + 1 }}.</td>
                <td style="width:80px;{{ $cell }}">Nama</td>
                <td style="width:12px;{{ $cell }}">:</td>
                <td style="{{ $cell }}"><strong>{{ $anggota->nama }}</strong></td>
            </tr>
            @if ($anggota->nip)
                <tr><td></td><td style="{{ $cell }}">NIP</td><td>:</td><td>{{ $anggota->nip }}</td></tr>
            @endif
            <tr>
                <td></td><td style="{{ $cell }}">Jabatan</td><td>:</td>
                {{-- Dokumen resmi tidak memberi koma pada baris kepala desa/lurah. --}}
                @php $pisah = $anggota->peran === \App\Enums\PeranPanitiaEnum::KEPALA_DESA ? ' ' : ', '; @endphp
                <td>{{ $anggota->jabatan }}{{ $anggota->jabatan ? $pisah : '' }}{{ $anggota->peran->frasa() }}</td>
            </tr>
        </table>
    @empty
        <p><em>Anggota panitia belum dipilih.</em></p>
    @endforelse

    <p style="margin:0 0 8px;">
        Dengan ini kami telah melakukan pemeriksaan lapang atas permohonan dari {{ $sapaan }}
        <strong>{{ $pemohon?->nama ?? '…………………' }}</strong>
        atas sebidang tanah seluas
        <strong>{{ $luasFmt ?? '…' }} m&sup2;</strong>@if ($luasInt) ({{ Terbilang::make($luasInt) }} meter persegi)@endif
        sesuai dengan Peta Bidang Tanah Nomor <strong>{{ $t?->nomor_pbt ?? '…' }}</strong>@if ($t?->tanggal_pbt) tanggal {{ $t->tanggal_pbt->locale('id')->translatedFormat('d F Y') }}@endif
        NIB. <strong>{{ $t?->nib ?? '…' }}</strong> terletak di {{ $letakSingkat }},
        dengan hasil sebagai berikut:
    </p>

    {{-- 1. Penguasaan, Penggunaan, Keadaan --}}
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="{{ $num }}">1.</td>
            <td style="{{ $cell }}">
                <strong>Penguasaan, Penggunaan dan Keadaan Tanah</strong>

                {{-- a. Penguasaan --}}
                <table style="width:100%;border-collapse:collapse;margin-top:6px;">
                    <tr>
                        <td style="{{ $sub }}">a.</td>
                        <td style="{{ $cell }}">
                            Bahwa bidang tanah yang dimohon dikuasai oleh <strong>{{ $pemohon?->nama ?? '…………………' }}</strong> dengan riwayat;
                            {{-- Butir terakhir (penguasaan fisik terkini) nantinya dihasilkan dari
                                 modul Surat Pernyataan Penguasaan Fisik; untuk sekarang seluruh
                                 butir berasal dari Riwayat Penguasaan permohonan. --}}
                            @if (count($poinRiwayat))
                                <table style="width:100%;border-collapse:collapse;">
                                    @foreach ($poinRiwayat as $poin)
                                        <tr>
                                            <td style="width:16px;{{ $cell }}">-</td>
                                            <td style="{{ $cell }}white-space:pre-line;">{{ $akhiri($poin, $loop->last) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <p style="margin:0;">…………………………………………………………………………</p>
                            @endif
                        </td>
                    </tr>
                    {{-- b. Penggunaan --}}
                    <tr>
                        <td style="{{ $sub }}">b.</td>
                        <td style="{{ $cell }}">
                            <strong>Penggunaan Tanah</strong>
                            <p style="margin:0 0 6px;">
                                Bahwa penggunaan tanah di lapangan adalah <strong>{{ $penggunaanSekarang }}</strong>
                                dan rencana penggunaan tanah berupa <strong>{{ $rencanaPenggunaan }}</strong>.
                                Berdasarkan Peta Analisis Penatagunaan Tanah Tema Kesesuaian Penggunaan Tanah Terhadap RTRW
                                tanggal {{ $t?->tgl_peta_analisis ? $t->tgl_peta_analisis->locale('id')->translatedFormat('d F Y') : '…………………' }}
                                yang ditandatangani oleh Kepala Seksi Penataan dan Pemberdayaan Kantor Pertanahan Kabupaten Bone Bolango{{ $penandatanganAnalisis }},
                                bidang tanah yang dimohon berada dalam <strong>{{ $kawasanRtrw }}</strong>, sehingga bidang tanah tersebut
                                {{ $t?->kesesuaian_penggunaan_tanah ?: 'Sesuai' }} dengan {{ $ba->perda_rtrw ?: '…………………' }}.
                            </p>
                        </td>
                    </tr>
                    {{-- c. Keadaan --}}
                    <tr>
                        <td style="{{ $sub }}">c.</td>
                        <td style="{{ $cell }}">
                            <strong>Keadaan Tanah</strong>
                            {{-- Kalimat baku terbentuk dari data tanah; `keadaan_tanah` dipakai
                                 sebagai penimpa bila petugas perlu menuliskannya sendiri. --}}
                            <p style="margin:0;white-space:pre-line;">{{ $ba->keadaan_tanah ?: $keadaanBaku }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- 2. Batas-batas --}}
        <tr>
            <td style="{{ $num }}">2.</td>
            <td style="{{ $cell }}">
                Batas &ndash; batas bidang tanah yang dimohon adalah sebagai berikut :
                <table style="border-collapse:collapse;margin:2px 0 6px;">
                    @foreach ($batas as $arah => $nilai)
                        <tr>
                            <td style="width:80px;{{ $cell }}">{{ $arah }}</td>
                            <td style="width:12px;{{ $cell }}">:</td>
                            <td>berbatasan dengan {{ $akhiri($nilai ?: '…', $loop->last) }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>

        {{-- 3. Keberatan --}}
        <tr>
            <td style="{{ $num }}">3.</td>
            <td style="{{ $cell }}">{{ $ba->catatan_keberatan ?: 'Bahwa pada saat kami melakukan Pemeriksaan Lapang tidak ada yang mengajukan keberatan atau merasa keberatan terhadap Permohonan Hak dimaksud;' }}</td>
        </tr>

        {{-- 4. Lampiran --}}
        <tr>
            <td style="{{ $num }}">4.</td>
            <td style="{{ $cell }}">Lampiran Dokumentasi Pemeriksaan Lapang;</td>
        </tr>
    </table>

    {{-- Tanda tangan panitia sengaja TIDAK dicetak: lembar tanda tangan diedarkan
         terpisah, ditandatangani (termasuk oleh kepala desa/lurah), lalu hasil
         pindaiannya diunggah kembali sebagai lampiran di bawah. --}}

    {{-- Lampiran: satu berkas per halaman, diperbesar sebesar mungkin tanpa
         mengubah rasio. Ukuran dihitung di server (lihat $fitGambar) agar hasilnya
         sama di browser, PDF, maupun Microsoft Word — Word tidak mendukung
         object-fit, jadi lebar/tinggi harus eksplisit. --}}
    @foreach ($ba->lampiran as $lampiran)
        @php
            $src = $imgSrc($lampiran->path);
            [$gw, $gh] = $fitGambar($lampiran->path, (bool) $lampiran->keterangan);
        @endphp
        @if ($src)
            <div style="page-break-before:always;text-align:center;">
                <img src="{{ $src }}" style="width:{{ $gw }}cm;@if ($gh) height:{{ $gh }}cm;@endif border:1px solid #000;" alt="{{ $lampiran->keterangan ?: 'Lampiran' }}">
                @if ($lampiran->keterangan)
                    <div style="font-size:10pt;margin-top:6px;">{{ $lampiran->keterangan }}</div>
                @endif
            </div>
        @endif
    @endforeach
</div>
