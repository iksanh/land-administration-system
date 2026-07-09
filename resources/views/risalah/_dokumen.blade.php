@php
    use App\Support\Terbilang;
    use App\Support\RisalahDefaults;
    use Illuminate\Support\Str;

    // $mode: 'print' (browser/PDF) atau 'word' (.doc). Tata letak memakai <table>
    // + inline style agar konsisten di browser/PDF maupun Microsoft Word (Word
    // tak mendukung flexbox). Struktur & penomoran romawi (I..X) mengikuti format
    // resmi Kantor Pertanahan Bone Bolango (lihat docs/RISALAH.pdf).
    $mode = $mode ?? 'print';

    $p = $r->permohonan;
    $t = $p?->tanah;
    $pemohon = $p?->pemohon;
    $desa = $t?->desa ?? $pemohon?->desa;
    $kec = $desa?->kecamatan;
    $kab = $kec?->kabupaten;
    $prov = $kab?->provinsi;

    // Domisili/tempat kedudukan pemohon (dari desa domisili pemohon).
    $pdesa = $pemohon?->desa;
    $domisiliPemohon = collect([
        $pemohon?->alamat_detail,
        $pdesa ? 'Desa '.$pdesa->nama : null,
        $pdesa?->kecamatan ? 'Kec. '.$pdesa->kecamatan->nama : null,
        $pdesa?->kecamatan?->kabupaten ? 'Kab. '.$pdesa->kecamatan->kabupaten->nama : null,
    ])->filter()->implode(', ');

    // Luas ukur (kadastral) dan luas menurut surat (untuk uraian selisih).
    $luasInt = $t && $t->luas !== null ? (int) $t->luas : null;
    $luasFmt = $t && $t->luas !== null ? rtrim(rtrim(number_format($t->luas, 2, ',', '.'), '0'), ',') : null;
    $luasSuratInt = $t && $t->luas_surat !== null ? (int) $t->luas_surat : null;
    $luasSuratFmt = $t && $t->luas_surat !== null ? rtrim(rtrim(number_format($t->luas_surat, 2, ',', '.'), '0'), ',') : null;
    $selisihInt = ($luasSuratInt !== null && $luasInt !== null && $luasSuratInt > $luasInt) ? $luasSuratInt - $luasInt : null;

    $poinRiwayat = array_values(array_filter($p?->riwayatPenguasaan?->poin ?? []));
    $dataPendukung = array_values(array_filter($r->data_pendukung ?? []));
    $dasarHukum = array_values(array_filter($r->dasar_hukum ?? []));

    $jenisHak = $r->jenis_hak ?: 'Hak Milik';
    $namaPemohon = $pemohon?->nama ?? '…………………';
    $letakSingkat = 'Desa '.($desa?->nama ?? '…').', Kecamatan '.($kec?->nama ?? '…').', Kabupaten '.($kab?->nama ?? '…').', Provinsi '.($prov?->nama ?? '…');
    $penggunaan = $t?->penggunaan_tanah ?: '…';
    $kawasanRtrw = $r->rtrw_kawasan ?: ($t?->rencana_penggunaan_rtrw ?: '…');
    $perdaRtrw = $r->perda_rtrw ?: RisalahDefaults::PERDA_RTRW;

    $jumlahPanitia = $r->panitia->count();

    $tgl = fn ($d) => $d ? $d->locale('id')->translatedFormat('d F Y') : '…………………';

    // Style helper ringkas.
    $cell = 'vertical-align:top;';
    $romNum = 'width:34px;vertical-align:top;font-weight:bold;';
    $letter = 'width:26px;vertical-align:top;padding-left:8px;';
    $numItem = 'width:26px;vertical-align:top;text-align:right;padding-right:6px;';
    $roman2 = 'width:30px;vertical-align:top;padding-left:16px;';
    $lbl = 'width:200px;vertical-align:top;';
    $sep = 'width:12px;vertical-align:top;';
    $heading = 'font-weight:bold;';
@endphp

<div style="font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.5;color:#000;text-align:justify;">

    {{-- ===== KOP SURAT ===== --}}
    {{-- Catatan: logo ATR/BPN belum tersedia sebagai aset; kop dirender teks. --}}
    <div style="text-align:center;line-height:1.25;">
        <div style="font-weight:bold;font-size:14pt;">KEMENTERIAN AGRARIA DAN TATA RUANG/</div>
        <div style="font-weight:bold;font-size:14pt;">BADAN PERTANAHAN NASIONAL</div>
        <div style="font-weight:bold;font-size:13pt;">KANTOR PERTANAHAN KABUPATEN BONE BOLANGO</div>
        <div style="font-weight:bold;font-size:13pt;">PROVINSI GORONTALO</div>
        <div style="font-size:9pt;">JALAN PROF. DR. ING. BJ. HABIBIE DESA MOUTONG KEC. TILONGKABILA KAB. BONE BOLANGO</div>
        <div style="font-size:9pt;">TELP/FAX (0435) 824582 E-mail : kab-bonebolango@atr.go.id</div>
    </div>
    <div style="border-bottom:3px solid #000;margin:2px 0 16px;"></div>

    {{-- ===== JUDUL & NOMOR ===== --}}
    <p style="text-align:center;font-weight:bold;text-decoration:underline;text-transform:uppercase;margin:0;">
        Risalah Panitia Pemeriksaan Tanah "A"
    </p>
    <p style="text-align:center;font-weight:bold;margin:2px 0 14px;">Nomor : {{ $r->nomor_risalah ?: '…………………' }}</p>

    {{-- ===== KALIMAT PEMBUKA & DAFTAR PANITIA ===== --}}
    <p style="margin:0 0 8px;">
        Pada hari ini
        @if ($r->tgl_risalah)
            {{ Terbilang::tanggal($r->tgl_risalah) }} ({{ $r->tgl_risalah->format('d/m/Y') }}),
        @else
            …………………, tanggal ………… bulan ………… tahun …………,
        @endif
        kami yang bertandatangan di bawah ini :
    </p>

    <table style="width:100%;border-collapse:collapse;margin:0 0 10px;">
        @foreach ($r->panitia as $i => $anggota)
            <tr>
                <td style="{{ $numItem }}">{{ $i + 1 }}.</td>
                <td style="width:42%;vertical-align:top;"><strong>{{ $anggota->nama }}</strong></td>
                <td style="{{ $cell }}">{{ $anggota->jabatan }}{{ $anggota->jabatan ? ', ' : '' }}{{ $anggota->peran->frasa() }}{{ $loop->last ? '' : ';' }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:0 0 14px;">
        Secara bersama-sama merupakan panitia "A" sebagaimana dimaksud dalam
        {{ RisalahDefaults::DASAR_PANITIA }}
        @if ($r->nomor_sk_panitia)
            Jo. Surat Keputusan Kepala Kantor Pertanahan Kabupaten Bone Bolango Nomor {{ $r->nomor_sk_panitia }}@if ($r->tgl_sk_panitia) Tanggal {{ $tgl($r->tgl_sk_panitia) }}@endif,
        @endif
        dan {{ $jumlahPanitia }} ({{ Terbilang::make($jumlahPanitia) }}) orang anggota telah datang di lokasi tanah yang dimohon
        terletak di {{ $letakSingkat }}, untuk mengadakan pemeriksaan atas Permohonan {{ $jenisHak }} atas Nama :
        <strong>{{ $namaPemohon }}</strong>.
    </p>

    {{-- ===== I. URAIAN MENGENAI PEMOHON ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">I.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">URAIAN MENGENAI PEMOHON</span>
                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                    <tr>
                        <td style="{{ $roman2 }}">1.</td>
                        <td style="{{ $cell }}" colspan="3">Perorangan</td>
                    </tr>
                    <tr><td></td><td style="{{ $letter }}">a.</td><td style="{{ $lbl }}">Nama Pemohon</td><td>: <strong>{{ $namaPemohon }}</strong></td></tr>
                    <tr><td></td><td style="{{ $letter }}">b.</td><td style="{{ $lbl }}">Domisili/Tempat Kedudukan</td><td>: {{ $domisiliPemohon ?: '…' }}</td></tr>
                    <tr><td></td><td style="{{ $letter }}">c.</td><td style="{{ $lbl }}">Kewarganegaraan</td><td>: Indonesia</td></tr>
                    <tr><td></td><td style="{{ $letter }}">d.</td><td style="{{ $lbl }}">NIK</td><td>: {{ $pemohon?->nik ?: '…' }}</td></tr>
                    <tr><td></td><td style="{{ $letter }}">e.</td><td style="{{ $lbl }}">Pekerjaan</td><td>: {{ $pemohon?->pekerjaan ?: '…' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== II. URAIAN MENGENAI TANAH YANG DIMOHON ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">II.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">URAIAN MENGENAI TANAH YANG DIMOHON</span>
                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                    <tr><td style="{{ $roman2 }}">1.</td><td style="{{ $cell }}" colspan="3">Letak</td></tr>
                    <tr><td></td><td style="{{ $letter }}">a.</td><td style="{{ $lbl }}">Jalan</td><td>: -</td></tr>
                    <tr><td></td><td style="{{ $letter }}">b.</td><td style="{{ $lbl }}">Desa</td><td>: {{ $desa?->nama ?: '…' }}</td></tr>
                    <tr><td></td><td style="{{ $letter }}">c.</td><td style="{{ $lbl }}">Kecamatan</td><td>: {{ $kec?->nama ?: '…' }}</td></tr>
                    <tr><td></td><td style="{{ $letter }}">d.</td><td style="{{ $lbl }}">Kabupaten</td><td>: {{ $kab?->nama ?: '…' }}</td></tr>
                    <tr><td></td><td style="{{ $letter }}">e.</td><td style="{{ $lbl }}">Provinsi</td><td>: {{ $prov?->nama ?: '…' }}</td></tr>
                    <tr>
                        <td style="{{ $roman2 }}">2.</td>
                        <td style="{{ $cell }}" colspan="2">Luas</td>
                        <td style="{{ $cell }}">: {{ $luasFmt ?? '…' }} m&sup2;@if ($luasInt) ({{ Str::ucfirst(Terbilang::make($luasInt)) }} meter persegi)@endif</td>
                    </tr>
                    <tr>
                        <td style="{{ $roman2 }}">3.</td>
                        <td style="{{ $cell }}" colspan="2">Peta Bidang Tanah</td>
                        <td style="{{ $cell }}">: No. {{ $t?->nomor_pbt ?: '…' }}@if ($t?->tanggal_pbt), Tanggal {{ $tgl($t->tanggal_pbt) }}@endif@if ($t?->nib), NIB. {{ $t->nib }}@endif</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== III. URAIAN ATAS HAK YANG AKAN DITETAPKAN ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">III.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">URAIAN ATAS HAK YANG AKAN DITETAPKAN</span>
                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                    <tr><td style="{{ $roman2 }}">1.</td><td style="{{ $lbl }}">Jenis hak</td><td>: {{ $jenisHak }}</td></tr>
                    <tr><td style="{{ $roman2 }}">2.</td><td style="{{ $lbl }}">Jangka Waktu</td><td>: {{ $r->jangka_waktu ?: '-' }}</td></tr>
                    <tr><td style="{{ $roman2 }}">3.</td><td style="{{ $cell }}" colspan="2">Penggunaan :</td></tr>
                    <tr><td></td><td style="{{ $letter }}">a.</td><td style="{{ $lbl }}">Penggunaan saat ini</td><td>: {{ $t?->penggunaan_tanah ?: '…' }}</td></tr>
                    <tr><td></td><td style="{{ $letter }}">b.</td><td style="{{ $lbl }}">Rencana penggunaan</td><td>: {{ $t?->rencana_penggunaan_rtrw ?: ($t?->penggunaan_tanah ?: '…') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== IV. DATA PENDUKUNG (TERLAMPIR) ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">IV.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">DATA PENDUKUNG (TERLAMPIR)</span>
                @if (count($dataPendukung) || $r->tgl_bap)
                    <table style="width:100%;border-collapse:collapse;margin-top:4px;">
                        @foreach ($dataPendukung as $i => $dp)
                            <tr>
                                <td style="{{ $numItem }}">{{ $i + 1 }}.</td>
                                <td style="{{ $cell }}white-space:pre-line;">{{ $dp }};</td>
                            </tr>
                        @endforeach
                        @if ($r->tgl_bap)
                            <tr>
                                <td style="{{ $numItem }}">{{ count($dataPendukung) + 1 }}.</td>
                                <td style="{{ $cell }}">Berita Acara Pemeriksaan Lapang tanggal {{ $tgl($r->tgl_bap) }} oleh anggota yang telah ditandatangani.</td>
                            </tr>
                        @endif
                    </table>
                @else
                    <p style="margin:2px 0 0;">…………………………………………………………………………</p>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== V. DASAR HUKUM ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">V.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">DASAR HUKUM</span>
                @if (count($dasarHukum) || $r->nomor_sk_panitia)
                    <table style="width:100%;border-collapse:collapse;margin-top:4px;">
                        @foreach ($dasarHukum as $i => $ds)
                            <tr>
                                <td style="{{ $numItem }}">{{ $i + 1 }}.</td>
                                <td style="{{ $cell }}">{{ $ds }};</td>
                            </tr>
                        @endforeach
                        @if ($r->nomor_sk_panitia)
                            <tr>
                                <td style="{{ $numItem }}">{{ count($dasarHukum) + 1 }}.</td>
                                <td style="{{ $cell }}">
                                    Keputusan Kepala Kantor Pertanahan Kabupaten Bone Bolango Nomor {{ $r->nomor_sk_panitia }}@if ($r->tgl_sk_panitia) Tanggal {{ $tgl($r->tgl_sk_panitia) }}@endif
                                    tentang Susunan Tim Panitia Pemeriksaan Tanah "A" (Panitia "A") Kantor Pertanahan Kabupaten Bone Bolango.
                                </td>
                            </tr>
                        @endif
                    </table>
                @else
                    <p style="margin:2px 0 0;">…………………………………………………………………………</p>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== VI. URAIAN DAN TELAAH ATAS SUBYEK HAK ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">VI.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">URAIAN DAN TELAAH ATAS SUBYEK HAK</span>
                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                    <tr><td style="{{ $roman2 }}">1.</td><td style="{{ $cell }}"><span style="text-decoration:underline;">Perorangan</span></td></tr>
                    <tr>
                        <td></td>
                        <td style="{{ $cell }}">
                            <table style="width:100%;border-collapse:collapse;">
                                <tr>
                                    <td style="{{ $letter }}">a.</td>
                                    <td style="{{ $cell }}">
                                        <strong>{{ $namaPemohon }}</strong> Kewarganegaraan Indonesia, bertempat tinggal di
                                        {{ $domisiliPemohon ?: '…' }}, pekerjaan {{ $pemohon?->pekerjaan ?: '…' }}.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="{{ $letter }}">b.</td>
                                    <td style="{{ $cell }}padding-top:4px;">
                                        Berdasarkan Undang-Undang Nomor 12 Tahun 2006 tentang Kewarganegaraan Republik Indonesia dan yang
                                        bersangkutan telah memiliki Nomor Induk Kependudukan sebagaimana diatur dalam Undang-Undang Republik
                                        Indonesia Nomor 23 Tahun 2006 tentang Administrasi Kependudukan, bahwa pemohon memenuhi syarat sebagai
                                        subyek hak sesuai dengan Undang-Undang Nomor 5 Tahun 1960 tentang Peraturan Dasar Pokok-Pokok Agraria
                                        Pasal 42 ayat (1) dan Peraturan Menteri Agraria Nomor 18 Tahun 2021 tentang Tata Cara Penetapan Hak
                                        Pengelolaan dan Hak Atas Tanah Pasal 52 ayat (1) huruf a.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== VII. URAIAN DAN TELAAH ATAS OBYEK HAK ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">VII.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">URAIAN DAN TELAAH ATAS OBYEK HAK</span>

                {{-- 1. Data Yuridis --}}
                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                    <tr><td style="{{ $roman2 }}">1.</td><td style="{{ $cell }}"><span style="{{ $heading }}">Data Yuridis:</span></td></tr>
                </table>

                <table style="width:100%;border-collapse:collapse;">
                    {{-- a. Riwayat Tanah --}}
                    <tr>
                        <td style="{{ $letter }}">a.</td>
                        <td style="{{ $cell }}">
                            <span style="text-decoration:underline;">Riwayat Tanah:</span>
                            <table style="width:100%;border-collapse:collapse;">
                                <tr>
                                    <td style="width:16px;{{ $cell }}">-</td>
                                    <td style="{{ $cell }}">
                                        Bahwa tanah yang dimohon adalah Tanah Negara yang seluruhnya seluas {{ $luasFmt ?? '…' }} m&sup2;@if ($luasInt) ({{ Str::ucfirst(Terbilang::make($luasInt)) }} meter persegi)@endif
                                        yang terletak di {{ $letakSingkat }}.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {{-- b. Riwayat Perolehan Tanah --}}
                    <tr>
                        <td style="{{ $letter }}">b.</td>
                        <td style="{{ $cell }}padding-top:4px;">
                            <span style="text-decoration:underline;">Riwayat Perolehan Tanah :</span>
                            <div style="margin-top:2px;">Bahwa {{ $namaPemohon }} (Pemohon) memperoleh tanah dengan riwayat sebagai berikut:</div>
                            @if (count($poinRiwayat))
                                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                                    @foreach ($poinRiwayat as $poin)
                                        <tr>
                                            <td style="width:18px;{{ $cell }}">&bull;</td>
                                            <td style="{{ $cell }}white-space:pre-line;">{{ $poin }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <p style="margin:2px 0 0;">…………………………………………………………………………</p>
                            @endif
                        </td>
                    </tr>
                    {{-- c. Riwayat Hak Atas Tanah --}}
                    <tr>
                        <td style="{{ $letter }}">c.</td>
                        <td style="{{ $cell }}padding-top:4px;">
                            <span style="text-decoration:underline;">Riwayat Hak Atas Tanah :</span>
                            <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                                <tr>
                                    <td style="width:18px;{{ $cell }}">&bull;</td>
                                    <td style="{{ $cell }}">
                                        Bahwa status tanah yang dimohon adalah Tanah Negara yang belum dilekati dengan sesuatu Hak yang
                                        seluruhnya seluas {{ $luasFmt ?? '…' }} m&sup2;@if ($luasInt) ({{ Str::ucfirst(Terbilang::make($luasInt)) }} meter persegi)@endif
                                        yang terletak di {{ $letakSingkat }}, yang kemudian telah dikuasai secara fisik secara terus menerus
                                        oleh {{ $namaPemohon }} selaku pemohon.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:18px;{{ $cell }}">&bull;</td>
                                    <td style="{{ $cell }}">
                                        Bahwa tanah tersebut telah dimohonkan untuk diberikan {{ $jenisHak }} pada Kantor Pertanahan Kabupaten
                                        Bone Bolango sesuai Surat permohonan hak yang dibuat dan ditandatangani oleh {{ $namaPemohon }}.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {{-- d. Pemanfaatan, Penggunaan dan Penguasaan Tanah --}}
                    <tr>
                        <td style="{{ $letter }}">d.</td>
                        <td style="{{ $cell }}padding-top:4px;">
                            <span style="text-decoration:underline;">Pemanfaatan, Penggunaan dan Penguasaan Tanah:</span>
                            <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                                <tr>
                                    <td style="{{ $roman2 }}">i.</td>
                                    <td style="{{ $cell }}">
                                        <span style="text-decoration:underline;">Pemanfaatan</span><br>
                                        Bahwa bidang tanah tersebut saat ini dipergunakan untuk {{ $penggunaan }};
                                    </td>
                                </tr>
                                <tr>
                                    <td style="{{ $roman2 }}">ii.</td>
                                    <td style="{{ $cell }}padding-top:2px;">
                                        <span style="text-decoration:underline;">Penggunaan</span><br>
                                        Bahwa penggunaan dan pemanfaatan bidang tanah tersebut {{ $penggunaan }}
                                        @if ($t?->tgl_peta_analisis) berdasarkan Peta Analisis Penatagunaan Tanah tanggal {{ $tgl($t->tgl_peta_analisis) }} yang ditandatangani oleh Kepala Seksi Penataan dan Pemberdayaan Kantor Pertanahan Kabupaten Bone Bolango @endif
                                        bidang tanah yang dimohon berada dalam {{ $kawasanRtrw }} sehingga bidang tanah tersebut sesuai
                                        dengan {{ $perdaRtrw }}. Sehingga penggunaan tanahnya untuk {{ $penggunaan }}.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="{{ $roman2 }}">iii.</td>
                                    <td style="{{ $cell }}padding-top:2px;">
                                        <span style="text-decoration:underline;">Penguasaan Tanah</span><br>
                                        Bahwa bidang tanah yang dimohon sampai dengan saat ini dikuasai terus menerus oleh pemohon, belum
                                        bersertipikat dan tidak dijadikan ataupun menjadi jaminan sesuatu hutang serta tidak dalam sengketa.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- 2. Data Fisik --}}
                <table style="width:100%;border-collapse:collapse;margin-top:6px;">
                    <tr><td style="{{ $roman2 }}">2.</td><td style="{{ $cell }}"><span style="{{ $heading }}">Data Fisik:</span></td></tr>
                </table>

                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="{{ $letter }}">a.</td>
                        <td style="{{ $cell }}">
                            Bahwa atas bidang tanah yang dimohon {{ $jenisHak }} oleh {{ $namaPemohon }} telah dilaksanakan pengukuran
                            secara kadastral oleh Seksi Survei dan Pemetaan Kantor Pertanahan Kabupaten Bone Bolango seluas
                            {{ $luasFmt ?? '…' }} m&sup2;@if ($luasInt) ({{ Str::ucfirst(Terbilang::make($luasInt)) }} meter persegi)@endif
                            sesuai Peta Bidang Tanah Nomor {{ $t?->nomor_pbt ?: '…' }}@if ($t?->tanggal_pbt) tanggal {{ $tgl($t->tanggal_pbt) }}@endif@if ($t?->nib), NIB. {{ $t->nib }}@endif
                            terletak di {{ $letakSingkat }}. Dengan batas-batas bidang tanah tersebut:
                            <table style="border-collapse:collapse;margin:2px 0 0;">
                                <tr><td style="width:80px;{{ $cell }}">Utara</td><td style="{{ $sep }}">:</td><td>berbatasan dengan {{ $t?->batas_utara ?: '…' }}</td></tr>
                                <tr><td style="{{ $cell }}">Timur</td><td style="{{ $sep }}">:</td><td>berbatasan dengan {{ $t?->batas_timur ?: '…' }}</td></tr>
                                <tr><td style="{{ $cell }}">Selatan</td><td style="{{ $sep }}">:</td><td>berbatasan dengan {{ $t?->batas_selatan ?: '…' }}</td></tr>
                                <tr><td style="{{ $cell }}">Barat</td><td style="{{ $sep }}">:</td><td>berbatasan dengan {{ $t?->batas_barat ?: '…' }}</td></tr>
                            </table>
                        </td>
                    </tr>
                    @if ($selisihInt)
                        <tr>
                            <td style="{{ $letter }}">b.</td>
                            <td style="{{ $cell }}padding-top:4px;">
                                Bahwa terdapat selisih seluas {{ number_format($selisihInt, 0, ',', '.') }} m&sup2; ({{ Str::ucfirst(Terbilang::make($selisihInt)) }} meter persegi)
                                antara jumlah keseluruhan luas tanah yang tercantum pada bukti surat tanah seluas {{ $luasSuratFmt }} m&sup2; ({{ Str::ucfirst(Terbilang::make($luasSuratInt)) }} meter persegi)
                                dengan hasil pengukuran kadastral seluas {{ $luasFmt }} m&sup2; ({{ Str::ucfirst(Terbilang::make($luasInt)) }} meter persegi). Bahwa terhadap selisih luas tersebut
                                tidak terdapat keberatan dari pemilik tanah.
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="{{ $letter }}">{{ $selisihInt ? 'c.' : 'b.' }}</td>
                        <td style="{{ $cell }}padding-top:4px;">Bahwa bidang tanah yang dimohon saat turun lapangan dipergunakan untuk {{ $penggunaan }};</td>
                    </tr>
                    <tr>
                        <td style="{{ $letter }}">{{ $selisihInt ? 'd.' : 'c.' }}</td>
                        <td style="{{ $cell }}padding-top:4px;">Bahwa bidang tanah yang dimohon berada di luar kawasan hutan;</td>
                    </tr>
                    <tr>
                        <td style="{{ $letter }}">{{ $selisihInt ? 'e.' : 'd.' }}</td>
                        <td style="{{ $cell }}padding-top:4px;">Bahwa pada saat kami melakukan Pemeriksaan Lapang tidak ada yang mengajukan keberatan atau merasa berkeberatan terhadap Permohonan Hak dimaksud;</td>
                    </tr>
                    <tr>
                        <td style="{{ $letter }}">{{ $selisihInt ? 'f.' : 'e.' }}</td>
                        <td style="{{ $cell }}padding-top:4px;">Bahwa semua data fisik yang diuraikan di atas telah memenuhi persyaratan permohonan {{ $jenisHak }} yang diajukan oleh {{ $namaPemohon }}.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== VIII. ANALISIS HAK ATAS TANAH YANG AKAN DITETAPKAN ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">VIII.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">ANALISIS HAK ATAS TANAH YANG AKAN DITETAPKAN</span>
                <p style="margin:2px 0 0;">Bahwa berdasarkan data yuridis dan data fisik dapat dianalisa sebagai berikut :</p>
                <table style="width:100%;border-collapse:collapse;margin-top:2px;">
                    <tr>
                        <td style="{{ $numItem }}">1.</td>
                        <td style="{{ $cell }}">
                            Bahwa {{ $namaPemohon }} Kewarganegaraan Indonesia, bertempat tinggal di {{ $domisiliPemohon ?: '…' }},
                            pekerjaan {{ $pemohon?->pekerjaan ?: '…' }}, memenuhi syarat sebagai subyek hak sesuai dengan Undang-Undang
                            Nomor 5 Tahun 1960 Pasal 42 ayat (1) dan Peraturan Menteri Agraria Nomor 18 Tahun 2021 Pasal 52 ayat (1) huruf a;
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $numItem }}">2.</td>
                        <td style="{{ $cell }}">
                            Bahwa bidang tanah yang dimohon telah dilaksanakan pengukuran secara kadastral seluas {{ $luasFmt ?? '…' }} m&sup2;
                            sesuai Peta Bidang Tanah Nomor {{ $t?->nomor_pbt ?: '…' }}@if ($t?->tanggal_pbt) tanggal {{ $tgl($t->tanggal_pbt) }}@endif@if ($t?->nib), NIB. {{ $t->nib }}@endif
                            terletak di {{ $letakSingkat }};
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $numItem }}">3.</td>
                        <td style="{{ $cell }}">
                            Bahwa penggunaan dan pemanfaatan bidang tanah tersebut {{ $penggunaan }} berada dalam {{ $kawasanRtrw }},
                            sehingga sesuai dengan {{ $perdaRtrw }};
                        </td>
                    </tr>
                    <tr><td style="{{ $numItem }}">4.</td><td style="{{ $cell }}">Bahwa bidang tanah yang dimohon berada di luar kawasan hutan;</td></tr>
                    <tr><td style="{{ $numItem }}">5.</td><td style="{{ $cell }}">Bahwa pada saat kami melakukan Pemeriksaan Lapang, tidak terdapat keberatan, sengketa, konflik atau perkara dengan pihak lain terhadap Permohonan Hak dimaksud;</td></tr>
                    <tr>
                        <td style="{{ $numItem }}">6.</td>
                        <td style="{{ $cell }}">
                            Bahwa berdasarkan hal-hal tersebut di atas, permohonan {{ $jenisHak }} yang dimohonkan oleh {{ $namaPemohon }}
                            telah memenuhi ketentuan peraturan perundang-undangan dan kebijakan, sehingga dapat dipertimbangkan untuk dikabulkan.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== IX. PENDAPAT ANGGOTA PANITIA ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">IX.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">PENDAPAT ANGGOTA PANITIA</span>
                <table style="width:100%;border-collapse:collapse;margin-top:4px;">
                    @foreach ($r->panitia as $i => $anggota)
                        <tr>
                            <td style="{{ $numItem }}">{{ $i + 1 }}.</td>
                            <td style="{{ $cell }}">
                                <strong>{{ $anggota->nama }}</strong>, {{ Str::after($anggota->peran->frasa(), 'sebagai ') ? 'sebagai '.Str::after($anggota->peran->frasa(), 'sebagai ') : $anggota->peran->label() }}:
                                <div style="white-space:pre-line;">{{ $anggota->pivot->pendapat ?: '…………………………………………………………………………' }}</div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== X. KESIMPULAN ===== --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr>
            <td style="{{ $romNum }}">X.</td>
            <td style="{{ $cell }}">
                <span style="{{ $heading }}">KESIMPULAN</span>
                <table style="width:100%;border-collapse:collapse;margin-top:4px;">
                    <tr>
                        <td style="{{ $numItem }}">1.</td>
                        <td style="{{ $cell }}">Bahwa aspek Yuridis terkait permohonan {{ $jenisHak }} atas {{ $namaPemohon }} telah memenuhi dan sesuai dengan peraturan perundang-undangan yang berlaku;</td>
                    </tr>
                    <tr>
                        <td style="{{ $numItem }}">2.</td>
                        <td style="{{ $cell }}">
                            Bahwa tanah yang dimohon seluas {{ $luasFmt ?? '…' }} m&sup2;@if ($luasInt) ({{ Str::ucfirst(Terbilang::make($luasInt)) }} meter persegi)@endif
                            sesuai Peta Bidang Tanah Nomor {{ $t?->nomor_pbt ?: '…' }}@if ($t?->tanggal_pbt) tanggal {{ $tgl($t->tanggal_pbt) }}@endif@if ($t?->nib), NIB. {{ $t->nib }}@endif terletak di {{ $letakSingkat }};
                        </td>
                    </tr>
                    <tr><td style="{{ $numItem }}">3.</td><td style="{{ $cell }}">Bahwa pada saat kami melakukan Pemeriksaan Lapang tidak ada yang mengajukan keberatan atau merasa berkeberatan terhadap Permohonan Hak dimaksud;</td></tr>
                    <tr>
                        <td style="{{ $numItem }}">4.</td>
                        <td style="{{ $cell }}">Bahwa penggunaan dan pemanfaatan bidang tanah tersebut {{ $penggunaan }} berada dalam {{ $kawasanRtrw }}, sehingga sesuai dengan {{ $perdaRtrw }}. Sehingga penggunaan tanahnya untuk {{ $penggunaan }};</td>
                    </tr>
                    <tr>
                        <td style="{{ $numItem }}">5.</td>
                        <td style="{{ $cell }}">
                            Berdasarkan uraian tersebut di atas (poin 1 sampai dengan poin 4), permohonan pemberian {{ $jenisHak }} yang
                            dimohonkan oleh {{ $namaPemohon }} atas tanah yang terletak di {{ $letakSingkat }}
                            <strong>dapat dipertimbangkan untuk diberikan {{ $jenisHak }}</strong>.
                        </td>
                    </tr>
                </table>
                @if ($r->kesimpulan_tambahan)
                    <p style="margin:6px 0 0;white-space:pre-line;">{{ $r->kesimpulan_tambahan }}</p>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== TANDA TANGAN PANITIA ===== --}}
    @php $lokasiTtd = $kab?->nama ?? 'Bone Bolango'; @endphp
    <p style="text-align:right;margin:18px 0 4px;">
        {{ $lokasiTtd }}, {{ $r->tgl_risalah ? $tgl($r->tgl_risalah) : '…………………' }}
    </p>
    <p style="text-align:center;font-weight:bold;margin:0 0 8px;">PANITIA PEMERIKSAAN TANAH "A"</p>

    <table style="width:100%;border-collapse:collapse;margin-top:6px;">
        @foreach ($r->panitia->chunk(2) as $baris)
            <tr>
                @foreach ($baris as $anggota)
                    <td style="width:50%;vertical-align:top;padding-bottom:34px;text-align:center;">
                        <div>{{ Str::ucfirst(Str::after($anggota->peran->frasa(), 'sebagai ')) }},</div>
                        <div style="height:64px;"></div>
                        <div style="font-weight:bold;text-decoration:underline;">{{ $anggota->nama }}</div>
                        @if ($anggota->nip)<div>NIP. {{ $anggota->nip }}</div>@endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</div>
