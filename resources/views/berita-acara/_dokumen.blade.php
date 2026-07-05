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
        kami yang bertandatangan di bawah ini:
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
                <td>{{ $anggota->jabatan }}{{ $anggota->jabatan ? ', ' : '' }}{{ $anggota->peran->frasa() }}</td>
            </tr>
        </table>
    @empty
        <p><em>Anggota panitia belum dipilih.</em></p>
    @endforelse

    <p style="margin:0 0 8px;">
        Dengan ini kami telah melakukan pemeriksaan lapang atas permohonan dari Sdr.
        <strong>{{ $pemohon?->nama ?? '…………………' }}</strong>
        atas sebidang tanah seluas
        <strong>{{ $luasFmt ?? '…' }} m&sup2;</strong>
        @if ($luasInt)( {{ Terbilang::make($luasInt) }} meter persegi )@endif
        sesuai dengan Peta Bidang Tanah Nomor <strong>{{ $t?->nomor_pbt ?? '…' }}</strong>
        @if ($t?->tanggal_pbt) tanggal {{ $t->tanggal_pbt->locale('id')->translatedFormat('d F Y') }} @endif
        NIB. <strong>{{ $t?->nib ?? '…' }}</strong> terletak di Desa {{ $desa?->nama ?? '…' }},
        Kecamatan {{ $kec?->nama ?? '…' }}, Kabupaten {{ $kab?->nama ?? '…' }},
        Provinsi {{ $prov?->nama ?? '…' }}, dengan hasil sebagai berikut:
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
                            <strong>Penguasaan</strong>
                            @if (count($poinRiwayat))
                                <table style="width:100%;border-collapse:collapse;">
                                    @foreach ($poinRiwayat as $poin)
                                        <tr>
                                            <td style="width:16px;{{ $cell }}">-</td>
                                            <td style="{{ $cell }}white-space:pre-line;">{{ $poin }}</td>
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
                                Bahwa penggunaan tanah di lapangan adalah <strong>{{ $t?->penggunaan_tanah ?? '…' }}</strong>
                                dan rencana penggunaan tanah berupa <strong>{{ $t?->rencana_penggunaan_rtrw ?? '…' }}</strong>.
                                @if ($t?->tgl_peta_analisis)
                                    Berdasarkan Peta Analisis Penatagunaan Tanah tanggal {{ $t->tgl_peta_analisis->locale('id')->translatedFormat('d F Y') }},
                                @endif
                                bidang tanah yang dimohon dinyatakan <strong>{{ $t?->kesesuaian_penggunaan_tanah ?? '…' }}</strong>
                                @if ($ba->perda_rtrw) sesuai dengan {{ $ba->perda_rtrw }} @endif.
                            </p>
                        </td>
                    </tr>
                    {{-- c. Keadaan --}}
                    <tr>
                        <td style="{{ $sub }}">c.</td>
                        <td style="{{ $cell }}">
                            <strong>Keadaan Tanah</strong>
                            <p style="margin:0;white-space:pre-line;">{{ $ba->keadaan_tanah ?: '…………………………………………………………………………' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- 2. Batas-batas --}}
        <tr>
            <td style="{{ $num }}">2.</td>
            <td style="{{ $cell }}">
                Batas-batas bidang tanah yang dimohon adalah sebagai berikut:
                <table style="border-collapse:collapse;margin:2px 0 6px;">
                    <tr><td style="width:80px;{{ $cell }}">Utara</td><td style="width:12px;">:</td><td>berbatasan dengan {{ $t?->batas_utara ?? '…' }}</td></tr>
                    <tr><td style="{{ $cell }}">Timur</td><td>:</td><td>berbatasan dengan {{ $t?->batas_timur ?? '…' }}</td></tr>
                    <tr><td style="{{ $cell }}">Selatan</td><td>:</td><td>berbatasan dengan {{ $t?->batas_selatan ?? '…' }}</td></tr>
                    <tr><td style="{{ $cell }}">Barat</td><td>:</td><td>berbatasan dengan {{ $t?->batas_barat ?? '…' }}</td></tr>
                </table>
            </td>
        </tr>

        {{-- 3. Keberatan --}}
        <tr>
            <td style="{{ $num }}">3.</td>
            <td style="{{ $cell }}">{{ $ba->catatan_keberatan ?: 'Bahwa pada saat kami melakukan Pemeriksaan Lapang tidak ada yang mengajukan keberatan terhadap Permohonan Hak dimaksud.' }}</td>
        </tr>

        {{-- 4. Lampiran --}}
        <tr>
            <td style="{{ $num }}">4.</td>
            <td style="{{ $cell }}">Lampiran Dokumentasi Pemeriksaan Lapang sebagaimana terlampir.</td>
        </tr>
    </table>

    {{-- Tanda tangan (2 kolom) --}}
    <table style="width:100%;border-collapse:collapse;margin-top:24px;">
        @foreach ($ba->panitia->chunk(2) as $baris)
            <tr>
                @foreach ($baris as $anggota)
                    <td style="width:50%;vertical-align:top;padding-bottom:30px;">
                        <div>{{ Str::ucfirst(Str::after($anggota->peran->frasa(), 'sebagai ')) }},</div>
                        <div style="height:60px;"></div>
                        <div style="font-weight:bold;text-decoration:underline;">{{ $anggota->nama }}</div>
                        @if ($anggota->nip)<div>NIP. {{ $anggota->nip }}</div>@endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    {{-- Foto dokumentasi --}}
    @if ($ba->lampiran->count())
        <div style="page-break-before:always;">
            <p style="text-align:center;font-weight:bold;text-transform:uppercase;font-size:13pt;margin:0 0 12px;">Lampiran Dokumentasi Pemeriksaan Lapang</p>
            <table style="width:100%;border-collapse:collapse;">
                @foreach ($ba->lampiran->chunk(3) as $baris)
                    <tr>
                        @foreach ($baris as $i => $lampiran)
                            @php $src = $imgSrc($lampiran->path); @endphp
                            <td style="width:33%;vertical-align:top;text-align:center;padding:4px;">
                                @if ($src)
                                    <img src="{{ $src }}" width="200" style="width:200px;height:150px;object-fit:cover;border:1px solid #000;" alt="Dokumentasi">
                                @endif
                                <div style="font-size:10pt;">{{ $lampiran->keterangan ?: 'Dokumentasi' }}</div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</div>
