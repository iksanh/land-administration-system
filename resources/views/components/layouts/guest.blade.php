<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} — SIP Bone Bolango</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased">
    {{ $slot }}
</body>
</html>
