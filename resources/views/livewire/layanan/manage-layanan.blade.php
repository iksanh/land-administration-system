<div class="flex flex-col">
    <x-flash />
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Master Layanan</h2>
        <p class="text-sm text-gray-500 mt-1">Kelola daftar layanan pertanahan yang tersedia di sistem.</p>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 mb-6 flex flex-col gap-4">
        <h3 class="text-base font-semibold text-gray-800">
            {{ $editingId ? 'Edit Layanan' : 'Tambah Layanan' }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Kode Layanan <span class="text-red-500">*</span></label>
                <input type="text" wire:model="kode" placeholder="Misal: LYN-001"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('kode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Nama Layanan <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nama" placeholder="Misal: Sertifikasi Hak Milik"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium text-gray-700">Deskripsi</label>
            <textarea wire:model="deskripsi" rows="2" placeholder="Keterangan layanan (opsional)"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
            Aktif
        </label>
        <div class="flex gap-3 pt-3 border-t border-gray-200">
            <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                {{ $editingId ? 'Simpan Perubahan' : 'Tambah Layanan' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            @endif
        </div>
    </form>

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari kode, nama, atau deskripsi..." :count="$layanan->count()" class="mb-4" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3 w-32">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Deskripsi</th>
                    <th class="px-4 py-3 text-center w-24">Status</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($layanan as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $item->kode }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $item->nama }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->deskripsi }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold
                                {{ $item->is_active ? 'bg-[#f6ffed] text-[#389e0d] border border-[#b7eb8f]' : 'bg-gray-100 text-gray-500 border border-gray-200' }}">
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="edit" variant="primary" wire:click="edit('{{ $item->id }}')">Edit</x-action-menu.item>
                                    <x-action-menu.divider />
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $item->id }}')" wire:confirm="Hapus layanan {{ $item->nama }}?">Hapus</x-action-menu.item>
                                </x-action-menu>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada layanan yang cocok dengan pencarian.' : 'Belum ada layanan.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
