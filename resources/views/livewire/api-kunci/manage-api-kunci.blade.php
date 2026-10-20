<div class="flex flex-col gap-6">
    <x-flash />
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Kunci API Sinkron</h2>
            <p class="text-sm text-gray-500 mt-1">
                Kunci untuk aplikasi BA Risalah (SIPANTAS) menarik data tanah lewat
                <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">GET /api/sinkron/tanah</code>
                dengan header <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">Authorization: Bearer &lt;kunci&gt;</code>.
            </p>
        </div>
        <button wire:click="$toggle('showForm')"
            class="px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm shrink-0
            {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Batal' : '+ Buat Kunci Baru' }}
        </button>
    </div>

    @if ($adaKunciEnv)
        <div class="bg-[#fffbe6] border border-[#ffe58f] rounded-md px-4 py-3 text-sm text-[#ad6800]">
            <code class="text-xs">SINKRON_API_KEY</code> di <code class="text-xs">.env</code> masih terisi dan tetap diterima sebagai kunci cadangan.
            Setelah SIPANTAS memakai kunci dari halaman ini, kosongkan nilai tersebut lalu jalankan <code class="text-xs">php artisan config:clear</code>.
        </div>
    @endif

    {{-- Kunci baru: tampil sekali --}}
    @if ($kunciBaru)
        <div class="bg-[#f6ffed] border border-[#b7eb8f] rounded-lg p-5 flex flex-col gap-3" x-data="{ disalin: false }">
            <p class="text-sm font-semibold text-[#389e0d]">Kunci "{{ $kunciBaruNama }}" berhasil dibuat</p>
            <p class="text-sm text-gray-700">Salin kunci ini sekarang. Kunci <strong>tidak akan ditampilkan lagi</strong> — yang disimpan hanya hash-nya.</p>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" readonly value="{{ $kunciBaru }}" x-ref="kunci" @focus="$el.select()"
                    class="flex-1 min-w-0 font-mono text-sm border border-[#b7eb8f] rounded-md px-3 py-2 bg-white">
                <button type="button"
                    @click="navigator.clipboard.writeText($refs.kunci.value).then(() => { disalin = true; setTimeout(() => disalin = false, 2000) })"
                    class="bg-[#52c41a] hover:bg-[#389e0d] text-white px-4 py-2 rounded-md font-medium text-sm shadow-sm">
                    <span x-text="disalin ? '✓ Tersalin' : 'Salin'">Salin</span>
                </button>
                <button type="button" wire:click="tutupKunciBaru"
                    class="px-4 py-2 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50">
                    Sudah disalin, tutup
                </button>
            </div>
        </div>
    @endif

    {{-- Form --}}
    @if ($showForm)
        <form wire:submit="create" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">Buat Kunci API Baru</h3>
            <div class="flex flex-col gap-1.5 max-w-lg">
                <label class="text-sm font-medium text-gray-700">Nama / Peruntukan <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nama" placeholder="Misal: SIPANTAS Produksi"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                <span class="text-xs text-gray-400">Kunci acak dibuat otomatis oleh sistem.</span>
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    Buat Kunci
                </button>
            </div>
        </form>
    @endif

    <x-search-bar model="search" placeholder="Cari nama atau awalan kunci..." :count="$list->count()" />

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3 text-center w-12">No</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kunci</th>
                    <th class="px-4 py-3">Dibuat</th>
                    <th class="px-4 py-3">Terakhir Dipakai</th>
                    <th class="px-4 py-3 text-center">Aktif</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($list as $i => $k)
                    <tr wire:key="kunci-{{ $k->id }}" class="hover:bg-gray-50 {{ $k->is_active ? '' : 'bg-gray-50/50 opacity-70' }}">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $k->nama }}</td>
                        <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">{{ $k->awalan }}…</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $k->created_at?->translatedFormat('d M Y H:i') }}
                            @if ($k->pembuat)
                                <span class="block text-xs text-gray-400">oleh {{ $k->pembuat->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $k->terakhir_dipakai_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleActive('{{ $k->id }}')"
                                class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors {{ $k->is_active ? 'bg-[#52c41a]' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $k->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $k->id }}')" wire:confirm="Hapus kunci {{ $k->nama }}? Aplikasi yang memakainya akan langsung ditolak.">Hapus</x-action-menu.item>
                                </x-action-menu>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada kunci yang cocok dengan pencarian.' : 'Belum ada kunci API.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
