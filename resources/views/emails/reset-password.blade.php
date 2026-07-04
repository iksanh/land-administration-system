<x-mail::message>
# Atur Ulang Kata Sandi

Halo {{ $name }},

Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di **{{ config('app.name') }}**.

Klik tombol di bawah ini untuk membuat kata sandi baru:

<x-mail::button :url="$resetUrl">
Atur Ulang Kata Sandi
</x-mail::button>

Tautan ini akan kedaluwarsa dalam {{ $expireMinutes }} menit.

Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini — kata sandi Anda tidak akan berubah.

Terima kasih,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke peramban Anda:
[{{ $resetUrl }}]({{ $resetUrl }})
</x-slot:subcopy>
</x-mail::message>
