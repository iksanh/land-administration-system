<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Pemetaan Layanan &amp; Berkas</h2>
        <p class="text-sm text-gray-500 mt-1">Atur dokumen persyaratan untuk masing-masing layanan pertanahan.</p>
    </div>

    {{-- Pick layanan --}}
    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-200">
        <label class="text-sm font-medium text-gray-700 block mb-1.5">Pilih Layanan</label>
        <select wire:model.live="selectedLayanan"
            class="w-full md:w-1/2 border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
            <option value="">— Pilih layanan —</option>
            @foreach ($layananList as $l)
                <option value="{{ $l->id }}">{{ $l->kode }} — {{ $l->nama }}</option>
            @endforeach
        </select>
    </div>

    @if ($selectedLayanan)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Left: checklist of all berkas --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200 font-medium text-gray-800 text-sm">Daftar Berkas (centang untuk memetakan)</div>
                <ul class="divide-y divide-gray-100 max-h-[28rem] overflow-y-auto">
                    @foreach ($berkasItems as $b)
                        <li class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50">
                            <input type="checkbox"
                                wire:click="toggle('{{ $b->id }}')"
                                @checked(in_array($b->id, $mappedIds))
                                class="rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
                            <span class="text-sm text-gray-700">{{ $b->nama }}</span>
                            @if ($b->is_mandatory)
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-600 border border-red-200">Wajib</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Right: mapped berkas, reorder by drag-and-drop (wire:sort) or arrows --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 font-medium text-gray-800 text-sm">
                        Berkas Terpetakan &amp; Urutan
                        <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-[#e6f4ff] text-[#1677ff] text-[11px] font-semibold">{{ $mapped->count() }}</span>
                    </div>
                    <span wire:loading.flex wire:target="sortBerkas,moveBerkas,toggle"
                        class="hidden items-center gap-1.5 text-xs text-[#1677ff]">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        Menyimpan…
                    </span>
                </div>

                @if ($mapped->isNotEmpty())
                    <div class="px-4 py-2 bg-[#fafafa] border-b border-gray-100 text-xs text-gray-500 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/></svg>
                        Seret ikon titik untuk mengubah urutan, atau gunakan tombol panah. Perubahan tersimpan otomatis.
                    </div>
                @endif

                <ul wire:sort.ghost="sortBerkas"
                    class="divide-y divide-gray-100 max-h-[28rem] overflow-y-auto">
                    @forelse ($mapped as $m)
                        <li wire:key="map-{{ $m->berkas_item_id }}" wire:sort:item="{{ $m->berkas_item_id }}"
                            class="group flex items-center gap-3 px-3 py-2.5 bg-white hover:bg-gray-50 transition-colors">
                            <button type="button" wire:sort:handle title="Seret untuk mengubah urutan"
                                class="p-1 -m-1 rounded text-gray-300 group-hover:text-gray-500 hover:bg-gray-100 cursor-grab active:cursor-grabbing touch-none">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/></svg>
                            </button>

                            <span class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-[#e6f4ff] text-[#1677ff] text-xs font-semibold">{{ $loop->iteration }}</span>

                            <div class="flex-1 min-w-0 flex items-center gap-2">
                                <span class="text-sm text-gray-800 truncate">{{ $m->berkasItem->nama ?? '—' }}</span>
                                @if ($m->berkasItem?->is_mandatory)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-600 border border-red-200 shrink-0">Wajib</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" title="Naikkan" @disabled($loop->first)
                                    wire:click="moveBerkas('{{ $m->berkas_item_id }}', -1)"
                                    class="p-1 rounded-md border border-gray-200 text-gray-500 hover:text-[#1677ff] hover:border-[#1677ff] hover:bg-[#e6f4ff] disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                                </button>
                                <button type="button" title="Turunkan" @disabled($loop->last)
                                    wire:click="moveBerkas('{{ $m->berkas_item_id }}', 1)"
                                    class="p-1 rounded-md border border-gray-200 text-gray-500 hover:text-[#1677ff] hover:border-[#1677ff] hover:bg-[#e6f4ff] disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                </button>
                                <x-action-btn icon="delete" variant="danger" class="ml-1" wire:click="toggle('{{ $m->berkas_item_id }}')">Hapus</x-action-btn>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-10 text-center text-sm text-gray-400">
                            Belum ada berkas dipetakan.<br>
                            <span class="text-xs">Centang berkas di daftar sebelah kiri untuk menambahkannya.</span>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">Pilih layanan untuk mengatur berkas persyaratannya.</div>
    @endif
</div>
