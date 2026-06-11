<div class="flex flex-col gap-6">
    <x-flash />
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Data Tanah</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola bidang tanah, luas, penggunaan, dan batas-batasnya.</p>
        </div>
        <button wire:click="$toggle('showForm')"
            class="px-4 py-2 rounded-md font-medium text-sm shadow-sm {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Tutup Form' : '+ Tambah Tanah' }}
        </button>
    </div>

    {{-- Form --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">{{ $editingId ? 'Edit Tanah' : 'Tanah Baru' }}</h3>

            @php $f = 'border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]'; @endphp

            {{-- Section: Bidang & Pemohon --}}
            <section class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Bidang &amp; Pemohon</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Pemohon</label>
                        <select wire:model="pemohon_id" class="{{ $f }}">
                            <option value="">—</option>
                            @foreach ($pemohonList as $pemohon)
                                <option value="{{ $pemohon->id }}">{{ $pemohon->nama }} ({{ $pemohon->nik }})</option>
                            @endforeach
                        </select>
                        @error('pemohon_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Luas (m²)</label>
                        <input type="number" step="0.01" wire:model="luas" placeholder="0" class="{{ $f }}">
                        @error('luas') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Luas Surat (m²)</label>
                        <input type="number" step="0.01" wire:model="luas_surat" placeholder="0" class="{{ $f }}">
                        @error('luas_surat') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Section: Identitas Bidang --}}
            <section class="flex flex-col gap-3 border-t border-gray-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Identitas Bidang (PBT / NIB)</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">NIB</label>
                        <input type="text" wire:model="nib" placeholder="Nomor Identifikasi Bidang" class="{{ $f }}">
                        @error('nib') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Nomor PBT</label>
                        <input type="text" wire:model="nomor_pbt" placeholder="Nomor Peta Bidang Tanah" class="{{ $f }}">
                        @error('nomor_pbt') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Tanggal PBT</label>
                        <input type="date" wire:model="tanggal_pbt" class="{{ $f }}">
                        @error('tanggal_pbt') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Section: Analisis Penggunaan Tanah --}}
            <section class="flex flex-col gap-3 border-t border-gray-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Analisis Penggunaan Tanah</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Tanggal Peta Analisis</label>
                        <input type="date" wire:model="tgl_peta_analisis" class="{{ $f }}">
                        @error('tgl_peta_analisis') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Penggunaan Tanah</label>
                        <input type="text" wire:model="penggunaan_tanah" placeholder="Penggunaan tanah saat ini" class="{{ $f }}">
                        @error('penggunaan_tanah') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Rencana Penggunaan (RTRW)</label>
                        <input type="text" wire:model="rencana_penggunaan_rtrw" placeholder="Peruntukan menurut RTRW" class="{{ $f }}">
                        @error('rencana_penggunaan_rtrw') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Kesesuaian Penggunaan Tanah</label>
                        <select wire:model="kesesuaian_penggunaan_tanah" class="{{ $f }}">
                            <option value="">—</option>
                            <option value="Sesuai">Sesuai</option>
                            <option value="Tidak Sesuai">Tidak Sesuai</option>
                            <option value="Sesuai Bersyarat">Sesuai Bersyarat</option>
                        </select>
                        @error('kesesuaian_penggunaan_tanah') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Penggunaan Tanah di SK</label>
                        <input type="text" wire:model="penggunaan_tanah_sk" placeholder="Penggunaan tanah sesuai SK" class="{{ $f }}">
                        @error('penggunaan_tanah_sk') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Section: Batas-Batas Bidang --}}
            <section class="flex flex-col gap-3 border-t border-gray-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Batas-Batas Bidang</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Batas Utara</label>
                        <input type="text" wire:model="batas_utara" class="{{ $f }}">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Batas Timur</label>
                        <input type="text" wire:model="batas_timur" class="{{ $f }}">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Batas Selatan</label>
                        <input type="text" wire:model="batas_selatan" class="{{ $f }}">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Batas Barat</label>
                        <input type="text" wire:model="batas_barat" class="{{ $f }}">
                    </div>
                </div>
            </section>

            {{-- Section: Lokasi (cascading wilayah picker) --}}
            <section class="flex flex-col gap-3 border-t border-gray-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lokasi (pilih bertahap hingga desa/kelurahan)</p>
                <x-wilayah-picker :provinsi-list="$provinsiList" :kabupaten-list="$kabupatenList" :kecamatan-list="$kecamatanList" :desa-list="$desaList" />
            </section>

            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan Tanah' }}
                </button>
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            </div>
        </form>
    @endif

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari pemohon, penggunaan, atau desa..." :count="$tanahList->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3 w-12 text-center">No</th>
                    <th class="px-4 py-3">Pemohon</th>
                    <th class="px-4 py-3">NIB</th>
                    <th class="px-4 py-3">No. PBT</th>
                    <th class="px-4 py-3 text-right">Luas (m²)</th>
                    <th class="px-4 py-3">Penggunaan</th>
                    <th class="px-4 py-3 text-center">Kesesuaian</th>
                    <th class="px-4 py-3">Desa</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tanahList as $i => $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $t->pemohon?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $t->nib ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $t->nomor_pbt ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $t->luas ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $t->penggunaan_tanah ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($t->kesesuaian_penggunaan_tanah)
                                @php
                                    $kColor = match ($t->kesesuaian_penggunaan_tanah) {
                                        'Sesuai' => 'bg-[#f6ffed] text-[#389e0d] border-[#b7eb8f]',
                                        'Tidak Sesuai' => 'bg-[#fff1f0] text-[#cf1322] border-[#ffa39e]',
                                        'Sesuai Bersyarat' => 'bg-[#fff7e6] text-[#d46b08] border-[#ffd591]',
                                        default => 'bg-gray-100 text-gray-600 border-gray-200',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $kColor }}">{{ $t->kesesuaian_penggunaan_tanah }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $t->desa?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-3">
                                <button wire:click="edit('{{ $t->id }}')" class="text-[#1677ff] hover:text-[#0958d9] font-medium text-xs">Edit</button>
                                <span class="w-px h-3 bg-gray-300"></span>
                                <button wire:click="delete('{{ $t->id }}')" wire:confirm="Hapus data tanah ini?" class="text-[#ff4d4f] hover:text-[#cf1322] font-medium text-xs">Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada data tanah yang cocok dengan pencarian.' : 'Belum ada data tanah.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
