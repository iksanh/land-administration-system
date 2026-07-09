{{--
    Editor daftar string terurut (reusable). Membutuhkan komponen host memakai
    trait App\Livewire\Concerns\WithOrderedLists (addListItem/removeListItem/moveListItem).

    Param:
      $prop        — nama properti array pada host (WAJIB), mis. 'data_pendukung'.
      $label       — judul field.
      $hint        — teks bantuan kecil di bawah judul.
      $rows        — tinggi textarea (default 2).
      $placeholder — placeholder textarea.
      $addLabel    — label tombol tambah (default "+ Tambah Baris").
--}}
@php
    $items = data_get($this, $prop) ?? [];
    $label ??= 'Daftar';
    $hint ??= null;
    $rows ??= 2;
    $placeholder ??= '';
    $addLabel ??= '+ Tambah Baris';
@endphp
<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <label class="text-sm font-medium text-gray-700">{{ $label }}</label>
        <button type="button" wire:click="addListItem('{{ $prop }}')"
            class="text-xs font-medium text-[#1677ff] hover:text-[#0958d9] inline-flex items-center gap-1">
            {{ $addLabel }}
        </button>
    </div>
    @if ($hint)<p class="text-xs text-gray-400 -mt-1">{{ $hint }}</p>@endif

    @forelse ($items as $i => $baris)
        <div class="flex items-start gap-2" wire:key="{{ $prop }}-{{ $i }}">
            <span class="mt-2 text-xs font-semibold text-gray-400 w-5 text-right shrink-0">{{ $i + 1 }}.</span>
            <textarea wire:model="{{ $prop }}.{{ $i }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
                class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
            <div class="flex flex-col gap-1 shrink-0">
                <button type="button" wire:click="moveListItem('{{ $prop }}', {{ $i }}, -1)" @disabled($i === 0)
                    class="w-7 h-7 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed text-xs" title="Naik">↑</button>
                <button type="button" wire:click="moveListItem('{{ $prop }}', {{ $i }}, 1)" @disabled($i === count($items) - 1)
                    class="w-7 h-7 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed text-xs" title="Turun">↓</button>
                <button type="button" wire:click="removeListItem('{{ $prop }}', {{ $i }})"
                    class="w-7 h-7 rounded border border-[#ffa39e] text-[#ff4d4f] hover:bg-[#fff1f0] text-xs" title="Hapus baris">✕</button>
            </div>
        </div>
    @empty
        <p class="text-xs text-gray-400">Belum ada baris. Klik "{{ $addLabel }}".</p>
    @endforelse
</div>
