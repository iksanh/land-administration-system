<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar Pemeriksaan — {{ $permohonan->nomor_registrasi }}</title>
    <style>
        body { margin: 30px; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button { font-size: 13px; padding: 6px 14px; cursor: pointer; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ Cetak</button>
    </div>

    @include('pemeriksaan._sheet', ['permohonan' => $permohonan, 'parents' => $parents, 'childrenMap' => $childrenMap])
</body>
</html>
