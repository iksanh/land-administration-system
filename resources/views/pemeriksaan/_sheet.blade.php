{{-- Reusable inspection-sheet body. Needs: $permohonan, $parents, $childrenMap.
     Styles are self-contained (scoped to .pemeriksaan-sheet) so it renders the
     same in the standalone print page and the in-app preview modal. --}}
<div class="pemeriksaan-sheet">
    <style>
        .pemeriksaan-sheet { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #000; }
        .pemeriksaan-sheet .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 15px; text-transform: uppercase; }
        .pemeriksaan-sheet table { width: 100%; border-collapse: collapse; }
        .pemeriksaan-sheet td, .pemeriksaan-sheet th { border: 1px solid #000; padding: 8px; vertical-align: top; }
        .pemeriksaan-sheet .header-yellow { background-color: #ffff00; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pemeriksaan-sheet .center { text-align: center; font-weight: bold; }
        .pemeriksaan-sheet .sub-item { padding-left: 20px; }
    </style>

    <div class="title">{{ $permohonan->layanan?->nama ?? '-' }}</div>

    <table>
        <tr class="header-yellow">
            <td colspan="2">Nama : {{ $permohonan->pemohon?->nama ?? '-' }}</td>
            <td>NIB : {{ $permohonan->tanah?->nib ?? '-' }}</td>
        </tr>
        <tr class="header-yellow">
            <th width="5%">No</th>
            <th width="45%">Kelengkapan Berkas</th>
            <th width="50%">Catatan</th>
        </tr>

        @php
            $catatanText = function ($row) {
                $texts = collect($row->catatan ?? [])->pluck('teks')->filter()->map(fn ($t) => e($t))->all();
                return $texts ? implode('<br>', $texts) : 'OK';
            };
        @endphp

        @forelse ($parents as $i => $parent)
            @php $children = $childrenMap[$parent->berkasItem->id] ?? []; $rowspan = 1 + count($children); @endphp
            <tr>
                <td class="center" rowspan="{{ $rowspan }}">{{ $i + 1 }}.</td>
                <td>{{ $parent->berkasItem->nama }}</td>
                <td>{!! $catatanText($parent) !!}</td>
            </tr>
            @foreach ($children as $j => $child)
                <tr>
                    <td class="sub-item">{{ chr(97 + $j) }}. {{ $child->berkasItem->nama }}</td>
                    <td>{!! $catatanText($child) !!}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="3" class="center" style="font-weight: normal;">Belum ada berkas yang diperiksa.</td></tr>
        @endforelse
    </table>
</div>
