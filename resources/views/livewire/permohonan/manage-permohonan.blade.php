<div class="flex flex-col gap-6">
    @php
        $statusColor = fn ($s) => match ($s) {
            'DRAFT' => 'bg-gray-100 text-gray-600 border-gray-200',
            'SUBMITTED' => 'bg-[#e6f4ff] text-[#1677ff] border-[#91caff]',
            'VERIFIKASI_BERKAS' => 'bg-[#fff7e6] text-[#d46b08] border-[#ffd591]',
            'PENGUKURAN' => 'bg-[#f9f0ff] text-[#722ed1] border-[#d3adf7]',
            'PANITIA' => 'bg-[#e6fffb] text-[#08979c] border-[#87e8de]',
            'SK_TERBIT' => 'bg-[#f6ffed] text-[#389e0d] border-[#b7eb8f]',
            'SELESAI' => 'bg-[#52c41a]/10 text-[#389e0d] border-[#b7eb8f]',
            'DITOLAK' => 'bg-[#fff1f0] text-[#cf1322] border-[#ffa39e]',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    @endphp

    <x-flash />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Permohonan</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola permohonan layanan pertanahan beserta alur statusnya.</p>
        </div>
        <button wire:click="$toggle('showForm')"
            class="px-4 py-2 rounded-md font-medium text-sm shadow-sm {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Tutup Form' : '+ Tambah Permohonan' }}
        </button>
    </div>

    @if (session('error'))
        <div class="bg-[#fff1f0] border border-[#ffa39e] text-[#cf1322] px-4 py-2.5 rounded-md text-sm">{{ session('error') }}</div>
    @endif

    {{-- Form --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">{{ $editingId ? 'Edit Permohonan' : 'Permohonan Baru' }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Nomor Registrasi <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nomor_registrasi" placeholder="Misal: REG-2026-001"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @error('nomor_registrasi') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tanggal Pendaftaran</label>
                    <input type="date" wire:model="tgl_pendaftaran"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Layanan</label>
                    <select wire:model="layanan_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        <option value="">—</option>
                        @foreach ($layananList as $l)
                            <option value="{{ $l->id }}">{{ $l->nama }}</option>
                        @endforeach
                    </select>
                    @error('layanan_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Pemohon</label>
                    <select wire:model="pemohon_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        <option value="">—</option>
                        @foreach ($pemohonList as $pemohon)
                            <option value="{{ $pemohon->id }}">{{ $pemohon->nama }} ({{ $pemohon->nik }})</option>
                        @endforeach
                    </select>
                    @error('pemohon_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5 md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Bidang Tanah</label>
                    <select wire:model="tanah_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                        <option value="">—</option>
                        @foreach ($tanahList as $t)
                            @php $used = in_array($t->id, $usedTanahIds, true); @endphp
                            <option value="{{ $t->id }}" @disabled($used)>
                                {{ $t->pemohon?->nama ?? 'Tanpa pemohon' }} — {{ $t->luas ?? '?' }} m²{{ $used ? ' (sudah terdaftar)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-xs text-gray-400">Bidang tanah yang sudah terdaftar pada permohonan lain tidak dapat dipilih.</span>
                    @error('tanah_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan Permohonan' }}
                </button>
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            </div>
        </form>
    @endif

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari no. registrasi, pemohon, layanan, atau status..." :count="$permohonanList->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3">No. Registrasi</th>
                    <th class="px-4 py-3">Pemohon</th>
                    <th class="px-4 py-3">Layanan</th>
                    <th class="px-4 py-3">Tanah</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center w-44">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($permohonanList as $p)
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $p->nomor_registrasi }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $p->pemohon?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $p->layanan?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            @if ($p->tanah)
                                {{ $p->tanah->luas ?? '?' }} m² — Desa {{ $p->tanah->desa?->nama ?? '—' }}
                            @else
                                Lokasi belum ditentukan
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $statusColor($p->status->value) }}">
                                {{ $p->status->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <button wire:click="startStatusChange('{{ $p->id }}')" class="text-[#722ed1] hover:text-[#531dab] font-medium text-xs">Ubah Status</button>
                                <span class="w-px h-3 bg-gray-300"></span>
                                <button wire:click="edit('{{ $p->id }}')" class="text-[#1677ff] hover:text-[#0958d9] font-medium text-xs">Edit</button>
                                @if ($p->status->value === 'DRAFT')
                                    <span class="w-px h-3 bg-gray-300"></span>
                                    <button wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus permohonan {{ $p->nomor_registrasi }}?" class="text-[#ff4d4f] hover:text-[#cf1322] font-medium text-xs">Hapus</button>
                                @endif
                            </div>

                            {{-- Status-change panel --}}
                            @if ($statusEditingId === $p->id)
                                <div class="mt-3 bg-[#f9f0ff] border border-[#d3adf7] rounded-md p-3 flex flex-col gap-2 text-left">
                                    <label class="text-[11px] font-semibold text-[#531dab] uppercase">Ubah Status</label>
                                    <select wire:model="newStatus" class="border border-[#d3adf7] rounded px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#722ed1]">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}">{{ $s->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('newStatus') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    <textarea wire:model="statusCatatan" rows="2" placeholder="Catatan (opsional)" class="border border-[#d3adf7] rounded px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-[#722ed1]"></textarea>
                                    <div class="flex gap-2">
                                        <button wire:click="changeStatus" class="bg-[#722ed1] hover:bg-[#531dab] text-white rounded px-3 py-1.5 text-xs font-medium">Simpan</button>
                                        <button wire:click="cancelStatusChange" class="bg-white border border-gray-300 text-gray-600 rounded px-3 py-1.5 text-xs">Batal</button>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada permohonan yang cocok dengan pencarian.' : 'Belum ada permohonan.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
