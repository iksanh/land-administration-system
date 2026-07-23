<div class="flex flex-col gap-6">
    {{-- Greeting --}}
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Selamat datang, {{ auth()->user()->name }} 👋</h2>
        <p class="text-sm text-gray-500 mt-1">Ringkasan sistem dan langkah pengelolaan permohonan pertanahan.</p>
    </div>

    {{-- Stat cards --}}
    @php
        $cards = [
            ['label' => 'Total Permohonan', 'value' => $totalPermohonan, 'route' => 'permohonan', 'color' => 'text-[#1677ff]'],
            ['label' => 'Data Pemohon', 'value' => $totalPemohon, 'route' => 'pemohon', 'color' => 'text-[#52c41a]'],
            ['label' => 'Bidang Tanah', 'value' => $totalTanah, 'route' => 'tanah', 'color' => 'text-[#722ed1]'],
            ['label' => 'Layanan Aktif', 'value' => $totalLayanan, 'route' => 'layanan', 'color' => 'text-[#d46b08]'],
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($cards as $c)
            <a href="{{ route($c['route']) }}" wire:navigate class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-[#91caff] transition">
                <p class="text-sm font-medium text-gray-500">{{ $c['label'] }}</p>
                <p class="text-3xl font-bold {{ $c['color'] }} mt-2">{{ $c['value'] }}</p>
                <p class="text-xs text-gray-400 mt-2">Lihat detail →</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Workflow guide --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800">Cara Menggunakan Aplikasi</h3>
            <p class="text-sm text-gray-500 mt-1 mb-5">Ikuti 4 langkah berikut untuk memproses sebuah permohonan.</p>
            @php
                $steps = [
                    ['Daftarkan Pemohon', 'Catat identitas pemohon (NIK, nama, domisili).', 'pemohon'],
                    ['Input Data Tanah', 'Catat bidang tanah: luas, penggunaan, dan batas-batas.', 'tanah'],
                    ['Buat Permohonan', 'Hubungkan pemohon, tanah, dan layanan; atur status.', 'permohonan'],
                    ['Periksa Berkas', 'Periksa kelengkapan berkas dan cetak lembar pemeriksaan.', 'pemeriksaan-berkas'],
                ];
            @endphp
            <ol class="flex flex-col gap-4">
                @foreach ($steps as $i => [$title, $desc, $r])
                    <li class="flex items-start gap-4">
                        <span class="w-8 h-8 rounded-full bg-[#e6f4ff] text-[#1677ff] flex items-center justify-center font-bold shrink-0">{{ $i + 1 }}</span>
                        <div class="flex-1">
                            <a href="{{ route($r) }}" wire:navigate class="font-medium text-gray-800 hover:text-[#1677ff]">{{ $title }}</a>
                            <p class="text-sm text-gray-500">{{ $desc }}</p>
                        </div>
                        <a href="{{ route($r) }}" wire:navigate class="text-xs font-medium text-[#1677ff] hover:text-[#0958d9] shrink-0 mt-1.5">Buka →</a>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Status breakdown --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800">Status Permohonan</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">Distribusi permohonan per status.</p>
            @if ($totalPermohonan === 0)
                <div class="text-center text-gray-400 text-sm py-8">
                    Belum ada permohonan.<br>
                    <a href="{{ route('permohonan') }}" wire:navigate class="text-[#1677ff] hover:text-[#0958d9] font-medium">Buat permohonan pertama →</a>
                </div>
            @else
                <div class="flex flex-col gap-2.5">
                    @foreach ($statuses as $s)
                        @php $count = $byStatus[$s->value] ?? 0; $pct = $totalPermohonan ? round($count / $totalPermohonan * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-600">{{ $s->label() }}</span>
                                <span class="text-gray-400 font-medium">{{ $count }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#1677ff] rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('pemohon') }}" wire:navigate class="px-4 py-2 rounded-md text-sm font-medium bg-[#1677ff] hover:bg-[#0958d9] text-white shadow-sm">+ Pemohon</a>
            <a href="{{ route('tanah') }}" wire:navigate class="px-4 py-2 rounded-md text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">+ Data Tanah</a>
            <a href="{{ route('permohonan') }}" wire:navigate class="px-4 py-2 rounded-md text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">+ Permohonan</a>
            <a href="{{ route('pemeriksaan-berkas') }}" wire:navigate class="px-4 py-2 rounded-md text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">Periksa Berkas</a>
        </div>
    </div>
</div>
