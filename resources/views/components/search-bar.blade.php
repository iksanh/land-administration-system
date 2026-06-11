@props(['model' => 'search', 'placeholder' => 'Cari...', 'count' => null])

{{-- Reusable list toolbar: live search box on the left, result count on the right. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center gap-3']) }}>
    <div class="relative w-full sm:max-w-xs">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input type="search" wire:model.live.debounce.300ms="{{ $model }}" placeholder="{{ $placeholder }}"
            class="w-full border border-gray-300 rounded-md pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
    </div>
    @unless (is_null($count))
        <span class="text-xs text-gray-500 sm:ml-auto shrink-0">
            <span wire:loading.remove wire:target="{{ $model }}">{{ $count }} data</span>
            <span wire:loading wire:target="{{ $model }}" class="text-[#1677ff]">Mencari…</span>
        </span>
    @endunless
</div>
