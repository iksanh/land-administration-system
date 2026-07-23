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
                    <th class="px-4 py-3 text-center">
                        {{-- Filter checklist status (posisi fixed agar tak terpotong overflow tabel) --}}
                        <div class="inline-flex items-center gap-1"
                            x-data="{ open: false, pos: {} }"
                            x-on:click.outside="open = false"
                            x-on:keydown.escape.window="open = false"
                            x-on:resize.window="open = false">
                            <span>Status</span>
                            <button type="button" x-ref="trigger" title="Filter status"
                                x-on:click="
                                    if (open) { open = false } else {
                                        const r = $refs.trigger.getBoundingClientRect();
                                        pos = { top: (r.bottom + 6) + 'px', left: Math.max(8, Math.min(r.right - 288, window.innerWidth - 296)) + 'px' };
                                        open = true;
                                    }"
                                class="w-6 h-6 inline-flex items-center justify-center rounded transition-colors {{ $statusFilter !== [] ? 'text-[#1677ff] bg-[#e6f4ff]' : 'text-gray-400 hover:bg-gray-200/70 hover:text-gray-600' }}">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition.opacity.duration.100ms x-bind:style="pos"
                                class="fixed z-40 w-72 bg-white border border-gray-200 rounded-lg shadow-lg text-left font-normal normal-case">
                                <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-gray-100">
                                    <span class="text-xs font-semibold text-gray-700">Filter Status</span>
                                    <span class="text-[11px] text-gray-400">{{ $statusFilter === [] ? 'Semua tampil' : count($statusFilter).' dipilih' }}</span>
                                </div>
                                <div class="max-h-72 overflow-y-auto py-1">
                                    @foreach ($statuses as $s)
                                        <label class="flex items-center gap-2.5 px-3.5 py-1.5 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" wire:model.live="statusFilter" value="{{ $s->value }}"
                                                class="rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
                                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $s->badgeClass() }}">{{ $s->label() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="px-3.5 py-2 border-t border-gray-100">
                                    <button type="button" wire:click="$set('statusFilter', [])"
                                        class="text-xs font-medium {{ $statusFilter === [] ? 'text-gray-300 cursor-default' : 'text-[#1677ff] hover:text-[#0958d9]' }}"
                                        @disabled($statusFilter === [])>
                                        Tampilkan semua
                                    </button>
                                </div>
                            </div>
                        </div>
                    </th>
                    <th class="px-4 py-3 text-center w-16">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($permohonanList as $p)
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">
                            {{ $p->nomor_registrasi }}
                            @if ($p->nomor_berkas)
                                <span class="block text-[10px] text-gray-400 mt-0.5 font-sans">Berkas {{ $p->nomor_berkas }}/{{ $p->tahun_berkas }}</span>
                            @endif
                        </td>
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
                        <td class="px-4 py-3 text-center">
                            <x-action-menu>
                                <x-action-menu.item icon="status" variant="purple" wire:click="startStatusChange('{{ $p->id }}')">Ubah Status</x-action-menu.item>
                                <x-action-menu.item icon="edit" variant="primary" wire:click="edit('{{ $p->id }}')">Edit</x-action-menu.item>
                                <x-action-menu.divider />
                                <x-action-menu.item icon="check-doc" variant="green" :href="route('pemeriksaan-berkas', ['permohonan' => $p->id])" wire:navigate>Periksa Berkas</x-action-menu.item>
                                <x-action-menu.item icon="doc" variant="cyan" :href="route('berita-acara', ['permohonan' => $p->id])" wire:navigate>Berita Acara</x-action-menu.item>
                                <x-action-menu.item icon="doc" variant="purple" :href="route('risalah', ['permohonan' => $p->id])" wire:navigate>Risalah</x-action-menu.item>
                                @if ($p->status->value === 'DRAFT')
                                    <x-action-menu.divider />
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $p->id }}')" wire:confirm="Hapus permohonan {{ $p->nomor_registrasi }}?">Hapus</x-action-menu.item>
                                @endif
                            </x-action-menu>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">
                        @if ($search !== '' || $statusFilter !== [])
                            Tidak ada permohonan yang cocok dengan pencarian / filter status.
                            @if ($statusFilter !== [])
                                <button type="button" wire:click="$set('statusFilter', [])" class="text-[#1677ff] hover:underline ml-1">Hapus filter</button>
                            @endif
                        @else
                            Belum ada permohonan.
                        @endif
                    </td></tr>
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
            // Gerbang role per tahap (admin selalu boleh) — cermin dari authorizeStage().
            $canAct = auth()->user()->isAdmin()
                || collect($current->allowedRoles())->contains(fn ($r) => auth()->user()->hasRole($r));
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
                            <span class="text-xs font-medium text-gray-500">
                                Tahap {{ $currentIdx + 1 }} dari {{ count($flow) }}
                                <span class="text-gray-400">· diproses oleh {{ $current->allowedRoleLabels() }}</span>
                            </span>
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
                                    @if ($step === \App\Enums\PermohonanStatusEnum::TERDAFTAR && $statusPermohonan->nomor_berkas)
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            Berkas {{ $statusPermohonan->nomor_berkas }}/{{ $statusPermohonan->tahun_berkas }}
                                            · daftar KKP {{ $statusPermohonan->tanggal_daftar_kkp?->locale('id')->translatedFormat('d M Y') }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Actions --}}
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50/60 rounded-b-xl flex flex-col gap-2.5">
                    {{-- Gerbang setelah TERDAFTAR: data KKP wajib sebelum ke Konsep RPD & BA & SK --}}
                    @if ($canAct && ! $rejected && $current === \App\Enums\PermohonanStatusEnum::TERDAFTAR)
                        <div class="bg-[#e6f4ff]/60 border border-[#91caff] rounded-md p-3.5 flex flex-col gap-2.5">
                            <div class="flex items-start gap-2">
                                <span class="shrink-0 mt-0.5 w-4 h-4 inline-flex items-center justify-center rounded-full bg-[#1677ff] text-white text-[10px] font-bold">!</span>
                                <p class="text-xs text-[#0958d9] leading-snug">
                                    <span class="font-semibold">Data pendaftaran KKP wajib diisi</span> sebelum permohonan bisa lanjut ke tahap <span class="font-semibold">Konsep RPD & BA & SK</span>.
                                </p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="flex flex-col gap-1 sm:col-span-2">
                                    <label class="text-xs font-medium text-gray-700">Nomor Berkas <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="nomor_berkas" placeholder="Nomor berkas dari aplikasi KKP"
                                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                                    @error('nomor_berkas') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-700">Tahun Berkas <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="tahun_berkas" min="2000" max="2100" placeholder="{{ now()->year }}"
                                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                                    @error('tahun_berkas') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-700">Tanggal Daftar KKP <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model="tanggal_daftar_kkp"
                                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                                    @error('tanggal_daftar_kkp') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (! $canAct)
                        {{-- User bukan role penanggung jawab tahap ini: hanya lihat. --}}
                        <div class="flex items-start gap-2.5 bg-gray-50 border border-gray-200 rounded-md px-3.5 py-3">
                            <span class="shrink-0 mt-0.5">🔒</span>
                            <p class="text-xs text-gray-500 leading-snug">
                                Tahap <span class="font-semibold">{{ $current->label() }}</span> diproses oleh role
                                <span class="font-semibold">{{ $current->allowedRoleLabels() }}</span>.
                                Anda dapat melihat posisi berkas, namun tidak dapat mengubah statusnya.
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <button wire:click="cancelStatusChange"
                                class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50">
                                Tutup
                            </button>
                        </div>
                    @else
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
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
