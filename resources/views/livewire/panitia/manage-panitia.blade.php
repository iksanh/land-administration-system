<div class="flex flex-col gap-6">
    <x-flash />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Panitia Pemeriksa Tanah A</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola anggota panitia yang menandatangani Berita Acara Pemeriksaan Lapang.</p>
        </div>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
        <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">
            {{ $editingId ? 'Edit Anggota Panitia' : 'Tambah Anggota Panitia' }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5 md:col-span-2">
                <label class="text-sm font-medium text-gray-700">Nama <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nama" placeholder="Nama lengkap beserta gelar"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">NIP</label>
                <input type="text" wire:model="nip" placeholder="Opsional"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('nip') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Peran <span class="text-red-500">*</span></label>
                <select wire:model="peran" class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @foreach ($peranOptions as $opt)
                        <option value="{{ $opt->value }}">{{ $opt->label() }} — {{ $opt->frasa() }}</option>
                    @endforeach
                </select>
                @error('peran') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5 md:col-span-2">
                <label class="text-sm font-medium text-gray-700">Jabatan</label>
                <input type="text" wire:model="jabatan" placeholder="Misal: Kepala Seksi Survei dan Pemetaan ..."
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('jabatan') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Urutan</label>
                <input type="number" min="0" wire:model="urutan"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                <span class="text-xs text-gray-400">Menentukan urutan tampil & tanda tangan.</span>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Status Aktif</label>
                <button type="button" wire:click="$toggle('is_active')"
                    class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors mt-1 {{ $is_active ? 'bg-[#52c41a]' : 'bg-gray-300' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                </button>
            </div>
        </div>
        <div class="flex gap-3 pt-3 border-t border-gray-200">
            <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                {{ $editingId ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            @endif
        </div>
    </form>

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari nama, NIP, atau jabatan..." :count="$panitiaList->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3 text-center w-16">Urutan</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">NIP</th>
                    <th class="px-4 py-3">Jabatan</th>
                    <th class="px-4 py-3 text-center">Peran</th>
                    <th class="px-4 py-3 text-center">Aktif</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($panitiaList as $p)
                    <tr class="hover:bg-gray-50 {{ $p->is_active ? '' : 'bg-gray-50/50 opacity-70' }}">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $p->urutan }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $p->nama }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->nip ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $p->jabatan ?: '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f9f0ff] text-[#722ed1] border border-[#d3adf7]">
                                {{ $p->peran->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($p->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f6ffed] text-[#52c41a] border border-[#b7eb8f]">Aktif</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-500 border border-gray-200">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="edit" variant="primary" wire:click="edit('{{ $p->id }}')">Edit</x-action-menu.item>
                                    <x-action-menu.divider />
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus anggota panitia {{ $p->nama }}?">Hapus</x-action-menu.item>
                                </x-action-menu>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada anggota panitia yang cocok.' : 'Belum ada anggota panitia.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
