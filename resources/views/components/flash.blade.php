{{-- Auto-dismissing toast for session flash. Included inside each Livewire
     component view (not the layout) because Livewire re-renders only the
     component on an action — the persistent layout would never show it. --}}
@php($msg = session('message'))
@php($err = session('error'))
@if ($msg || $err)
    <div wire:key="flash-{{ uniqid() }}"
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 4000)"
        x-show="show"
        x-transition.opacity.duration.300ms
        class="fixed top-5 right-5 z-[60] flex items-center gap-3 border rounded-lg px-4 py-3 text-sm shadow-lg
            {{ $err ? 'bg-[#fff1f0] border-[#ffa39e] text-[#cf1322]' : 'bg-[#f6ffed] border-[#b7eb8f] text-[#389e0d]' }}">
        <span>{{ $err ?: $msg }}</span>
        <button type="button" x-on:click="show = false" class="opacity-50 hover:opacity-100 leading-none">✕</button>
    </div>
@endif
