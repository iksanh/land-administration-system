{{--
    Menu aksi "three-dot" (kebab) ala aplikasi enterprise — menghemat tinggi
    baris tabel. Panel diposisikan fixed (dihitung saat dibuka) sehingga tidak
    terpotong oleh wrapper tabel yang overflow-x-auto, dan membalik ke atas
    bila dekat tepi bawah viewport. Isi slot dengan <x-action-menu.item> dan
    <x-action-menu.divider>.
--}}
@props(['label' => 'Aksi'])

<div x-data="{
        open: false,
        toggle() {
            if (this.open) { this.open = false; return; }
            this.open = true;
            this.$nextTick(() => {
                const r = this.$refs.trigger.getBoundingClientRect();
                const panel = this.$refs.panel;
                const w = panel.offsetWidth, h = panel.offsetHeight;
                let left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8));
                let top = r.bottom + 4;
                if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 4);
                panel.style.left = left + 'px';
                panel.style.top = top + 'px';
            });
        }
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    x-on:scroll.capture.window="open = false"
    x-on:resize.window="open = false"
    class="inline-block">

    <button type="button" x-ref="trigger" x-on:click="toggle()" title="{{ $label }}" aria-label="{{ $label }}"
        class="w-8 h-8 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
        x-bind:class="open && 'bg-gray-100 text-gray-700'">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="5" r="1.7" /><circle cx="12" cy="12" r="1.7" /><circle cx="12" cy="19" r="1.7" />
        </svg>
    </button>

    <div x-ref="panel" x-show="open" x-cloak x-transition.opacity.duration.100ms
        x-on:click="open = false"
        class="fixed z-40 min-w-44 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-left">
        {{ $slot }}
    </div>
</div>
