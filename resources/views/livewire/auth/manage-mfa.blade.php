<div class="flex flex-col gap-6 max-w-2xl">
    <x-flash />

    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Keamanan Akun</h2>
        <p class="text-sm text-gray-500 mt-1">Autentikasi dua faktor (MFA) menambah lapisan keamanan saat login.</p>
    </div>

    {{-- One-time recovery codes (after enable / regenerate) --}}
    @if (! empty($recoveryCodes))
        <div class="bg-[#fffbe6] border border-[#ffe58f] rounded-lg p-5">
            <h3 class="font-semibold text-[#ad6800] mb-1">Simpan kode pemulihan ini</h3>
            <p class="text-sm text-[#ad6800]/90 mb-3">
                Gunakan salah satu kode ini untuk masuk jika kehilangan akses ke aplikasi autentikator.
                Setiap kode hanya bisa dipakai sekali. <strong>Kode ini hanya ditampilkan sekarang.</strong>
            </p>
            <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach ($recoveryCodes as $code)
                    <div class="bg-white border border-[#ffe58f] rounded px-3 py-1.5 text-center tracking-wider">
                        {{ substr($code, 0, 5) }}-{{ substr($code, 5) }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($enabled)
        {{-- Enabled state --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#f6ffed] text-[#389e0d] border border-[#b7eb8f]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#389e0d]"></span> Aktif
                </span>
                <span class="text-sm text-gray-600">Autentikasi dua faktor aktif untuk akun ini.</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <button wire:click="regenerateRecoveryCodes"
                    class="text-sm px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Buat ulang kode pemulihan
                </button>
            </div>

            {{-- Disable --}}
            <form wire:submit="disable" class="border-t border-gray-100 pt-5 flex flex-col gap-2 max-w-sm">
                <label class="text-sm font-medium text-gray-700">Nonaktifkan MFA</label>
                <p class="text-xs text-gray-500 -mt-1 mb-1">Masukkan kode autentikator (atau kode pemulihan) untuk konfirmasi.</p>
                <input type="text" inputmode="numeric" wire:model="disableCode" placeholder="Kode 6 digit"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm font-mono tracking-widest focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20" />
                @error('disableCode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                <button type="submit"
                    class="self-start mt-1 text-sm px-4 py-2 rounded-md text-red-600 border border-red-500 hover:bg-red-50">
                    Nonaktifkan
                </button>
            </form>
        </div>
    @elseif ($settingUp)
        {{-- Enrollment: scan + confirm --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 flex flex-col gap-5">
            <div>
                <h3 class="font-semibold text-gray-800 mb-1">1. Pindai kode QR</h3>
                <p class="text-sm text-gray-500">Buka Google Authenticator / Authy, lalu pindai kode di bawah ini.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-5 items-start">
                {{-- QR rendered client-side from the otpauth URI (qrcode npm lib via app.js) --}}
                <div class="shrink-0 border border-gray-200 rounded-lg p-3 bg-white"
                    x-data="qrCanvas(@js($otpauthUri))" wire:ignore>
                    <canvas x-ref="canvas" width="200" height="200"></canvas>
                    <p class="text-[11px] text-gray-400 text-center mt-2 max-w-[200px]">
                        Tidak bisa pindai? Masukkan kunci manual:
                    </p>
                    <p class="text-[11px] font-mono text-gray-600 text-center break-all">{{ $manualKey }}</p>
                </div>

                {{-- Confirm --}}
                <form wire:submit="confirmSetup" class="flex-1 flex flex-col gap-2 w-full">
                    <h3 class="font-semibold text-gray-800">2. Masukkan kode konfirmasi</h3>
                    <p class="text-sm text-gray-500 -mt-1 mb-1">Ketik 6 digit yang muncul di aplikasi.</p>
                    <input type="text" inputmode="numeric" autocomplete="one-time-code" wire:model="confirmCode"
                        placeholder="••••••"
                        class="border border-gray-300 rounded-md px-3 py-2.5 text-center text-lg font-mono tracking-[0.3em] focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20" />
                    @error('confirmCode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    <div class="flex gap-2 mt-2">
                        <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-2 text-sm font-medium">
                            Aktifkan MFA
                        </button>
                        <button type="button" wire:click="cancelSetup" class="border border-gray-300 text-gray-600 rounded-md px-4 py-2 text-sm hover:bg-gray-50">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- Disabled state --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                </span>
                <span class="text-sm text-gray-600">Akun ini belum menggunakan autentikasi dua faktor.</span>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                Aktifkan MFA agar setiap login memerlukan kode dari aplikasi autentikator di ponsel Anda.
            </p>
            <button wire:click="startSetup"
                class="bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-2 text-sm font-medium">
                Aktifkan MFA
            </button>
        </div>
    @endif

    {{-- Change own password --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-gray-800 mb-1">Ubah Password</h3>
        <p class="text-sm text-gray-500 mb-4">Perbarui password akun Anda. Minimal 6 karakter.</p>
        <form wire:submit="changePassword" class="flex flex-col gap-4 max-w-sm">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Password Saat Ini</label>
                <input type="password" wire:model="currentPassword" autocomplete="current-password"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20" />
                @error('currentPassword') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Password Baru</label>
                <input type="password" wire:model="newPassword" autocomplete="new-password" placeholder="Minimal 6 karakter"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20" />
                @error('newPassword') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                <input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" placeholder="Ulangi password baru"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20" />
                @error('newPasswordConfirmation') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <button type="submit"
                class="self-start bg-[#1677ff] hover:bg-[#0958d9] text-white rounded-md px-4 py-2 text-sm font-medium">
                Simpan Password Baru
            </button>
        </form>
    </div>
</div>
