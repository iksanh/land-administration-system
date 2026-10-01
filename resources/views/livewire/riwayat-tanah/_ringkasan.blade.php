{{--
    Kartu ringkasan Riwayat Penguasaan (reusable) untuk dokumen yang MEMAKAI
    riwayat tapi bukan pemiliknya — Berita Acara & Risalah. Menampilkan jumlah
    poin + cuplikan, dan tombol yang membuka editor modal (`_modal`).

    Membutuhkan host memakai trait App\Livewire\Concerns\WithRiwayatPenguasaan.

    Param:
      $permohonanId — permohonan yang riwayatnya ditampilkan (wajib, boleh '').
      $label        — judul field (default "Riwayat Penguasaan").
--}}
@php
    $label ??= 'Riwayat Penguasaan';
    $poinRingkas = array_values(array_filter(array_map('trim', $riwayat_penguasaan)));
@endphp
<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between gap-2 flex-wrap">
        <label class="text-sm font-medium text-gray-700">{{ $label }}</label>
        <span class="text-[11px] text-gray-400 inline-flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
            Data bersama — dipakai Berita Acara, Risalah &amp; SK
        </span>
    </div>

    @if (! $permohonanId)
        <div class="border border-gray-200 bg-gray-50 rounded-md px-4 py-3 text-sm text-gray-500">
            Pilih permohonan terlebih dahulu.
        </div>
    @elseif (count($poinRingkas))
        <div class="border border-gray-200 rounded-md bg-white px-4 py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm text-gray-700 font-medium">{{ count($poinRingkas) }} poin riwayat penguasaan</p>
                <p class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($poinRingkas[0], 90) }}</p>
            </div>
            <button type="button" wire:click="openRiwayat('{{ $permohonanId }}')"
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border border-[#91caff] text-[#1677ff] bg-[#e6f4ff] hover:bg-[#bae0ff]">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>
                Lihat &amp; Ubah
            </button>
        </div>
    @else
        <div class="border border-[#ffe58f] bg-[#fffbe6] rounded-md px-4 py-3 flex items-center justify-between gap-3">
            <p class="text-sm text-[#ad8b00] min-w-0">Riwayat penguasaan belum diisi untuk permohonan ini.</p>
            <button type="button" wire:click="openRiwayat('{{ $permohonanId }}')"
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-[#1677ff] text-white hover:bg-[#0958d9]">
                + Isi Riwayat
            </button>
        </div>
    @endif
</div>
