{{--
    Modal editor Riwayat Penguasaan (reusable). Pasangan dari `_ringkasan`:
    dibuka lewat openRiwayat(), menyimpan lewat simpanRiwayat() — terpisah dari
    aksi simpan dokumen induknya karena riwayat adalah data bersama.

    Membutuhkan host memakai trait App\Livewire\Concerns\WithRiwayatPenguasaan.
    Letakkan di luar <form> dokumen induk agar tombolnya tidak ikut men-submit.
--}}
@if ($showRiwayatModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:key="riwayat-modal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-start justify-between gap-4 px-5 py-3 border-b border-gray-200">
                <div>
                    <h3 class="font-semibold text-gray-800">Riwayat Penguasaan Tanah</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        Disimpan per permohonan dan dipakai bersama oleh Berita Acara, Risalah &amp; SK —
                        perubahan di sini langsung berlaku pada seluruh dokumen permohonan ini.
                    </p>
                </div>
                <button type="button" wire:click="closeRiwayat"
                    class="shrink-0 bg-white border border-gray-300 text-gray-600 rounded-md px-4 py-1.5 text-sm hover:bg-gray-50">Tutup</button>
            </div>

            <div class="overflow-y-auto p-6">
                @include('livewire.riwayat-tanah._editor', [
                    'label' => 'Poin Riwayat',
                    'hint' => 'Tiap poin dicetak sebagai butir terpisah. Poin kosong dibuang saat disimpan.',
                ])
            </div>

            <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" wire:click="closeRiwayat"
                    class="rounded-md border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Batal</button>
                <button type="button" wire:click="simpanRiwayat"
                    class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-1.5 text-sm font-medium">Simpan Riwayat</button>
            </div>
        </div>
    </div>
@endif
