<div class="flex flex-col gap-6">
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
                    <th class="px-4 py-3">Tgl. Pendaftaran</th>
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
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $p->tgl_pendaftaran?->locale('id')->translatedFormat('d F Y') ?? '—' }}</td>
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
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $p->status->badgeClass() }}">
                                {{ $p->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <x-action-btn icon="status" variant="purple" wire:click="startStatusChange('{{ $p->id }}')">Ubah Status</x-action-btn>
                                <x-action-btn icon="edit" variant="primary" wire:click="edit('{{ $p->id }}')">Edit</x-action-btn>
                                <a href="{{ route('berita-acara', ['permohonan' => $p->id]) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors text-[#08979c] border-[#87e8de] hover:bg-[#e6fffb] hover:border-[#08979c]">
                                    Berita Acara
                                </a>
                                <a href="{{ route('risalah', ['permohonan' => $p->id]) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors text-[#531dab] border-[#d3adf7] hover:bg-[#f9f0ff] hover:border-[#531dab]">
                                    Risalah
                                </a>
                                @if ($p->status->value === 'DRAFT')
                                    <x-action-btn icon="delete" variant="danger" wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus permohonan {{ $p->nomor_registrasi }}?">Hapus</x-action-btn>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada permohonan yang cocok dengan pencarian.' : 'Belum ada permohonan.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Status stepper modal --}}
    @if ($statusEditingId && $statusPermohonan)
        @php
            $flow = \App\Enums\PermohonanStatusEnum::flow();
            $current = $statusPermohonan->status;
            $currentIdx = $current->stepIndex();
            $rejected = $current === \App\Enums\PermohonanStatusEnum::DITOLAK;
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="status-modal-{{ $statusPermohonan->id }}">
            <div class="absolute inset-0 bg-gray-900/45" wire:click="cancelStatusChange"></div>
            <div class="relative bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-lg max-h-[90vh] flex flex-col">

                {{-- Modal header --}}
                <div class="flex items-start justify-between px-5 py-4 border-b border-gray-200">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-800">Ubah Status Permohonan</h3>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            <span class="font-mono">{{ $statusPermohonan->nomor_registrasi }}</span>
                            — {{ $statusPermohonan->pemohon?->nama ?? 'Tanpa pemohon' }}
                        </p>
                    </div>
                    <button wire:click="cancelStatusChange" class="ml-3 shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100">✕</button>
                </div>

                {{-- Stepper --}}
                <div class="px-5 py-4 overflow-y-auto flex-1">
                    @if ($rejected)
                        <div class="mb-4 bg-[#fff1f0] border border-[#ffa39e] rounded-md px-3 py-2.5 text-sm text-[#cf1322]">
                            Permohonan ini berstatus <span class="font-semibold">Ditolak</span>. Isi catatan lalu klik
                            <span class="font-semibold">Buka Kembali</span> untuk mengembalikannya ke tahap sebelum penolakan.
                        </div>
                    @else
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-medium text-gray-500">Tahap {{ $currentIdx + 1 }} dari {{ count($flow) }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $current->badgeClass() }}">{{ $current->label() }}</span>
                        </div>
                    @endif

                    <ol>
                        @foreach ($flow as $i => $step)
                            @php
                                $done = ! $rejected && $i < $currentIdx;
                                $active = ! $rejected && $i === $currentIdx;
                            @endphp
                            <li class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="w-6 h-6 shrink-0 rounded-full inline-flex items-center justify-center text-[11px] font-semibold border
                                        {{ $done ? 'bg-[#1677ff] border-[#1677ff] text-white' : '' }}
                                        {{ $active ? 'bg-white border-[#1677ff] text-[#1677ff] ring-4 ring-[#1677ff]/15' : '' }}
                                        {{ ! $done && ! $active ? 'bg-white border-gray-300 text-gray-400' : '' }}">
                                        {{ $done ? '✓' : $i + 1 }}
                                    </span>
                                    @unless ($loop->last)
                                        <span class="w-px flex-1 min-h-4 {{ $done ? 'bg-[#1677ff]' : 'bg-gray-200' }}"></span>
                                    @endunless
                                </div>
                                <div class="pb-4 pt-1 min-w-0">
                                    <p class="text-[13px] leading-snug {{ $active ? 'font-semibold text-[#1677ff]' : ($done ? 'text-gray-700' : 'text-gray-400') }}">
                                        {{ $step->label() }}
                                        @if ($active) <span class="ml-1 text-[10px] font-semibold uppercase tracking-wide text-[#1677ff]/70">— saat ini</span> @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Actions --}}
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50/60 rounded-b-xl flex flex-col gap-2.5">
                    <textarea wire:model="statusCatatan" rows="2"
                        placeholder="{{ $rejected ? 'Catatan buka kembali (wajib)...' : 'Catatan — wajib saat mundur atau tolak, opsional saat maju...' }}"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
                    @error('statusCatatan') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($rejected)
                            <button wire:click="reopenStatus"
                                class="px-4 py-2 rounded-md text-sm font-medium bg-[#1677ff] hover:bg-[#0958d9] text-white shadow-sm">
                                Buka Kembali
                            </button>
                        @else
                            @if ($current->next())
                                <button wire:click="advanceStatus" wire:loading.attr="disabled"
                                    class="px-4 py-2 rounded-md text-sm font-medium bg-[#1677ff] hover:bg-[#0958d9] text-white shadow-sm">
                                    Lanjut: {{ $current->next()->label() }} →
                                </button>
                            @else
                                <span class="text-sm font-medium text-[#389e0d]">✓ Alur selesai — berkas di Loket Penyerahan.</span>
                            @endif
                            @if ($current->prev())
                                <button wire:click="regressStatus" wire:loading.attr="disabled"
                                    class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50">
                                    ← Mundur
                                </button>
                            @endif
                            <button wire:click="rejectStatus" wire:loading.attr="disabled"
                                class="ml-auto px-3 py-2 rounded-md text-sm font-medium text-[#cf1322] bg-white border border-[#ffa39e] hover:bg-[#fff1f0]">
                                Tolak
                            </button>
                        @endif
                        <button wire:click="cancelStatusChange"
                            class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 {{ $rejected ? 'ml-auto' : '' }}">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
