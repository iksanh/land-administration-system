<div class="flex flex-col gap-6">
    <x-flash />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Riwayat Penguasaan Tanah</h2>
            <p class="text-sm text-gray-500 mt-1">
                Diisi sekali per permohonan, lalu dipakai bersama oleh Berita Acara Lapang, Risalah Panitia A, dan SK.
            </p>
        </div>
        @if ($showForm)
            <button type="button" wire:click="resetForm"
                class="px-4 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 shrink-0">
                Tutup Editor
            </button>
        @endif
    </div>

    {{-- ============================ EDITOR ============================ --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Permohonan <span class="text-red-500">*</span></label>
                <select wire:model.live="permohonan_id"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    <option value="">— pilih permohonan —</option>
                    @foreach ($permohonanList as $p)
                        <option value="{{ $p->id }}">{{ $p->nomor_registrasi }} — {{ $p->pemohon?->nama }}</option>
                    @endforeach
                </select>
                @error('permohonan_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Dampak perubahan: dokumen mana saja yang memakai riwayat ini --}}
            @if ($terpilih)
                <div class="rounded-md border border-[#91caff] bg-[#e6f4ff] px-4 py-3 text-xs text-[#0958d9] flex flex-col gap-1">
                    <span class="font-semibold">Teks ini dipakai bersama</span>
                    <div class="flex flex-wrap gap-x-5 gap-y-1">
                        <span>
                            Berita Acara:
                            @if ($beritaAcara)
                                <a href="{{ route('berita-acara', ['permohonan' => $terpilih->id]) }}" wire:navigate class="underline font-medium">{{ $beritaAcara->nomor_ba ?: 'sudah dibuat' }}</a>
                            @else
                                <span class="opacity-70">belum dibuat</span>
                            @endif
                        </span>
                        <span>
                            Risalah:
                            @if ($risalah)
                                <a href="{{ route('risalah', ['permohonan' => $terpilih->id]) }}" wire:navigate class="underline font-medium">{{ $risalah->nomor_risalah ?: 'sudah dibuat' }}</a>
                            @else
                                <span class="opacity-70">belum dibuat</span>
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            @include('livewire.riwayat-tanah._editor', [
                'label' => 'Poin Riwayat',
                'hint' => 'Tiap poin dicetak sebagai butir terpisah pada dokumen. Poin kosong dibuang saat disimpan.',
            ])

            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    Simpan Riwayat
                </button>
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
            </div>
        </form>
    @endif

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1 min-w-0">
            <x-search-bar model="search" placeholder="Cari nomor registrasi atau nama pemohon..." :count="$list->count()" />
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600 shrink-0 cursor-pointer">
            <input type="checkbox" wire:model.live="hanyaBelumDiisi" class="accent-[#1677ff]">
            Hanya yang belum diisi
        </label>
    </div>

    {{-- Daftar permohonan + status riwayat --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3">No. Registrasi</th>
                    <th class="px-4 py-3">Pemohon</th>
                    <th class="px-4 py-3">Cuplikan Riwayat</th>
                    <th class="px-4 py-3 text-center w-28">Poin</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($list as $p)
                    @php $poin = array_values(array_filter($p->riwayatPenguasaan?->poin ?? [])); @endphp
                    <tr wire:key="riwayat-row-{{ $p->id }}"
                        class="hover:bg-gray-50 {{ $permohonan_id === $p->id ? 'bg-[#e6f4ff]' : '' }}">
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $p->nomor_registrasi }}</td>
                        <td class="px-4 py-3">{{ $p->pemohon?->nama ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-md truncate">
                            {{ count($poin) ? \Illuminate\Support\Str::limit($poin[0], 90) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if (count($poin))
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f6ffed] text-[#52c41a] border border-[#b7eb8f]">{{ count($poin) }} poin</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-[#fffbe6] text-[#d48806] border border-[#ffe58f]">Belum diisi</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="edit" variant="primary" wire:click="editFor('{{ $p->id }}')">
                                        {{ count($poin) ? 'Ubah Riwayat' : 'Isi Riwayat' }}
                                    </x-action-menu.item>
                                    <x-action-menu.item icon="doc" variant="cyan" :href="route('berita-acara', ['permohonan' => $p->id])" wire:navigate>Berita Acara</x-action-menu.item>
                                    <x-action-menu.item icon="doc" variant="purple" :href="route('risalah', ['permohonan' => $p->id])" wire:navigate>Risalah</x-action-menu.item>
                                    @if (count($poin))
                                        <x-action-menu.divider />
                                        <x-action-menu.item icon="delete" variant="danger" wire:click="clear('{{ $p->id }}')"
                                            wire:confirm="Kosongkan riwayat penguasaan {{ $p->nomor_registrasi }}? Dokumen yang memakainya ikut kehilangan teks ini.">Kosongkan</x-action-menu.item>
                                    @endif
                                </x-action-menu>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                            @if ($search !== '')
                                Tidak ada permohonan yang cocok.
                            @elseif ($hanyaBelumDiisi)
                                Semua permohonan sudah punya riwayat penguasaan.
                            @else
                                Belum ada permohonan.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
