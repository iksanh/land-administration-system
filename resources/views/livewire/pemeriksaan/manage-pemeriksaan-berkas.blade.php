<div class="flex flex-col gap-6">
    @php
        $statusColor = fn ($s) => match ($s) {
            'OK' => 'bg-[#f6ffed] text-[#389e0d] border-[#b7eb8f]',
            'REVISI' => 'bg-[#fff7e6] text-[#d46b08] border-[#ffd591]',
            'TOLAK' => 'bg-[#fff1f0] text-[#cf1322] border-[#ffa39e]',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    @endphp

    <x-flash />

    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Pemeriksaan Berkas</h2>
        <p class="text-sm text-gray-500 mt-1">Periksa kelengkapan berkas per permohonan dan beri catatan.</p>
    </div>

    {{-- Pick permohonan --}}
    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex-1">
            <label class="text-sm font-medium text-gray-700 block mb-1.5">Pilih Permohonan</label>
            <select wire:model.live="selectedPermohonan"
                class="w-full md:w-2/3 border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                <option value="">— Pilih permohonan —</option>
                @foreach ($permohonanList as $p)
                    <option value="{{ $p->id }}">{{ $p->nomor_registrasi }} — {{ $p->pemohon?->nama ?? 'Tanpa pemohon' }}</option>
                @endforeach
            </select>
        </div>
        @if ($selectedPermohonan)
            <button type="button" wire:click="openPrint"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-[#1677ff] bg-white border border-[#1677ff] hover:bg-[#e6f4ff] shrink-0">
                🖨️ Cetak Lembar Pemeriksaan
            </button>
        @endif
    </div>

    @if ($selectedPermohonan)
        @if ($berkasList->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                Layanan permohonan ini belum memiliki berkas yang dipetakan (atur di Pemetaan Berkas).
            </div>
        @else
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm divide-y divide-gray-100">
                @foreach ($berkasList as $berkas)
                    @php $row = $pemeriksaan->get($berkas->id); @endphp
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-800">{{ $berkas->nama }}</span>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $statusColor($row?->status?->value ?? 'PENDING') }}">
                                        {{ $row?->status?->value ?? 'PENDING' }}
                                    </span>
                                </div>
                                @if ($row && $row->catatan)
                                    <ul class="mt-1 text-sm text-gray-500 list-disc list-inside">
                                        @foreach ($row->catatan as $c)
                                            <li>{{ $c['teks'] }} @if($c['is_custom'])<span class="text-[10px] text-[#722ed1]">(custom)</span>@endif</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <button wire:click="startPeriksa('{{ $berkas->id }}')" class="text-[#1677ff] hover:text-[#0958d9] font-medium text-xs shrink-0">Periksa</button>
                        </div>

                        {{-- Inline check panel --}}
                        @if ($editingBerkasId === $berkas->id)
                            <div class="mt-3 bg-[#e6f4ff]/40 border border-[#91caff] rounded-md p-4 flex flex-col gap-3">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[11px] font-semibold text-[#0958d9] uppercase">Status</label>
                                    <select wire:model="formStatus" class="w-48 border border-[#91caff] rounded px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}">{{ $s->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('formStatus') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
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
                                    <button wire:click="savePeriksa" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded px-4 py-1.5 text-xs font-medium">Simpan Pemeriksaan</button>
                                    <button wire:click="cancelPeriksa" class="bg-white border border-gray-300 text-gray-600 rounded px-4 py-1.5 text-xs">Batal</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
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
