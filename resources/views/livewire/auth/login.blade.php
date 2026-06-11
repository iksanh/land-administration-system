<div class="min-h-screen bg-[#f0f2f5] flex items-center justify-center p-4">
    <div class="bg-white p-8 sm:p-10 rounded-xl shadow-sm border border-gray-100 w-full max-w-md">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-[#e6f4ff] rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[#1677ff]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Sistem Informasi Pertanahan</h1>
            <p class="text-sm text-gray-500 mt-2">Silakan masukkan kredensial Anda untuk masuk ke sistem.</p>
        </div>

        {{-- Error alert --}}
        @error('email')
            <div class="bg-[#fff2f0] border border-[#ffccc7] text-[#cf1322] px-4 py-3 rounded-md mb-6 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm">{{ $message }}</span>
            </div>
        @enderror

        {{-- Login form --}}
        <form wire:submit="login" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Email</label>
                <input
                    type="email"
                    wire:model="email"
                    required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20 bg-white"
                    placeholder="admin@app.com"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input
                    type="password"
                    wire:model="password"
                    required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20 bg-white"
                    placeholder="••••••••"
                />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full bg-[#1677ff] text-white py-2.5 px-4 rounded-md font-medium hover:bg-[#0958d9] disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200 flex justify-center items-center gap-2 shadow-sm"
                >
                    <span wire:loading.remove wire:target="login">Masuk ke Sistem</span>
                    <span wire:loading wire:target="login">Sedang Masuk...</span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">
                &copy; {{ date('Y') }} Kantor Pertanahan Bone Bolango.<br>Hak Cipta Dilindungi.
            </p>
        </div>
    </div>
</div>
