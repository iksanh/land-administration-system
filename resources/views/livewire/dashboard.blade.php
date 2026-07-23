<div class="flex flex-col gap-6">
    {{-- Greeting --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Selamat datang, {{ auth()->user()->name }} 👋</h2>
            <p class="text-sm text-gray-500 mt-1">Pantauan pekerjaan permohonan pertanahan — {{ now()->locale('id')->translatedFormat('l, d F Y') }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('permohonan') }}" wire:navigate class="px-4 py-2 rounded-md text-sm font-medium bg-[#1677ff] hover:bg-[#0958d9] text-white shadow-sm">+ Permohonan</a>
            <a href="{{ route('pemeriksaan-berkas') }}" wire:navigate class="px-4 py-2 rounded-md text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">Periksa Berkas</a>
        </div>
    </div>

    {{-- Stat cards: ringkasan alur --}}
    @php
        $cards = [
            ['label' => 'Total Permohonan', 'value' => $totalPermohonan, 'sub' => 'semua status', 'color' => 'text-gray-800'],
            ['label' => 'Dalam Proses', 'value' => $dalamProses, 'sub' => 'sedang berjalan di kantor', 'color' => 'text-[#1677ff]'],
            ['label' => 'Selesai', 'value' => $selesai, 'sub' => 'di Loket Penyerahan', 'color' => 'text-[#389e0d]'],
            ['label' => 'Pra-Daftar / Ditolak', 'value' => $praDaftar.' / '.$ditolak, 'sub' => 'belum jalan / dihentikan', 'color' => 'text-gray-600'],
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($cards as $c)
            <a href="{{ route('permohonan') }}" wire:navigate class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-[#91caff] transition">
                <p class="text-sm font-medium text-gray-500">{{ $c['label'] }}</p>
                <p class="text-3xl font-bold {{ $c['color'] }} mt-2">{{ $c['value'] }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $c['sub'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {{-- Perlu perhatian: berkas paling lama diam di tahapnya --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800">Perlu Perhatian</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">Berkas aktif paling lama di tahapnya saat ini.</p>
            @if ($perluPerhatian->isEmpty())
                <p class="text-sm text-gray-400 text-center py-8">Tidak ada berkas aktif. 🎉</p>
            @else
                <ul class="divide-y divide-gray-100 -my-2">
                    @foreach ($perluPerhatian as $p)
                        @php
                            $hari = (int) \Carbon\Carbon::parse($p->tahap_sejak)->diffInDays(now());
                            // Umur di tahap: ≤7 normal, 8–14 perlu dicek, >14 kritis.
                            [$hariCls, $hariIkon] = $hari > 14
                                ? ['text-[#cf1322] bg-[#fff1f0] border-[#ffa39e]', '⚠']
                                : ($hari > 7 ? ['text-[#d46b08] bg-[#fff7e6] border-[#ffd591]', '!'] : ['text-gray-500 bg-gray-50 border-gray-200', '·']);
                        @endphp
                        <li class="py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <span class="font-mono text-xs text-gray-500">{{ $p->nomor_registrasi }}</span>
                                    {{ $p->pemohon?->nama ?? 'Tanpa pemohon' }}
                                </p>
                                <span class="inline-flex mt-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span>
                            </div>
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold border {{ $hariCls }}"
                                title="Sejak {{ \Carbon\Carbon::parse($p->tahap_sejak)->locale('id')->translatedFormat('d F Y') }}">
                                {{ $hariIkon }} {{ $hari }} hari
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Sebaran tahapan --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800">Sebaran Tahapan</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">Jumlah permohonan di tiap tahap alur.</p>
            @if ($totalPermohonan === 0)
                <div class="text-center text-gray-400 text-sm py-8">
                    Belum ada permohonan.<br>
                    <a href="{{ route('permohonan') }}" wire:navigate class="text-[#1677ff] hover:text-[#0958d9] font-medium">Buat permohonan pertama →</a>
                </div>
            @else
                <div class="flex flex-col gap-2.5 max-h-80 overflow-y-auto pr-1">
                    @foreach ($statuses as $s)
                        @php $count = (int) ($byStatus[$s->value] ?? 0); @endphp
                        @continue($count === 0)
                        @php $pct = max(3, round($count / $totalPermohonan * 100)); @endphp
                        <div>
                            <div class="flex justify-between items-center gap-2 text-xs mb-1">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $s->badgeClass() }}">{{ $s->label() }}</span>
                                <span class="text-gray-500 font-semibold shrink-0">{{ $count }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#1677ff] rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Aktivitas terbaru --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800">Aktivitas Terbaru</h3>
                <a href="{{ route('audit-log') }}" wire:navigate class="text-xs font-medium text-[#1677ff] hover:text-[#0958d9]">Semua →</a>
            </div>
            <p class="text-sm text-gray-500 mt-1 mb-4">Perubahan status terakhir oleh petugas.</p>
            @if ($aktivitas->isEmpty())
                <p class="text-sm text-gray-400 text-center py-8">Belum ada aktivitas status.</p>
            @else
                <ul class="divide-y divide-gray-100 -my-2">
                    @foreach ($aktivitas as $log)
                        <li class="py-2.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono text-[11px] text-gray-500">{{ $log->permohonan?->nomor_registrasi ?? '—' }}</span>
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $log->status_baru->badgeClass() }}">{{ $log->status_baru->label() }}</span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ $log->petugas_id ? ($userNames[$log->petugas_id] ?? 'Tidak diketahui') : 'Sistem' }}
                                · {{ $log->created_at?->locale('id')->diffForHumans() }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {{-- Tren permohonan masuk 6 bulan --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800">Permohonan Masuk — 6 Bulan Terakhir</h3>
            <p class="text-sm text-gray-500 mt-1 mb-5">Jumlah permohonan baru per bulan.</p>
            <div class="flex items-end gap-3 sm:gap-6 h-36 border-b border-gray-200 px-2">
                @foreach ($bulanan as $b)
                    <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full" title="{{ $b['label'] }}: {{ $b['count'] }} permohonan">
                        <span class="text-[11px] font-semibold text-gray-500">{{ $b['count'] }}</span>
                        <div class="w-full max-w-10 rounded-t bg-[#1677ff] {{ $b['count'] === 0 ? 'opacity-20' : '' }}"
                            style="height: {{ $b['count'] === 0 ? 2 : max(6, round($b['count'] / $bulananMax * 100)) }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="flex gap-3 sm:gap-6 px-2 mt-1.5">
                @foreach ($bulanan as $b)
                    <span class="flex-1 text-center text-[11px] text-gray-400">{{ $b['label'] }}</span>
                @endforeach
            </div>
        </div>

        {{-- Data master + aksi cepat --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Data Master</h3>
            <ul class="divide-y divide-gray-100 -my-1 mb-4">
                @foreach ([
                    ['Pemohon', $totalPemohon, 'pemohon'],
                    ['Bidang Tanah', $totalTanah, 'tanah'],
                    ['Layanan Aktif', $totalLayanan, 'layanan'],
                ] as [$label, $val, $r])
                    <li>
                        <a href="{{ route($r) }}" wire:navigate class="flex items-center justify-between py-2.5 group">
                            <span class="text-sm text-gray-600 group-hover:text-[#1677ff]">{{ $label }}</span>
                            <span class="text-sm font-bold text-gray-800">{{ $val }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                <a href="{{ route('pemohon') }}" wire:navigate class="px-3 py-1.5 rounded-md text-xs font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">+ Pemohon</a>
                <a href="{{ route('tanah') }}" wire:navigate class="px-3 py-1.5 rounded-md text-xs font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">+ Data Tanah</a>
                <a href="{{ route('berita-acara') }}" wire:navigate class="px-3 py-1.5 rounded-md text-xs font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">Berita Acara</a>
                <a href="{{ route('risalah') }}" wire:navigate class="px-3 py-1.5 rounded-md text-xs font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">Risalah</a>
            </div>
        </div>
    </div>
</div>
