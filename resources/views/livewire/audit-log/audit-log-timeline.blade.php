<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Riwayat Status (Audit Log)</h2>
        <p class="text-sm text-gray-500 mt-1">Jejak perubahan status permohonan beserta petugas dan catatannya.</p>
    </div>

    {{-- Pick permohonan --}}
    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-200">
        <label class="text-sm font-medium text-gray-700 block mb-1.5">Pilih Permohonan</label>
        <select wire:model.live="selectedPermohonan"
            class="w-full md:w-2/3 border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
            <option value="">— Pilih permohonan —</option>
            @foreach ($permohonanList as $p)
                <option value="{{ $p->id }}">{{ $p->nomor_registrasi }} — {{ $p->pemohon?->nama ?? 'Tanpa pemohon' }}</option>
            @endforeach
        </select>
    </div>

    @if ($selectedPermohonan)
        @if ($logs->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">Belum ada perubahan status untuk permohonan ini.</div>
        @else
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <ol class="relative border-l border-gray-200 ml-3">
                    @foreach ($logs as $log)
                        <li class="mb-6 ml-6">
                            <span class="absolute -left-2.5 flex items-center justify-center w-5 h-5 bg-[#1677ff] rounded-full ring-4 ring-white"></span>
                            <div class="flex items-center gap-2 flex-wrap">
                                @if ($log->status_sebelumnya)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">{{ $log->status_sebelumnya->value }}</span>
                                    <span class="text-gray-400">→</span>
                                @endif
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-[#e6f4ff] text-[#1677ff] border border-[#91caff]">{{ $log->status_baru->value }}</span>
                                <span class="text-xs text-gray-400 ml-1">{{ $log->created_at?->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Petugas: {{ $log->petugas_id ? ($users[$log->petugas_id] ?? 'Tidak diketahui') : 'Sistem' }}
                            </p>
                            @if ($log->catatan)
                                <p class="text-sm text-gray-700 mt-1 border-l-2 border-gray-200 pl-2">{{ $log->catatan }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    @else
        <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">Pilih permohonan untuk melihat riwayat statusnya.</div>
    @endif
</div>
