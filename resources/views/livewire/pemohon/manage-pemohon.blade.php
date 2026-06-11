<div class="flex flex-col gap-6">
    <x-flash />
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Data Pemohon</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola data pemohon (NIK, identitas, dan domisili).</p>
        </div>
        <button wire:click="$toggle('showForm')"
            class="px-4 py-2 rounded-md font-medium text-sm shadow-sm {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Tutup Form' : '+ Tambah Pemohon' }}
        </button>
    </div>

    {{-- Form --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">{{ $editingId ? 'Edit Pemohon' : 'Pemohon Baru' }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">NIK <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nik" maxlength="16" placeholder="16 digit"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @error('nik') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nama"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tempat Lahir</label>
                    <input type="text" wire:model="tempat_lahir"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tanggal Lahir</label>
                    <input type="date" wire:model="tanggal_lahir"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Jenis Kelamin</label>
                    <select wire:model="jenis_kelamin"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        <option value="">—</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Pekerjaan</label>
                    <input type="text" wire:model="pekerjaan"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
            </div>

            {{-- Domisili: cascading wilayah picker --}}
            <div class="flex flex-col gap-2 border-t border-gray-200 pt-4">
                <p class="text-sm font-semibold text-gray-700">Domisili (pilih bertahap hingga desa/kelurahan)</p>
                <x-wilayah-picker :provinsi-list="$provinsiList" :kabupaten-list="$kabupatenList" :kecamatan-list="$kecamatanList" :desa-list="$desaList" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Alamat Detail</label>
                <textarea wire:model="alamat_detail" rows="2"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan Pemohon' }}
                </button>
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            </div>
        </form>
    @endif

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari NIK, nama, atau pekerjaan..." :count="$pemohonList->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3 w-12 text-center">No</th>
                    <th class="px-4 py-3">NIK</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3 text-center">L/P</th>
                    <th class="px-4 py-3">Desa</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pemohonList as $i => $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->nik }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $p->nama }}</td>
                        <td class="px-4 py-3 text-center">{{ $p->jenis_kelamin?->value ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $p->desa?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-3">
                                <button wire:click="edit('{{ $p->id }}')" class="text-[#1677ff] hover:text-[#0958d9] font-medium text-xs">Edit</button>
                                <span class="w-px h-3 bg-gray-300"></span>
                                <button wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus pemohon {{ $p->nama }}?" class="text-[#ff4d4f] hover:text-[#cf1322] font-medium text-xs">Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada pemohon yang cocok dengan pencarian.' : 'Belum ada data pemohon.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
