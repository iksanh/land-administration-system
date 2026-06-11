<div class="flex flex-col">
    <x-flash />
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Master Berkas</h2>
        <p class="text-sm text-gray-500 mt-1">Kelola daftar dokumen/berkas persyaratan beserta sub-berkas dan catatan pemeriksaan.</p>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 mb-6 flex flex-col gap-4">
        <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Edit Berkas' : 'Tambah Berkas' }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Nama Berkas <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nama" placeholder="Misal: KTP Pemohon"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Induk Berkas (opsional)</label>
                <select wire:model="parent_id"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    <option value="">— Tanpa induk (berkas utama) —</option>
                    @foreach ($parentOptions as $opt)
                        @if ($opt->id !== $editingId)
                            <option value="{{ $opt->id }}">{{ $opt->nama }}</option>
                        @endif
                    @endforeach
                </select>
                @error('parent_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium text-gray-700">Catatan Pemeriksaan</label>
            <textarea wire:model="catatan" rows="3" placeholder="Petunjuk pemeriksaan berkas (opsional)"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="is_mandatory" class="rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
            Wajib (mandatory)
        </label>
        <div class="flex gap-3 pt-3 border-t border-gray-200">
            <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                {{ $editingId ? 'Simpan Perubahan' : 'Tambah Berkas' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            @endif
        </div>
    </form>

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari nama berkas atau sub-berkas..." :count="$roots->count()" class="mb-4" />

    {{-- Tree --}}
    <ul class="space-y-3">
        @forelse ($roots as $item)
            <li>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm group hover:border-blue-200 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span>{{ $item->subBerkas->count() > 0 ? '📁' : '📄' }}</span>
                                <span class="font-medium text-gray-800">{{ $item->nama }}</span>
                                @if ($item->is_mandatory)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-600 border border-red-200">Wajib</span>
                                @endif
                            </div>
                            @if ($item->catatan)
                                <div class="mt-1 text-sm text-gray-500 whitespace-pre-line border-l-2 border-gray-200 pl-2">{{ $item->catatan }}</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button wire:click="edit('{{ $item->id }}')" class="text-sm font-medium text-[#1677ff] hover:text-[#0958d9]">Edit</button>
                            <button wire:click="delete('{{ $item->id }}')" wire:confirm="Hapus berkas '{{ $item->nama }}' beserta sub-berkasnya?" class="text-sm font-medium text-[#ff4d4f] hover:text-[#cf1322]">Hapus</button>
                        </div>
                    </div>

                    {{-- Children --}}
                    @if ($item->subBerkas->count() > 0)
                        <ul class="mt-3 ml-6 space-y-2 border-l border-gray-100 pl-4">
                            @foreach ($item->subBerkas->sortBy('nama') as $child)
                                <li class="flex items-start justify-between group/child">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span>📄</span>
                                            <span class="text-sm text-gray-700">{{ $child->nama }}</span>
                                            @if ($child->is_mandatory)
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-600 border border-red-200">Wajib</span>
                                            @endif
                                        </div>
                                        @if ($child->catatan)
                                            <div class="mt-0.5 text-xs text-gray-500 whitespace-pre-line border-l-2 border-gray-200 pl-2">{{ $child->catatan }}</div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3 opacity-0 group-hover/child:opacity-100 transition-opacity">
                                        <button wire:click="edit('{{ $child->id }}')" class="text-xs font-medium text-[#1677ff] hover:text-[#0958d9]">Edit</button>
                                        <button wire:click="delete('{{ $child->id }}')" wire:confirm="Hapus berkas '{{ $child->nama }}'?" class="text-xs font-medium text-[#ff4d4f] hover:text-[#cf1322]">Hapus</button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </li>
        @empty
            <li class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada berkas yang cocok dengan pencarian.' : 'Belum ada berkas.' }}</li>
        @endforelse
    </ul>
</div>
