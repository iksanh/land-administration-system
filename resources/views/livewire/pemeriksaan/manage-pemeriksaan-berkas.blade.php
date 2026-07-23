<div class="flex flex-col gap-6">
    @php
        $statusColor = fn ($s) => match ($s) {
            'OK' => 'bg-[#f6ffed] text-[#389e0d] border-[#b7eb8f]',
            'REVISI' => 'bg-[#fff7e6] text-[#d46b08] border-[#ffd591]',
            'TOLAK' => 'bg-[#fff1f0] text-[#cf1322] border-[#ffa39e]',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
        // Warna tombol saat status aktif dipilih (segmented control satu-klik).
        $statusActive = fn ($s) => match ($s) {
            'OK' => 'bg-[#389e0d] text-white',
            'REVISI' => 'bg-[#d46b08] text-white',
            'TOLAK' => 'bg-[#cf1322] text-white',
            default => 'bg-gray-500 text-white',
        };
        $statusLabel = fn ($s) => match ($s) {
            'PENDING' => 'Belum',
            'OK' => 'OK',
            'REVISI' => 'Revisi',
            'TOLAK' => 'Tolak',
            default => $s,
        };
    @endphp

    <x-flash />

    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Pemeriksaan Berkas</h2>
        <p class="text-sm text-gray-500 mt-1">Periksa kelengkapan berkas per permohonan dan beri catatan.</p>
    </div>

    {{-- Pick permohonan (combobox dengan pencarian — daftar penuh tidak pernah dirender) --}}
    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex-1">
            <label class="text-sm font-medium text-gray-700 block mb-1.5">Pilih Permohonan</label>

            @if ($permohonan)
                {{-- Sudah terpilih: kartu ringkas + tombol ganti --}}
                <div class="w-full md:w-2/3 flex items-center justify-between gap-3 bg-white border border-[#91caff] rounded-md px-3 py-2">
                    <div class="min-w-0 flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-xs text-gray-700">{{ $permohonan->nomor_registrasi }}</span>
                        <span class="text-sm font-medium text-gray-800 truncate">{{ $permohonan->pemohon?->nama ?? 'Tanpa pemohon' }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold border {{ $permohonan->status->badgeClass() }}">{{ $permohonan->status->label() }}</span>
                    </div>
                    <button type="button" wire:click="clearPermohonan"
                        class="shrink-0 text-xs font-medium text-[#1677ff] hover:text-[#0958d9]">✕ Ganti</button>
                </div>
            @else
                {{-- Belum terpilih: input pencarian + dropdown hasil --}}
                <div class="relative w-full md:w-2/3" x-data="{ open: false }" x-on:click.outside="open = false">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">🔍</span>
                        <input type="text" wire:model.live.debounce.300ms="permohonanSearch"
                            x-on:focus="open = true" x-on:input="open = true"
                            x-on:keydown.escape="open = false; $el.blur()"
                            placeholder="Ketik no. registrasi, nama, atau NIK pemohon..."
                            class="w-full border border-gray-300 rounded-md pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"
                            autocomplete="off">
                    </div>

                    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                        class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-72 overflow-y-auto divide-y divide-gray-50">
                        @forelse ($permohonanList as $p)
                            <button type="button" wire:key="opt-{{ $p->id }}"
                                wire:click="selectPermohonan('{{ $p->id }}')" x-on:click="open = false"
                                class="w-full text-left px-3 py-2.5 hover:bg-[#e6f4ff] focus:bg-[#e6f4ff] focus:outline-none flex items-center justify-between gap-3">
                                <span class="min-w-0">
                                    <span class="block font-mono text-[11px] text-gray-500">{{ $p->nomor_registrasi }}</span>
                                    <span class="block text-sm font-medium text-gray-800 truncate">
                                        {{ $p->pemohon?->nama ?? 'Tanpa pemohon' }}
                                        @if ($p->pemohon?->nik)<span class="font-normal text-gray-400 text-xs">· {{ $p->pemohon->nik }}</span>@endif
                                    </span>
                                </span>
                                <span class="shrink-0 inline-flex px-2 py-0.5 rounded text-[10px] font-semibold border {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span>
                            </button>
                        @empty
                            <div class="px-3 py-6 text-center text-sm text-gray-400">
                                Tidak ada permohonan yang cocok dengan "{{ $permohonanSearch }}".
                            </div>
                        @endforelse

                        @if ($permohonanTotal > $permohonanLimit)
                            <div class="px-3 py-2 text-[11px] text-gray-400 bg-gray-50/70 sticky bottom-0">
                                Menampilkan {{ $permohonanLimit }} dari {{ $permohonanTotal }} permohonan — persempit dengan mengetik kata kunci.
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        @if ($selectedPermohonan)
            <button type="button" wire:click="openPrint"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-[#1677ff] bg-white border border-[#1677ff] hover:bg-[#e6f4ff] shrink-0">
                🖨️ Cetak Lembar Pemeriksaan
            </button>
        @endif
    </div>

    @if ($selectedPermohonan)
        {{-- Progres pemeriksaan + tombol lanjut tahap (hanya di tahap periksa Staf/Korsub) --}}
        @if ($permohonan && $periksaStat && $periksaStat['total'] > 0)
            @php
                $allOk = $periksaStat['ok'] === $periksaStat['total'];
                $next = $permohonan->status->next();
                $bermasalah = $periksaStat['checked'] - $periksaStat['ok'];
            @endphp
            <div class="rounded-lg border p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $allOk ? 'bg-[#f6ffed]/70 border-[#b7eb8f]' : 'bg-white border-gray-200' }}">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-800">
                        Tahap: <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border align-middle {{ $permohonan->status->badgeClass() }}">{{ $permohonan->status->label() }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1.5">
                        {{ $periksaStat['checked'] }}/{{ $periksaStat['total'] }} berkas diperiksa
                        · <span class="text-[#389e0d] font-medium">{{ $periksaStat['ok'] }} OK</span>
                        @if ($bermasalah > 0)
                            · <span class="text-[#d46b08] font-medium">{{ $bermasalah }} revisi/tolak</span>
                        @endif
                    </p>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-2 max-w-xs">
                        <div class="h-full rounded-full {{ $allOk ? 'bg-[#52c41a]' : 'bg-[#1677ff]' }}"
                            style="width: {{ round($periksaStat['ok'] / $periksaStat['total'] * 100) }}%"></div>
                    </div>
                </div>
                <div class="shrink-0">
                    @if (! $canSelesai)
                        <p class="text-xs text-gray-400 max-w-52">🔒 Tahap ini diselesaikan oleh role {{ $permohonan->status->allowedRoleLabels() }}.</p>
                    @elseif ($next)
                        <button type="button" wire:click="selesaiPeriksa"
                            wire:confirm="{{ $allOk
                                ? "Kirim permohonan ke tahap {$next->label()}?"
                                : "Belum semua berkas OK ({$periksaStat['ok']}/{$periksaStat['total']}). Tetap kirim ke tahap {$next->label()}?" }}"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-white shadow-sm {{ $allOk ? 'bg-[#389e0d] hover:bg-[#237804]' : 'bg-[#1677ff] hover:bg-[#0958d9]' }}">
                            {{ $allOk ? '✓' : '→' }} Kirim ke {{ $next->label() }}
                        </button>
                        @unless ($allOk)
                            <p class="text-[11px] text-gray-400 mt-1.5 max-w-56">Belum semua berkas OK — hasil pemeriksaan tetap terekam di catatan riwayat.</p>
                        @endunless
                    @endif
                </div>
            </div>
        @endif

        @if (! $hasBerkas)
            <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                Layanan permohonan ini belum memiliki berkas yang dipetakan (atur di Pemetaan Berkas).
            </div>
        @else
            <x-search-bar model="search" placeholder="Cari nama berkas..." :count="$berkasList->count()" />

            @if ($berkasList->isEmpty())
                <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                    Tidak ada berkas yang cocok dengan "{{ $search }}".
                    <button type="button" wire:click="$set('search', '')" class="text-[#1677ff] hover:underline ml-1">Hapus pencarian</button>
                </div>
            @else
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm divide-y divide-gray-100">
                @foreach ($berkasList as $berkas)
                    @php
                        $row = $pemeriksaan->get($berkas->id);
                        $cur = $row?->status?->value ?? 'PENDING';
                    @endphp
                    <div class="p-4" wire:key="berkas-{{ $berkas->id }}">
                        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
                            <div class="flex flex-col min-w-0">
                                <span class="font-medium text-gray-800">{{ $berkas->nama }}</span>
                                @if ($row && $row->catatan)
                                    <ul class="mt-1 text-sm text-gray-500 list-disc list-inside">
                                        @foreach ($row->catatan as $c)
                                            <li>{{ $c['teks'] }} @if($c['is_custom'])<span class="text-[10px] text-[#722ed1]">(custom)</span>@endif</li>
                                        @endforeach
                                    </ul>
                                @endif
                                {{-- Tombol edit catatan (untuk berkas yang sudah punya status). --}}
                                @if ($cur !== 'PENDING' && $editingBerkasId !== $berkas->id)
                                    <button wire:click="openCatatan('{{ $berkas->id }}')"
                                        class="mt-1 self-start text-xs font-medium text-[#1677ff] hover:text-[#0958d9]">
                                        {{ $row && $row->catatan ? '✎ Ubah catatan' : '＋ Tambah catatan' }}
                                    </button>
                                @endif
                            </div>

                            {{-- Segmented status: satu klik langsung tersimpan. --}}
                            <div class="inline-flex rounded-md border border-gray-200 overflow-hidden shrink-0 shadow-sm"
                                wire:loading.class="opacity-60 pointer-events-none" wire:target="setStatus">
                                @foreach ($statuses as $s)
                                    @php $active = $cur === $s->value; @endphp
                                    <button type="button" wire:click="setStatus('{{ $berkas->id }}', '{{ $s->value }}')"
                                        class="px-3 py-1.5 text-xs font-semibold border-l border-gray-200 first:border-l-0 transition-colors
                                            {{ $active ? $statusActive($s->value) : 'bg-white text-gray-500 hover:bg-gray-50' }}">
                                        @if ($active && $s->value === 'OK')✓ @endif{{ $statusLabel($s->value) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Editor catatan (muncul otomatis untuk REVISI/TOLAK, atau saat diklik). --}}
                        @if ($editingBerkasId === $berkas->id)
                            <div class="mt-3 bg-[#e6f4ff]/40 border border-[#91caff] rounded-md p-4 flex flex-col gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-semibold text-[#0958d9] uppercase">Catatan untuk berkas ber-status</span>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $statusColor($cur) }}">{{ $cur }}</span>
                                </div>

                                @if ($catatanOptions->isNotEmpty())
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[11px] font-semibold text-[#0958d9] uppercase">Catatan dari Katalog</label>
                                        <div class="flex flex-col gap-1 max-h-40 overflow-y-auto">
                                            @foreach ($catatanOptions as $mc)
                                                <label class="flex items-start gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" wire:model="selectedCatatanIds" value="{{ $mc->id }}" class="mt-0.5 rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
                                                    <span>{{ $mc->teks }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[11px] font-semibold text-[#0958d9] uppercase">Catatan Tambahan</label>
                                    <textarea wire:model="customCatatan" rows="2" placeholder="Catatan khusus (opsional)" class="border border-[#91caff] rounded px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1677ff]"></textarea>
                                </div>

                                <div class="flex gap-2">
                                    <button wire:click="saveCatatan" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-4 py-1.5 text-xs font-medium">Simpan Catatan</button>
                                    <button wire:click="cancelPeriksa" class="bg-white border border-gray-300 text-gray-600 rounded px-4 py-1.5 text-xs">Tutup</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif
        @endif
    @else
        <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">Pilih permohonan untuk memeriksa berkasnya.</div>
    @endif

    {{-- Print preview modal: previews the sheet on screen; the Cetak button
         prints via a hidden iframe loading the standalone sheet route, so the
         printer dialog always receives exactly the sheet (no app chrome). --}}
    @if ($showPrint && $permohonan)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:key="print-modal"
            x-data="{
                cetak() {
                    const frame = document.getElementById('pemeriksaan-print-frame');
                    frame.onload = () => { frame.contentWindow.focus(); frame.contentWindow.print(); };
                    frame.src = '{{ route('pemeriksaan.print', $permohonan->id) }}?t=' + Date.now();
                }
            }">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Pratinjau Lembar Pemeriksaan</h3>
                    <div class="flex gap-2">
                        <button type="button" x-on:click="cetak()" class="inline-flex items-center gap-2 bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-1.5 text-sm font-medium">🖨️ Cetak</button>
                        <button type="button" wire:click="closePrint" class="bg-white border border-gray-300 text-gray-600 rounded-md px-4 py-1.5 text-sm hover:bg-gray-50">Tutup</button>
                    </div>
                </div>
                <div class="overflow-y-auto p-6">
                    @include('pemeriksaan._sheet', ['permohonan' => $permohonan, 'parents' => $printParents, 'childrenMap' => $printChildrenMap])
                </div>
            </div>
            {{-- Off-screen (not display:none — hidden iframes won't print in some browsers). --}}
            <iframe id="pemeriksaan-print-frame" aria-hidden="true" tabindex="-1"
                style="position: absolute; width: 0; height: 0; border: 0; visibility: hidden;"></iframe>
        </div>
    @endif
</div>
