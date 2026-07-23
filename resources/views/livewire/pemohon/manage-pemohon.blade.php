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
        @php $isKuasa = $jenis_pemohon === 'dikuasakan'; @endphp
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">{{ $editingId ? 'Edit Pemohon' : 'Pemohon Baru' }}</h3>

            {{-- Jenis pemohon: diri sendiri vs dikuasakan (selectable cards) --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-700">Permohonan diajukan <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button type="button" wire:click="$set('jenis_pemohon', 'diri_sendiri')"
                        class="flex items-start gap-3 text-left p-3 rounded-lg border transition {{ ! $isKuasa ? 'border-[#1677ff] bg-[#1677ff]/5 ring-1 ring-[#1677ff]/30' : 'border-gray-300 bg-white hover:border-gray-400' }}">
                        <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 {{ ! $isKuasa ? 'border-[#1677ff]' : 'border-gray-300' }}">
                            <span class="h-2.5 w-2.5 rounded-full {{ ! $isKuasa ? 'bg-[#1677ff]' : 'bg-transparent' }}"></span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">Atas Nama Diri Sendiri</span>
                            <span class="block text-xs text-gray-500 mt-0.5">Pemohon mengurus permohonannya sendiri.</span>
                        </span>
                    </button>
                    <button type="button" wire:click="$set('jenis_pemohon', 'dikuasakan')"
                        class="flex items-start gap-3 text-left p-3 rounded-lg border transition {{ $isKuasa ? 'border-[#1677ff] bg-[#1677ff]/5 ring-1 ring-[#1677ff]/30' : 'border-gray-300 bg-white hover:border-gray-400' }}">
                        <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 {{ $isKuasa ? 'border-[#1677ff]' : 'border-gray-300' }}">
                            <span class="h-2.5 w-2.5 rounded-full {{ $isKuasa ? 'bg-[#1677ff]' : 'bg-transparent' }}"></span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">Dikuasakan</span>
                            <span class="block text-xs text-gray-500 mt-0.5">Diwakili orang lain dengan surat kuasa.</span>
                        </span>
                    </button>
                </div>
                @error('jenis_pemohon') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            @if ($isKuasa)
                <p class="text-sm font-semibold text-gray-700 -mb-1">Data Pemohon (Pemberi Kuasa)</p>
            @endif
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

            {{-- Data penerima kuasa — hanya saat dikuasakan --}}
            @if ($isKuasa)
                <div class="flex flex-col gap-4 rounded-lg border border-[#1677ff]/30 bg-[#1677ff]/[0.03] p-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-[#1677ff]/20">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1677ff]/10 text-[#1677ff] text-sm">👤</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Data Yang Dikuasakan (Penerima Kuasa)</p>
                            <p class="text-xs text-gray-500">Orang yang diberi kuasa untuk mengurus permohonan ini.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">Nama Yang Dikuasakan <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="kuasa_nama"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                            @error('kuasa_nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">NIK Yang Dikuasakan <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="kuasa_nik" maxlength="16" placeholder="16 digit"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                            @error('kuasa_nik') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">Pekerjaan</label>
                            <input type="text" wire:model="kuasa_pekerjaan"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">No. HP / Telepon</label>
                            <input type="text" wire:model="kuasa_no_hp" placeholder="08xxxxxxxxxx"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">Hubungan dengan Pemohon</label>
                            <input type="text" wire:model="kuasa_hubungan" placeholder="mis. Anak, Saudara, Kuasa Hukum"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Alamat Yang Dikuasakan</label>
                        <textarea wire:model="kuasa_alamat" rows="2"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-[#1677ff]/20 pt-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">No. Surat Kuasa</label>
                            <input type="text" wire:model="kuasa_no_surat"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">Tanggal Surat Kuasa</label>
                            <input type="date" wire:model="kuasa_tanggal_surat"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        </div>
                    </div>
                </div>
            @endif

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
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">{{ $p->nama }}</span>
                            @if ($p->jenis_pemohon === \App\Enums\JenisPemohonEnum::DIKUASAKAN)
                                <span class="ml-1.5 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 border border-amber-200">Dikuasakan</span>
                                <div class="text-xs text-gray-500 mt-0.5">Kuasa: {{ $p->kuasa_nama ?? '—' }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">{{ $p->jenis_kelamin?->value ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $p->desa?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="edit" variant="primary" wire:click="edit('{{ $p->id }}')">Edit</x-action-menu.item>
                                    <x-action-menu.divider />
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus pemohon {{ $p->nama }}?">Hapus</x-action-menu.item>
                                </x-action-menu>
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
