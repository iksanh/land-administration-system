<div class="flex flex-col gap-6">
    <x-flash />
    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Master Wilayah</h2>
        <p class="text-sm text-gray-500 mt-1">Telusuri dan kelola data wilayah: provinsi → kabupaten → kecamatan → desa.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- PROVINSI --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="font-medium text-gray-800 text-sm">Provinsi</span>
                <span class="text-xs text-gray-400">{{ $provinsiList->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto flex-1">
                @forelse ($provinsiList as $p)
                    <li>
                        <button wire:click="selectProvinsi('{{ $p->id }}')"
                            class="w-full text-left px-4 py-2 text-sm flex justify-between items-center hover:bg-gray-50 {{ $selProvinsi === $p->id ? 'bg-[#e6f4ff] text-[#1677ff] font-medium' : 'text-gray-700' }}">
                            <span>{{ $p->nama }}</span>
                            <span class="text-[10px] font-mono text-gray-400">{{ $p->id }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-400 text-sm">Tidak ada data.</li>
                @endforelse
            </ul>
            <form wire:submit="addProvinsi" class="p-3 border-t border-gray-100 flex flex-col gap-2">
                <input type="text" wire:model="provId" placeholder="Kode (mis. 75)" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                @error('provId') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                <input type="text" wire:model="provNama" placeholder="Nama provinsi" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                @error('provNama') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-2 py-1.5 text-xs font-medium">+ Tambah</button>
            </form>
        </div>

        {{-- KABUPATEN --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col {{ $selProvinsi ? '' : 'opacity-60' }}">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="font-medium text-gray-800 text-sm">Kabupaten/Kota</span>
                <span class="text-xs text-gray-400">{{ $kabupatenList->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto flex-1">
                @forelse ($kabupatenList as $k)
                    <li>
                        <button wire:click="selectKabupaten('{{ $k->id }}')"
                            class="w-full text-left px-4 py-2 text-sm flex justify-between items-center hover:bg-gray-50 {{ $selKabupaten === $k->id ? 'bg-[#e6f4ff] text-[#1677ff] font-medium' : 'text-gray-700' }}">
                            <span>{{ $k->nama }}</span>
                            <span class="text-[10px] font-mono text-gray-400">{{ $k->id }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-400 text-sm">{{ $selProvinsi ? 'Belum ada kabupaten.' : 'Pilih provinsi.' }}</li>
                @endforelse
            </ul>
            @if ($selProvinsi)
                <form wire:submit="addKabupaten" class="p-3 border-t border-gray-100 flex flex-col gap-2">
                    <input type="text" wire:model="kabId" placeholder="Kode (mis. 7501)" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    @error('kabId') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    <input type="text" wire:model="kabNama" placeholder="Nama kabupaten/kota" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    @error('kabNama') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-2 py-1.5 text-xs font-medium">+ Tambah</button>
                </form>
            @endif
        </div>

        {{-- KECAMATAN --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col {{ $selKabupaten ? '' : 'opacity-60' }}">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="font-medium text-gray-800 text-sm">Kecamatan</span>
                <span class="text-xs text-gray-400">{{ $kecamatanList->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto flex-1">
                @forelse ($kecamatanList as $k)
                    <li>
                        <button wire:click="selectKecamatan('{{ $k->id }}')"
                            class="w-full text-left px-4 py-2 text-sm flex justify-between items-center hover:bg-gray-50 {{ $selKecamatan === $k->id ? 'bg-[#e6f4ff] text-[#1677ff] font-medium' : 'text-gray-700' }}">
                            <span>{{ $k->nama }}</span>
                            <span class="text-[10px] font-mono text-gray-400">{{ $k->id }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-400 text-sm">{{ $selKabupaten ? 'Belum ada kecamatan.' : 'Pilih kabupaten.' }}</li>
                @endforelse
            </ul>
            @if ($selKabupaten)
                <form wire:submit="addKecamatan" class="p-3 border-t border-gray-100 flex flex-col gap-2">
                    <input type="text" wire:model="kecId" placeholder="Kode (mis. 750101)" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    @error('kecId') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    <input type="text" wire:model="kecNama" placeholder="Nama kecamatan" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    @error('kecNama') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-2 py-1.5 text-xs font-medium">+ Tambah</button>
                </form>
            @endif
        </div>

        {{-- DESA --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col {{ $selKecamatan ? '' : 'opacity-60' }}">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="font-medium text-gray-800 text-sm">Desa/Kelurahan</span>
                <span class="text-xs text-gray-400">{{ $desaList->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto flex-1">
                @forelse ($desaList as $d)
                    <li class="px-4 py-2 text-sm flex justify-between items-center text-gray-700">
                        <span>
                            {{ $d->nama }}
                            @if ($d->nama_kepala_desa)
                                <span class="block text-[10px] text-gray-400">Kades: {{ $d->nama_kepala_desa }}</span>
                            @endif
                        </span>
                        <span class="text-[10px] font-mono text-gray-400">{{ $d->id }}</span>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-400 text-sm">{{ $selKecamatan ? 'Belum ada desa.' : 'Pilih kecamatan.' }}</li>
                @endforelse
            </ul>
            @if ($selKecamatan)
                <form wire:submit="addDesa" class="p-3 border-t border-gray-100 flex flex-col gap-2">
                    <input type="text" wire:model="desaId" placeholder="Kode (10 digit)" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    @error('desaId') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    <input type="text" wire:model="desaNama" placeholder="Nama desa/kelurahan" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    @error('desaNama') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    <input type="text" wire:model="desaKepala" placeholder="Nama kepala desa (opsional)" class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                    <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-2 py-1.5 text-xs font-medium">+ Tambah</button>
                </form>
            @endif
        </div>
    </div>
</div>
