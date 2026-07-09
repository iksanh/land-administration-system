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
                    <li class="px-4 py-2 text-sm flex justify-between items-start gap-2 text-gray-700">
                        <span class="min-w-0">
                            <span class="block truncate">{{ $d->nama }}</span>
                            <span class="text-[10px] font-mono text-gray-400">{{ $d->id }}</span>
                        </span>
                        <button wire:click="manageKades('{{ $d->id }}')"
                            class="shrink-0 inline-flex items-center gap-1 text-[11px] rounded px-2 py-1 border border-gray-200 text-gray-600 hover:border-[#1677ff] hover:text-[#1677ff]">
                            👤 Kades
                            @if ($d->kepala_desa_aktif_count)
                                <span class="inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full bg-[#1677ff] text-white text-[9px] font-medium">{{ $d->kepala_desa_aktif_count }}</span>
                            @endif
                        </button>
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

    {{-- MODAL: Kelola Kepala Desa --}}
    @if ($kadesDesa)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="kades-modal">
            <div class="absolute inset-0 bg-black/40" wire:click="closeKades"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 border-b border-gray-200 flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">Kepala Desa — {{ $kadesDesa->nama }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Kepala desa <span class="font-medium text-[#1677ff]">aktif</span> otomatis ikut sebagai penandatangan Berita Acara &amp; Risalah.</p>
                    </div>
                    <button wire:click="closeKades" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                {{-- Daftar kepala desa --}}
                <ul class="divide-y divide-gray-100 overflow-y-auto flex-1">
                    @forelse ($kadesList as $kd)
                        <li class="px-5 py-3 flex items-center justify-between gap-3" wire:key="kades-{{ $kd->id }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-800 truncate">{{ $kd->nama }}</span>
                                    @if ($kd->is_active)
                                        <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-[#f6ffed] text-[#389e0d] border border-[#b7eb8f]">Aktif</span>
                                    @else
                                        <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 border border-gray-200">Non-aktif</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    {{ $kd->jabatan }}{{ $kd->periode ? ' — '.$kd->periode : '' }}{{ $kd->nip ? ' — NIP '.$kd->nip : '' }}
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-1">
                                <button wire:click="toggleKades('{{ $kd->id }}')"
                                    class="text-[11px] rounded px-2 py-1 border border-gray-200 text-gray-600 hover:border-[#1677ff] hover:text-[#1677ff]">
                                    {{ $kd->is_active ? 'Non-aktifkan' : 'Aktifkan' }}
                                </button>
                                <button wire:click="editKades('{{ $kd->id }}')"
                                    class="text-[11px] rounded px-2 py-1 border border-gray-200 text-gray-600 hover:border-[#1677ff] hover:text-[#1677ff]">Ubah</button>
                                <button wire:click="deleteKades('{{ $kd->id }}')" wire:confirm="Hapus kepala desa ini?"
                                    class="text-[11px] rounded px-2 py-1 border border-gray-200 text-red-500 hover:border-red-400">Hapus</button>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-gray-400">Belum ada kepala desa. Tambahkan di bawah.</li>
                    @endforelse
                </ul>

                {{-- Form tambah / ubah --}}
                <form wire:submit="saveKades" class="px-5 py-4 border-t border-gray-200 flex flex-col gap-2 bg-gray-50">
                    <div class="text-xs font-medium text-gray-600">{{ $kadesEditingId ? 'Ubah kepala desa' : 'Tambah kepala desa' }}</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div class="sm:col-span-2">
                            <input type="text" wire:model="kadesNama" placeholder="Nama kepala desa"
                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                            @error('kadesNama') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <input type="text" wire:model="kadesNip" placeholder="NIP (opsional)"
                            class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                        <input type="text" wire:model="kadesPeriode" placeholder="Periode (mis. 2019-2025)"
                            class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                        <input type="text" wire:model="kadesJabatan" placeholder="Jabatan"
                            class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                        <label class="flex items-center gap-2 text-sm text-gray-700 px-1">
                            <input type="checkbox" wire:model="kadesAktif" class="accent-[#1677ff]">
                            Aktif
                        </label>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-4 py-1.5 text-sm font-medium">
                            {{ $kadesEditingId ? 'Simpan Perubahan' : '+ Tambah' }}
                        </button>
                        @if ($kadesEditingId)
                            <button type="button" wire:click="resetKadesForm" class="rounded px-3 py-1.5 text-sm border border-gray-300 text-gray-600 hover:bg-white">Batal</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
