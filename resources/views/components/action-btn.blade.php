@props([
    'icon' => null,
    'variant' => 'neutral',
])

@php
    // Subtle Ant-flavoured outline buttons that tint on hover.
    $variants = [
        'primary' => 'text-[#1677ff] border-[#91caff] hover:bg-[#e6f4ff] hover:border-[#1677ff]',
        'danger' => 'text-[#ff4d4f] border-[#ffa39e] hover:bg-[#fff1f0] hover:border-[#ff4d4f]',
        'purple' => 'text-[#722ed1] border-[#d3adf7] hover:bg-[#f9f0ff] hover:border-[#722ed1]',
        'warning' => 'text-[#d48806] border-[#ffd591] hover:bg-[#fff7e6] hover:border-[#faad14]',
        'neutral' => 'text-gray-600 border-gray-300 hover:bg-gray-50 hover:border-gray-400',
    ];

    // Heroicons (outline) path data, keyed by name.
    $icons = [
        'edit' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125',
        'delete' => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'status' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        'key' => 'M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z',
    ];

    $cls = $variants[$variant] ?? $variants['neutral'];
    $path = $icons[$icon] ?? null;
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors $cls"]) }}>
    @if ($path)
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
        </svg>
    @endif
    {{ $slot }}
</button>
