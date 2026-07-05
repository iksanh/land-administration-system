{{--
    Editor Riwayat Penguasaan (reusable).
    Membutuhkan komponen host memakai trait App\Livewire\Concerns\WithRiwayatPenguasaan
    (menyediakan $riwayat_penguasaan, $typoIndex, $typoResults, $typoError +
    method addRiwayat/removeRiwayat/moveRiwayat/checkTypo/toggleTypo/applyTypo/cancelTypo).

    Param opsional:
      $label — judul field (default "Riwayat Penguasaan").
      $hint  — teks bantuan kecil di bawah judul.
--}}
@php
    $label ??= 'Riwayat Penguasaan';
    $hint ??= 'Tiap poin akan dicetak sebagai butir terpisah.';
@endphp
<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <label class="text-sm font-medium text-gray-700">{{ $label }}</label>
        <button type="button" wire:click="addRiwayat"
            class="text-xs font-medium text-[#1677ff] hover:text-[#0958d9] inline-flex items-center gap-1">
            + Tambah Poin
        </button>
    </div>
    <p class="text-xs text-gray-400 -mt-1">{{ $hint }}</p>
    @forelse ($riwayat_penguasaan as $i => $poin)
        <div class="flex flex-col gap-2" wire:key="riwayat-{{ $i }}">
            <div class="flex items-start gap-2">
                <span class="mt-2 text-xs font-semibold text-gray-400 w-5 text-right shrink-0">{{ $i + 1 }}.</span>
                <div class="flex-1 flex flex-col gap-1.5">
                    <textarea wire:model="riwayat_penguasaan.{{ $i }}" rows="3" placeholder="Misal: Bahwa bidang tanah dikuasai oleh ... sejak tahun ..."
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
                    <button type="button" wire:click="checkTypo({{ $i }})"
                        wire:loading.attr="disabled" wire:target="checkTypo({{ $i }})"
                        class="self-start inline-flex items-center gap-1 text-xs font-medium text-[#1677ff] hover:text-[#0958d9] disabled:opacity-60">
                        <svg wire:loading wire:target="checkTypo({{ $i }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span wire:loading.remove wire:target="checkTypo({{ $i }})">✨ Cek Typo (AI)</span>
                        <span wire:loading wire:target="checkTypo({{ $i }})">Memeriksa…</span>
                    </button>
                </div>
                <div class="flex flex-col gap-1 shrink-0">
                    <button type="button" wire:click="moveRiwayat({{ $i }}, -1)" @disabled($i === 0)
                        class="w-7 h-7 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed text-xs" title="Naik">↑</button>
                    <button type="button" wire:click="moveRiwayat({{ $i }}, 1)" @disabled($i === count($riwayat_penguasaan) - 1)
                        class="w-7 h-7 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed text-xs" title="Turun">↓</button>
                    <button type="button" wire:click="removeRiwayat({{ $i }})"
                        class="w-7 h-7 rounded border border-[#ffa39e] text-[#ff4d4f] hover:bg-[#fff1f0] text-xs" title="Hapus poin">✕</button>
                </div>
            </div>

            {{-- Panel review typo untuk poin ini --}}
            @if ($typoIndex === $i)
                <div class="ml-7 rounded-lg border border-[#91caff] bg-[#e6f4ff] p-3 flex flex-col gap-3">
                    @if ($typoError)
                        <p class="text-sm text-red-700">{{ $typoError }}</p>
                        <button type="button" wire:click="cancelTypo" class="self-start text-xs text-gray-600 hover:underline">Tutup</button>
                    @elseif (count($typoResults) === 0)
                        <p class="text-sm text-green-700 inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Tidak ditemukan typo pada poin ini.
                        </p>
                        <button type="button" wire:click="cancelTypo" class="self-start text-xs text-gray-600 hover:underline">Tutup</button>
                    @else
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-[#0958d9] mb-1.5">Pratinjau — klik kata untuk ganti / biarkan</p>
                            <div class="text-sm leading-7 text-gray-800 whitespace-pre-wrap break-words bg-white rounded-md border border-gray-200 px-3 py-2">
                                @foreach ($this->typoSegments() as $seg)
                                    @if ($seg['type'] === 'text'){{ $seg['content'] }}@elseif ($seg['accepted'])<button type="button" wire:click="toggleTypo({{ $seg['index'] }})" title="Asli: {{ $seg['original'] }} — {{ $seg['reason'] }}" class="inline rounded bg-green-100 px-1 font-medium text-green-800 ring-1 ring-green-300 hover:bg-green-200 cursor-pointer">{{ $seg['suggestion'] }}</button>@else<button type="button" wire:click="toggleTypo({{ $seg['index'] }})" title="Saran: {{ $seg['suggestion'] }} — {{ $seg['reason'] }}" class="inline rounded bg-amber-100 px-1 text-amber-800 line-through decoration-amber-500 ring-1 ring-amber-300 hover:bg-amber-200 cursor-pointer">{{ $seg['original'] }}</button>@endif
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($typoResults as $ti => $r)
                                <button type="button" wire:click="toggleTypo({{ $ti }})" title="{{ $r['reason'] }}"
                                    class="inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs {{ $r['accepted'] ? 'border-green-300 bg-green-50 text-green-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}">
                                    <span class="line-through opacity-70">{{ $r['original'] }}</span>
                                    <span>→</span>
                                    <span class="font-medium">{{ $r['suggestion'] }}</span>
                                    <span class="ml-0.5 text-[10px] uppercase opacity-70">{{ $r['accepted'] ? 'ganti' : 'biarkan' }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="flex gap-2">
                            <button type="button" wire:click="applyTypo"
                                class="inline-flex items-center gap-1.5 rounded-md bg-[#1677ff] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#0958d9]">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Terapkan koreksi
                            </button>
                            <button type="button" wire:click="cancelTypo"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <p class="text-xs text-gray-400">Belum ada poin. Klik "+ Tambah Poin".</p>
    @endforelse
</div>
