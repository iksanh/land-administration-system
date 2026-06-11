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

            {{-- Right: mapped berkas with urutan --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200 font-medium text-gray-800 text-sm">Berkas Terpetakan &amp; Urutan</div>
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                        <tr>
                            <th class="px-4 py-2.5 w-24 text-center">Urutan</th>
                            <th class="px-4 py-2.5">Berkas</th>
                            <th class="px-4 py-2.5 text-center w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($mapped as $m)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-center">
                                    <input type="number" min="1" value="{{ $m->urutan }}"
                                        wire:change="updateUrutan('{{ $m->berkas_item_id }}', $event.target.value)"
                                        class="w-16 border border-gray-300 rounded-md px-2 py-1 text-sm text-center focus:outline-none focus:ring-1 focus:ring-[#1677ff]">
                                </td>
                                <td class="px-4 py-2 text-gray-800">{{ $m->berkasItem->nama ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    <button wire:click="toggle('{{ $m->berkas_item_id }}')" class="text-xs font-medium text-[#ff4d4f] hover:text-[#cf1322]">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Belum ada berkas dipetakan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">Pilih layanan untuk mengatur berkas persyaratannya.</div>
    @endif
</div>
