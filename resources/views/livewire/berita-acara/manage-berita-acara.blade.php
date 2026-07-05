<div class="flex flex-col gap-6">
    <x-flash />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Berita Acara Pemeriksaan Lapang</h2>
            <p class="text-sm text-gray-500 mt-1">Susun dan cetak Berita Acara oleh Panitia Pemeriksa Tanah A.</p>
        </div>
        <button wire:click="{{ $showForm ? 'resetForm' : '$set(\'showForm\', true)' }}"
            class="px-4 py-2 rounded-md font-medium text-sm shadow-sm {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Tutup Form' : '+ Tambah Berita Acara' }}
        </button>
    </div>

    {{-- Form --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-5">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">
                {{ $editingId ? 'Edit Berita Acara' : 'Berita Acara Baru' }}
            </h3>

            {{-- Permohonan & nomor --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Permohonan <span class="text-red-500">*</span></label>
                    @if ($editingId)
                        <input type="text" disabled value="{{ $selectedTanah?->nomor_registrasi }} — {{ $selectedTanah?->pemohon?->nama }}"
                            class="border border-gray-200 rounded-md px-3 py-2 text-sm bg-gray-100 text-gray-600">
                    @else
                        <select wire:model.live="permohonan_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                            <option value="">— Pilih permohonan —</option>
                            @foreach ($permohonanList as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->nomor_registrasi }} — {{ $opt->pemohon?->nama ?? 'Tanpa pemohon' }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('permohonan_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Nomor Berita Acara</label>
                    <input type="text" wire:model="nomor_ba" placeholder="Opsional"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
            </div>

            {{-- Pratinjau data tanah (read-only, otomatis dari permohonan) --}}
            @if ($selectedTanah && $selectedTanah->tanah)
                @php $t = $selectedTanah->tanah; @endphp
                <div class="bg-[#e6f4ff]/40 border border-[#91caff] rounded-md p-4 text-sm">
                    <p class="text-xs font-semibold text-[#0958d9] uppercase tracking-wide mb-2">Data tanah otomatis (dari permohonan)</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-gray-700">
                        <div><span class="text-gray-400 block text-[11px]">Luas</span>{{ $t->luas ? rtrim(rtrim(number_format($t->luas, 2), '0'), '.') : '—' }} m²</div>
                        <div><span class="text-gray-400 block text-[11px]">No. PBT</span>{{ $t->nomor_pbt ?: '—' }}</div>
                        <div><span class="text-gray-400 block text-[11px]">NIB</span>{{ $t->nib ?: '—' }}</div>
                        <div><span class="text-gray-400 block text-[11px]">Penggunaan</span>{{ $t->penggunaan_tanah ?: '—' }}</div>
                        <div class="col-span-2 md:col-span-4"><span class="text-gray-400 block text-[11px]">Lokasi</span>
                            Desa {{ $t->desa?->nama ?? '—' }}, Kec. {{ $t->desa?->kecamatan?->nama ?? '—' }},
                            {{ $t->desa?->kecamatan?->kabupaten?->nama ?? '—' }}, {{ $t->desa?->kecamatan?->kabupaten?->provinsi?->nama ?? '—' }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tanggal & narasi --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tanggal Pemeriksaan Lapang</label>
                    <input type="date" wire:model="tgl_pemeriksaan"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
            </div>
            @include('livewire.riwayat-tanah._editor', [
                'label' => 'Riwayat Penguasaan (1.a)',
                'hint' => 'Tiap poin akan dicetak sebagai butir terpisah pada bagian 1.a.',
            ])
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Keadaan Tanah / Existing (1.c)</label>
                    <textarea wire:model="keadaan_tanah" rows="3" placeholder="Keadaan tanah saat ini..."
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Pernyataan Keberatan (3)</label>
                    <textarea wire:model="catatan_keberatan" rows="3"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Referensi Perda RTRW</label>
                <input type="text" wire:model="perda_rtrw"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
            </div>

            {{-- Panitia --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-700">Anggota Panitia Penandatangan</label>
                @if ($panitiaList->isEmpty())
                    <p class="text-xs text-amber-600">Belum ada anggota panitia aktif. Tambahkan dulu di menu <a href="{{ route('panitia') }}" class="underline" wire:navigate>Panitia Pemeriksa</a>.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach ($panitiaList as $anggota)
                            <label class="flex items-start gap-2 border border-gray-200 rounded-md px-3 py-2 bg-white cursor-pointer hover:border-[#1677ff]">
                                <input type="checkbox" wire:model="selectedPanitia" value="{{ $anggota->id }}" class="mt-0.5 accent-[#1677ff]">
                                <span class="text-sm">
                                    <span class="font-medium text-gray-800">{{ $anggota->nama }}</span>
                                    <span class="block text-[11px] text-gray-500">{{ $anggota->peran->label() }}{{ $anggota->jabatan ? ' — '.$anggota->jabatan : '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('selectedPanitia.*') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Lampiran foto --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-700">Lampiran Dokumentasi (foto)</label>
                <input type="file" wire:model="newPhotos" multiple accept="image/*"
                    class="text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-[#e6f4ff] file:text-[#1677ff] file:text-sm file:font-medium">
                <span class="text-xs text-gray-400">Format gambar, maks 5 MB per foto. Opsional — berita acara tetap bisa dicetak tanpa foto.</span>
                @error('newPhotos.*') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                <div wire:loading wire:target="newPhotos" class="text-xs text-[#1677ff]">Mengunggah foto...</div>

                @if (count($newPhotos))
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach ($newPhotos as $photo)
                            @if (is_object($photo) && method_exists($photo, 'temporaryUrl'))
                                <img src="{{ $photo->temporaryUrl() }}" class="w-20 h-20 object-cover rounded border border-gray-200">
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($lampiranList->count())
                    <p class="text-[11px] text-gray-400 uppercase tracking-wide mt-2">Foto tersimpan</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($lampiranList as $lampiran)
                            <div class="relative group">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($lampiran->path) }}" class="w-20 h-20 object-cover rounded border border-gray-200">
                                <button type="button" wire:click="removeLampiran('{{ $lampiran->id }}')" wire:confirm="Hapus foto ini?"
                                    class="absolute -top-1.5 -right-1.5 bg-[#ff4d4f] text-white rounded-full w-5 h-5 text-xs leading-none flex items-center justify-center shadow">✕</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan Berita Acara' }}
                </button>
                @if ($editingId)
                    <a href="{{ route('berita-acara.print', $editingId) }}" target="_blank"
                        class="bg-white border border-[#1677ff] text-[#1677ff] hover:bg-[#e6f4ff] px-6 py-2 rounded-md font-medium text-sm inline-flex items-center gap-2">
                        🖨️ Cetak / Preview PDF
                    </a>
                    <a href="{{ route('berita-acara.word', $editingId) }}"
                        class="bg-white border border-[#52c41a] text-[#389e0d] hover:bg-[#f6ffed] px-6 py-2 rounded-md font-medium text-sm inline-flex items-center gap-2">
                        ⬇️ Download Word
                    </a>
                @endif
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Tutup</button>
            </div>
        </form>
    @endif

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari nomor BA, no. registrasi, atau pemohon..." :count="$list->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3">Nomor BA</th>
                    <th class="px-4 py-3">No. Registrasi</th>
                    <th class="px-4 py-3">Pemohon</th>
                    <th class="px-4 py-3">Tgl. Pemeriksaan</th>
                    <th class="px-4 py-3 text-center w-44">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($list as $ba)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $ba->nomor_ba ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $ba->permohonan?->nomor_registrasi ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $ba->permohonan?->pemohon?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $ba->tgl_pemeriksaan?->locale('id')->translatedFormat('d F Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <x-action-btn icon="edit" variant="primary" wire:click="edit('{{ $ba->id }}')">Edit</x-action-btn>
                                <a href="{{ route('berita-acara.print', $ba->id) }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors text-[#722ed1] border-[#d3adf7] hover:bg-[#f9f0ff] hover:border-[#722ed1]">
                                    🖨️ Cetak
                                </a>
                                <a href="{{ route('berita-acara.word', $ba->id) }}"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors text-[#389e0d] border-[#b7eb8f] hover:bg-[#f6ffed] hover:border-[#52c41a]">
                                    ⬇️ Word
                                </a>
                                <x-action-btn icon="delete" variant="danger" wire:click="delete('{{ $ba->id }}')" wire:confirm="Hapus Berita Acara ini?">Hapus</x-action-btn>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada Berita Acara yang cocok.' : 'Belum ada Berita Acara.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
