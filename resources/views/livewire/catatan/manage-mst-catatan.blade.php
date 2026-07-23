<div class="flex flex-col gap-6">
    <x-flash />
    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Master Catatan</h2>
        <p class="text-sm text-gray-500 mt-1">Katalog catatan pemeriksaan yang dapat dipakai ulang (global atau khusus berkas).</p>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
        <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Edit Catatan' : 'Tambah Catatan' }}</h3>
        <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium text-gray-700">Teks Catatan <span class="text-red-500">*</span></label>
            <textarea wire:model="teks" rows="2" placeholder="Misal: Cek kesesuaian materai dan tanda tangan"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
            @error('teks') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Berkas Terkait</label>
                <select wire:model="berkas_item_id"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    <option value="">— Global (semua berkas) —</option>
                    @foreach ($berkasOptions as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }}</option>
                    @endforeach
                </select>
                @error('berkas_item_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 mt-7">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
                Aktif
            </label>
        </div>
        <div class="flex gap-3 pt-3 border-t border-gray-200">
            <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                {{ $editingId ? 'Simpan Perubahan' : 'Tambah Catatan' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            @endif
        </div>
    </form>

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari catatan..." :count="$catatanList->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3">Catatan</th>
                    <th class="px-4 py-3 w-56">Lingkup</th>
                    <th class="px-4 py-3 text-center w-24">Status</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($catatanList as $c)
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-4 py-3 text-gray-800 whitespace-pre-line">{{ $c->teks }}</td>
                        <td class="px-4 py-3">
                            @if ($c->berkas_item_id)
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-[#e6f4ff] text-[#1677ff] border border-[#91caff]">{{ $c->berkasItem?->nama ?? 'Berkas' }}</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200">Global</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ $c->is_active ? 'bg-[#f6ffed] text-[#389e0d] border border-[#b7eb8f]' : 'bg-gray-100 text-gray-500 border border-gray-200' }}">
                                {{ $c->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="edit" variant="primary" wire:click="edit('{{ $c->id }}')">Edit</x-action-menu.item>
                                    <x-action-menu.divider />
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $c->id }}')" wire:confirm="Hapus catatan ini?">Hapus</x-action-menu.item>
                                </x-action-menu>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada catatan yang cocok dengan pencarian.' : 'Belum ada catatan.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
