<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Pemeriksaan Lapang — {{ $ba->permohonan?->nomor_registrasi ?? '' }}</title>
    <style>
        @page { size: A4; margin: 2.5cm 2.5cm 2.5cm 3cm; }
        body { margin: 30px; }
        .toolbar { margin-bottom: 18px; }
        .toolbar a, .toolbar button { font-size: 13px; padding: 6px 16px; cursor: pointer; text-decoration: none; border: 1px solid #888; border-radius: 4px; color: #000; background: #f5f5f5; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
        <a href="{{ route('berita-acara.word', $ba->id) }}">⬇️ Download Word</a>
    </div>

    @include('berita-acara._dokumen', ['ba' => $ba, 'mode' => 'print'])
</body>
</html>
