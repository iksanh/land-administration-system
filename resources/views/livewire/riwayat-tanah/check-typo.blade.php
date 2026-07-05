<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Cek Typo Riwayat Tanah</h2>
        <p class="text-sm text-gray-500 mt-1">
            Periksa ejaan bahasa Indonesia pada teks riwayat tanah menggunakan AI (Gemini).
            Istilah seperti SHM, HGB, Girik, Letter C, Roya, dan PTSL tidak dianggap typo.
        </p>
    </div>

    @if (! $checked)
        {{-- ===================== MODE INPUT ===================== --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <form wire:submit="check" class="flex flex-col gap-4">
                <div>
                    <label for="text" class="block text-sm font-medium text-gray-700 mb-1.5">Teks Riwayat Tanah</label>
                    <textarea id="text" wire:model="text" rows="10"
                        placeholder="Tempel atau ketik teks riwayat tanah di sini…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-[#1677ff] focus:ring-1 focus:ring-[#1677ff] focus:outline-none resize-y"></textarea>
                    @error('text')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if ($error)
                    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
                @endif

                <div class="flex items-center gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="check"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#1677ff] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#0958d9] disabled:opacity-60 disabled:cursor-not-allowed transition">
                        <svg wire:loading wire:target="check" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="check">Periksa Typo</span>
                        <span wire:loading wire:target="check">Memeriksa…</span>
                    </button>
                    <p class="text-xs text-gray-400">{{ strlen($text) }} karakter</p>
                </div>
            </form>
        </div>
    @else
        {{-- ===================== MODE REVIEW ===================== --}}
        @if (count($results) === 0)
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Tidak ditemukan typo. Teks sudah baik.
                </div>
                <button wire:click="resetForm" class="mt-4 text-sm font-medium text-[#1677ff] hover:underline">← Periksa teks lain</button>
            </div>
        @else
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-sm text-gray-600">
                    Ditemukan <span class="font-semibold text-gray-800">{{ count($results) }}</span> potensi typo.
                    <span class="text-green-600 font-medium">{{ $acceptedCount }} akan diganti</span>,
                    <span class="text-gray-500">{{ count($results) - $acceptedCount }} dibiarkan</span>.
                </p>
                <div class="flex gap-2 ml-auto text-xs">
                    <button wire:click="setAll(true)" class="rounded-md border border-gray-300 px-2.5 py-1 text-gray-600 hover:bg-gray-50">Ganti semua</button>
                    <button wire:click="setAll(false)" class="rounded-md border border-gray-300 px-2.5 py-1 text-gray-600 hover:bg-gray-50">Biarkan semua</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Teks dengan highlight inline --}}
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-800">Teks dengan Highlight</h3>
                        <p class="text-xs text-gray-400">Klik kata untuk ganti / biarkan</p>
                    </div>
                    <div class="text-sm leading-7 text-gray-800 whitespace-pre-wrap break-words">
                        @foreach ($segments as $seg)
                            @if ($seg['type'] === 'text')
                                {{ $seg['content'] }}
                            @elseif ($seg['accepted'])
                                <button type="button" wire:click="toggle({{ $seg['index'] }})"
                                    title="Asli: {{ $seg['original'] }} — {{ $seg['reason'] }} (klik untuk biarkan)"
                                    class="inline rounded bg-green-100 px-1 font-medium text-green-800 ring-1 ring-green-300 hover:bg-green-200 cursor-pointer">{{ $seg['suggestion'] }}</button>
                            @else
                                <button type="button" wire:click="toggle({{ $seg['index'] }})"
                                    title="Saran: {{ $seg['suggestion'] }} — {{ $seg['reason'] }} (klik untuk ganti)"
                                    class="inline rounded bg-amber-100 px-1 text-amber-800 line-through decoration-amber-500 ring-1 ring-amber-300 hover:bg-amber-200 cursor-pointer">{{ $seg['original'] }}</button>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex gap-4 mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-100 ring-1 ring-green-300"></span> Diganti</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 ring-1 ring-amber-300"></span> Dibiarkan (asli)</span>
                    </div>
                </div>

                {{-- Daftar konfirmasi per kata --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Konfirmasi Perubahan</h3>
                    <ul class="flex flex-col divide-y divide-gray-100">
                        @foreach ($results as $i => $r)
                            <li class="py-3 first:pt-0">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="rounded bg-red-50 px-1.5 py-0.5 text-red-600 line-through">{{ $r['original'] }}</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                    <span class="rounded bg-green-50 px-1.5 py-0.5 font-medium text-green-700">{{ $r['suggestion'] }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $r['reason'] }}</p>
                                <div class="flex gap-2 mt-2">
                                    <button wire:click="toggle({{ $i }})"
                                        class="rounded-md px-2.5 py-1 text-xs font-medium {{ $r['accepted'] ? 'bg-[#1677ff] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $r['accepted'] ? '✓ Diganti' : 'Ganti' }}
                                    </button>
                                    <button wire:click="toggle({{ $i }})"
                                        class="rounded-md px-2.5 py-1 text-xs font-medium {{ ! $r['accepted'] ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ ! $r['accepted'] ? '✓ Dibiarkan' : 'Biarkan' }}
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Hasil akhir --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6" x-data="{ copied: false }">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-800">Teks Terkoreksi</h3>
                    <button type="button"
                        @click="navigator.clipboard.writeText($refs.corrected.textContent.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m11.25 4.125v3.375" /></svg>
                        <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                    </button>
                </div>
                <pre x-ref="corrected" class="text-sm leading-7 text-gray-800 whitespace-pre-wrap break-words font-sans bg-gray-50 rounded-lg border border-gray-100 p-4">{{ $corrected }}</pre>

                <div class="flex flex-wrap gap-3 mt-4">
                    <button wire:click="applyToText"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#1677ff] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#0958d9] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Terapkan & Edit Ulang
                    </button>
                    <button wire:click="resetForm"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                        Periksa Teks Lain
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>
