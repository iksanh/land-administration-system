<div class="flex flex-col gap-6">
    <x-flash />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Risalah Panitia Pemeriksaan Tanah "A"</h2>
            <p class="text-sm text-gray-500 mt-1">Susun dan cetak Risalah telaah lengkap oleh Panitia Pemeriksa Tanah A.</p>
        </div>
        <button wire:click="{{ $showForm ? 'resetForm' : '$set(\'showForm\', true)' }}"
            class="px-4 py-2 rounded-md font-medium text-sm shadow-sm {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Tutup Form' : '+ Tambah Risalah' }}
        </button>
    </div>

    {{-- Form --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-5">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">
                {{ $editingId ? 'Edit Risalah' : 'Risalah Baru' }}
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
                    <label class="text-sm font-medium text-gray-700">Nomor Risalah</label>
                    <input type="text" wire:model="nomor_risalah" placeholder="mis. 45/2025"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
            </div>

            {{-- Pratinjau data tanah (read-only, otomatis dari permohonan) --}}
            @if ($selectedTanah && $selectedTanah->tanah)
                @php $t = $selectedTanah->tanah; @endphp
                <div class="bg-[#e6f4ff]/40 border border-[#91caff] rounded-md p-4 text-sm">
                    <p class="text-xs font-semibold text-[#0958d9] uppercase tracking-wide mb-2">Data otomatis (dari permohonan)</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-gray-700">
                        <div><span class="text-gray-400 block text-[11px]">Pemohon</span>{{ $selectedTanah->pemohon?->nama ?? '—' }}</div>
                        <div><span class="text-gray-400 block text-[11px]">NIK</span>{{ $selectedTanah->pemohon?->nik ?: '—' }}</div>
                        <div><span class="text-gray-400 block text-[11px]">Luas</span>{{ $t->luas ? rtrim(rtrim(number_format($t->luas, 2), '0'), '.') : '—' }} m²</div>
                        <div><span class="text-gray-400 block text-[11px]">No. PBT / NIB</span>{{ $t->nomor_pbt ?: '—' }} / {{ $t->nib ?: '—' }}</div>
                        <div class="col-span-2 md:col-span-4"><span class="text-gray-400 block text-[11px]">Lokasi</span>
                            Desa {{ $t->desa?->nama ?? '—' }}, Kec. {{ $t->desa?->kecamatan?->nama ?? '—' }},
                            {{ $t->desa?->kecamatan?->kabupaten?->nama ?? '—' }}, {{ $t->desa?->kecamatan?->kabupaten?->provinsi?->nama ?? '—' }}
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-2">Uraian pemohon, tanah, telaah subyek/obyek, analisis, dan kesimpulan akan disusun otomatis dari data ini saat dicetak.</p>
                </div>
            @endif

            {{-- Tanggal & hak --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tanggal Risalah</label>
                    <input type="date" wire:model="tgl_risalah"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Jenis Hak</label>
                    <input type="text" wire:model="jenis_hak"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Jangka Waktu</label>
                    <input type="text" wire:model="jangka_waktu"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tgl. Berita Acara Lapang</label>
                    <input type="date" wire:model="tgl_bap"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
            </div>

            {{-- SK Panitia & kawasan RTRW --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Nomor SK Panitia "A"</label>
                    <input type="text" wire:model="nomor_sk_panitia" placeholder="mis. 134/SK-75.03/V/2025"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Tgl. SK Panitia "A"</label>
                    <input type="date" wire:model="tgl_sk_panitia"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Kawasan RTRW (Peta Analisis)</label>
                    <input type="text" wire:model="rtrw_kawasan" placeholder="mis. Kawasan Permukiman Perdesaan"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                </div>
            </div>

            {{-- Riwayat perolehan tanah — READ-ONLY, referensi dari Berita Acara.
                 Diedit hanya di modul Berita Acara; di sini cukup ditinjau. --}}
            @php $poinRiwayat = array_values(array_filter(array_map('trim', $riwayat_penguasaan))); @endphp
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <label class="text-sm font-medium text-gray-700">Riwayat Perolehan Tanah</label>
                    <span class="text-[11px] text-gray-400 inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a2.25 2.25 0 0 1 2.25 2.25v6.75a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25v-6.75a2.25 2.25 0 0 1 2.25-2.25Z"/></svg>
                        Bersumber dari Berita Acara — tidak dapat diedit di sini
                    </span>
                </div>
                @if (count($poinRiwayat))
                    <div class="border border-gray-200 rounded-md bg-white px-4 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-700 font-medium">{{ count($poinRiwayat) }} poin riwayat penguasaan</p>
                            <p class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($poinRiwayat[0], 90) }}</p>
                            @if ($beritaAcara)
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    Berita Acara{{ $beritaAcara->nomor_ba ? ' No. '.$beritaAcara->nomor_ba : '' }}{{ $beritaAcara->tgl_pemeriksaan ? ' — '.$beritaAcara->tgl_pemeriksaan->locale('id')->translatedFormat('d F Y') : '' }}
                                </p>
                            @endif
                        </div>
                        <button type="button" wire:click="showRiwayatDetail"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border border-[#91caff] text-[#1677ff] bg-[#e6f4ff] hover:bg-[#bae0ff]">
                            🔍 Lihat Detail
                        </button>
                    </div>
                @else
                    <div class="border border-[#ffe58f] bg-[#fffbe6] rounded-md px-4 py-3 text-sm text-[#ad8b00]">
                        Riwayat penguasaan belum diisi.
                        @if ($permohonan_id)
                            Silakan isi terlebih dahulu melalui
                            <a href="{{ route('berita-acara', ['permohonan' => $permohonan_id]) }}" class="underline font-medium" wire:navigate>Berita Acara Lapang</a>.
                        @else
                            Pilih permohonan terlebih dahulu.
                        @endif
                    </div>
                @endif
            </div>

            {{-- Data pendukung (terlampir) --}}
            @include('livewire._list-editor', [
                'prop' => 'data_pendukung',
                'label' => 'Data Pendukung (Terlampir)',
                'hint' => 'Daftar dokumen pendukung yang dilampirkan, mis. "Asli Surat permohonan hak ... tanggal ...".',
                'rows' => 2,
                'placeholder' => 'mis. Asli Surat Pernyataan Penguasaan Fisik Bidang Tanah yang ditandatangani oleh ... tanggal ...',
                'addLabel' => '+ Tambah Data Pendukung',
            ])

            {{-- Dasar hukum --}}
            <div class="flex flex-col gap-1">
                <div class="flex justify-end">
                    <button type="button" wire:click="resetDasarHukum" wire:confirm="Kembalikan daftar dasar hukum ke daftar standar? Perubahan Anda akan hilang."
                        class="text-xs font-medium text-gray-500 hover:text-[#1677ff]">↺ Pulihkan daftar standar</button>
                </div>
                @include('livewire._list-editor', [
                    'prop' => 'dasar_hukum',
                    'label' => 'Dasar Hukum',
                    'hint' => 'Sudah terisi daftar peraturan standar — sesuaikan bila perlu.',
                    'rows' => 2,
                    'placeholder' => 'mis. Undang-Undang Nomor 5 Tahun 1960 tentang Peraturan Dasar Pokok-Pokok Agraria',
                    'addLabel' => '+ Tambah Dasar Hukum',
                ])
            </div>

            {{-- Perda RTRW --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Referensi Perda RTRW</label>
                <input type="text" wire:model="perda_rtrw"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
            </div>

            {{-- Panitia + pendapat per anggota --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-700">Anggota Panitia & Pendapat</label>
                @if ($panitiaList->isEmpty())
                    <p class="text-xs text-amber-600">Belum ada anggota panitia aktif. Tambahkan dulu di menu <a href="{{ route('panitia') }}" class="underline" wire:navigate>Panitia Pemeriksa</a>.</p>
                @else
                    <div class="flex flex-col gap-2">
                        @foreach ($panitiaList as $anggota)
                            <div class="border border-gray-200 rounded-md px-3 py-2 bg-white flex flex-col gap-2" wire:key="panitia-{{ $anggota->id }}">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedPanitia" value="{{ $anggota->id }}" class="mt-0.5 accent-[#1677ff]">
                                    <span class="text-sm">
                                        <span class="font-medium text-gray-800">{{ $anggota->nama }}</span>
                                        <span class="block text-[11px] text-gray-500">{{ $anggota->peran->label() }}{{ $anggota->jabatan ? ' — '.$anggota->jabatan : '' }}</span>
                                    </span>
                                </label>
                                @if (in_array($anggota->id, $selectedPanitia, true))
                                    <textarea wire:model="pendapat.{{ $anggota->id }}" rows="2" placeholder="Pendapat anggota panitia (opsional)..."
                                        class="border border-gray-200 rounded-md px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @error('selectedPanitia.*') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                @if ($kepalaDesaOtomatis->isNotEmpty())
                    <div class="rounded-md border border-[#b7eb8f] bg-[#f6ffed] px-3 py-2 text-xs text-[#389e0d]">
                        Otomatis ditambahkan sebagai penandatangan (Kepala Desa aktif di lokasi tanah):
                        <span class="font-medium">{{ $kepalaDesaOtomatis->pluck('nama')->join(', ') }}</span>.
                        Kelola daftarnya di menu <a href="{{ route('wilayah') }}" class="underline" wire:navigate>Master Wilayah</a>.
                    </div>
                @endif
            </div>

            {{-- Kesimpulan tambahan --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Catatan Tambahan Kesimpulan</label>
                <textarea wire:model="kesimpulan_tambahan" rows="2" placeholder="Opsional — catatan tambahan pada bagian kesimpulan."
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]"></textarea>
            </div>

            <div class="flex flex-wrap gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan Risalah' }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="openPrint('{{ $editingId }}')"
                        class="bg-white border border-[#1677ff] text-[#1677ff] hover:bg-[#e6f4ff] px-6 py-2 rounded-md font-medium text-sm inline-flex items-center gap-2">
                        🖨️ Cetak / Pratinjau
                    </button>
                    <a href="{{ route('risalah.word', $editingId) }}"
                        class="bg-white border border-[#52c41a] text-[#389e0d] hover:bg-[#f6ffed] px-6 py-2 rounded-md font-medium text-sm inline-flex items-center gap-2">
                        ⬇️ Download Word
                    </a>
                @endif
                <button type="button" wire:click="resetForm" class="px-6 py-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Tutup</button>
            </div>
        </form>
    @endif

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari nomor risalah, no. registrasi, atau pemohon..." :count="$list->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3">Nomor Risalah</th>
                    <th class="px-4 py-3">No. Registrasi</th>
                    <th class="px-4 py-3">Pemohon</th>
                    <th class="px-4 py-3">Tgl. Risalah</th>
                    <th class="px-4 py-3 text-center w-44">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($list as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $r->nomor_risalah ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $r->permohonan?->nomor_registrasi ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $r->permohonan?->pemohon?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $r->tgl_risalah?->locale('id')->translatedFormat('d F Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <x-action-btn icon="edit" variant="primary" wire:click="edit('{{ $r->id }}')">Edit</x-action-btn>
                                <button type="button" wire:click="openPrint('{{ $r->id }}')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors text-[#722ed1] border-[#d3adf7] hover:bg-[#f9f0ff] hover:border-[#722ed1]">
                                    🖨️ Cetak
                                </button>
                                <a href="{{ route('risalah.word', $r->id) }}"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border bg-white transition-colors text-[#389e0d] border-[#b7eb8f] hover:bg-[#f6ffed] hover:border-[#52c41a]">
                                    ⬇️ Word
                                </a>
                                <x-action-btn icon="delete" variant="danger" wire:click="delete('{{ $r->id }}')" wire:confirm="Hapus Risalah ini?">Hapus</x-action-btn>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada Risalah yang cocok.' : 'Belum ada Risalah.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal detail riwayat penguasaan (read-only, referensi Berita Acara) --}}
    @if ($showRiwayatModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:key="riwayat-modal">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
                <div class="flex items-start justify-between gap-4 px-5 py-3 border-b border-gray-200">
                    <div>
                        <h3 class="font-semibold text-gray-800">Detail Riwayat Penguasaan</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Bersumber dari Berita Acara Pemeriksaan Lapang — hanya dapat diubah di modul Berita Acara.</p>
                    </div>
                    <button type="button" wire:click="closeRiwayatModal" class="shrink-0 bg-white border border-gray-300 text-gray-600 rounded-md px-4 py-1.5 text-sm hover:bg-gray-50">Tutup</button>
                </div>
                <div class="overflow-y-auto p-6 flex flex-col gap-4">
                    @if ($beritaAcara)
                        <div class="text-xs text-gray-500 flex flex-wrap gap-x-6 gap-y-1 border-b border-gray-100 pb-3">
                            <span>No. Berita Acara: <span class="font-medium text-gray-700">{{ $beritaAcara->nomor_ba ?: '—' }}</span></span>
                            <span>Tgl. Pemeriksaan: <span class="font-medium text-gray-700">{{ $beritaAcara->tgl_pemeriksaan?->locale('id')->translatedFormat('d F Y') ?? '—' }}</span></span>
                        </div>
                    @endif
                    @php $poinDetail = array_values(array_filter(array_map('trim', $riwayat_penguasaan))); @endphp
                    @forelse ($poinDetail as $i => $baris)
                        <div class="flex items-start gap-3" wire:key="riwayat-detail-{{ $i }}">
                            <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full bg-[#e6f4ff] text-[#1677ff] text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                            <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $baris }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Belum ada riwayat penguasaan pada Berita Acara permohonan ini.</p>
                    @endforelse
                </div>
                <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">
                    @if ($permohonan_id)
                        <a href="{{ route('berita-acara', ['permohonan' => $permohonan_id]) }}" wire:navigate
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-md text-sm font-medium border border-[#87e8de] text-[#08979c] bg-white hover:bg-[#e6fffb]">
                            Buka Berita Acara
                        </a>
                    @endif
                    <button type="button" wire:click="closeRiwayatModal" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-1.5 text-sm font-medium">Selesai</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal pratinjau cetak: menampilkan dokumen di layar; tombol Cetak mencetak
         lewat iframe tersembunyi yang memuat rute standalone, sehingga dialog printer
         menerima persis dokumennya (tanpa chrome aplikasi). --}}
    @if ($showPrint && $printRisalah)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:key="risalah-print-modal"
            x-data="{
                cetak() {
                    const frame = document.getElementById('risalah-print-frame');
                    frame.onload = () => { frame.contentWindow.focus(); frame.contentWindow.print(); };
                    frame.src = '{{ route('risalah.print', $printRisalah->id) }}?t=' + Date.now();
                }
            }">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Pratinjau Risalah Panitia Pemeriksaan Tanah "A"</h3>
                    <div class="flex gap-2">
                        <button type="button" x-on:click="cetak()" class="inline-flex items-center gap-2 bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-1.5 text-sm font-medium">🖨️ Cetak</button>
                        <a href="{{ route('risalah.word', $printRisalah->id) }}"
                            class="inline-flex items-center gap-2 bg-white border border-[#52c41a] text-[#389e0d] hover:bg-[#f6ffed] rounded-md px-4 py-1.5 text-sm font-medium">⬇️ Word</a>
                        <button type="button" wire:click="closePrint" class="bg-white border border-gray-300 text-gray-600 rounded-md px-4 py-1.5 text-sm hover:bg-gray-50">Tutup</button>
                    </div>
                </div>
                <div class="overflow-y-auto p-6 bg-gray-100">
                    <div class="bg-white shadow-sm mx-auto p-8" style="max-width:21cm;">
                        @include('risalah._dokumen', ['r' => $printRisalah, 'mode' => 'print'])
                    </div>
                </div>
            </div>
            {{-- Off-screen (bukan display:none — iframe tersembunyi tak tercetak di sebagian browser). --}}
            <iframe id="risalah-print-frame" aria-hidden="true" tabindex="-1"
                style="position: absolute; width: 0; height: 0; border: 0; visibility: hidden;"></iframe>
        </div>
    @endif
</div>
