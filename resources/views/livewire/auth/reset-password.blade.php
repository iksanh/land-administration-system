<div class="min-h-screen bg-[#f0f2f5] flex items-center justify-center p-4">
    <div class="bg-white p-8 sm:p-10 rounded-xl shadow-sm border border-gray-100 w-full max-w-md">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-[#e6f4ff] rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[#1677ff]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Atur Ulang Kata Sandi</h1>
            <p class="text-sm text-gray-500 mt-2">Buat kata sandi baru untuk akun Anda.</p>
        </div>

        {{-- Error alert --}}
        @foreach (['email', 'token', 'password'] as $errField)
            @error($errField)
                <div class="bg-[#fff2f0] border border-[#ffccc7] text-[#cf1322] px-4 py-3 rounded-md mb-6 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm">{{ $message }}</span>
                </div>
            @enderror
        @endforeach

        <form wire:submit="resetPassword" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Email</label>
                <input
                    type="email"
                    wire:model="email"
                    required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm bg-gray-50 text-gray-600 transition-all duration-200 focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20"
                    placeholder="nama@app.com"
                    readonly
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kata Sandi Baru</label>
                <input
                    type="password"
                    wire:model="password"
                    required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20 bg-white"
                    placeholder="Minimal 8 karakter"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Kata Sandi</label>
                <input
                    type="password"
                    wire:model="password_confirmation"
                    required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20 bg-white"
                    placeholder="Ulangi kata sandi baru"
                />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full bg-[#1677ff] text-white py-2.5 px-4 rounded-md font-medium hover:bg-[#0958d9] disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200 flex justify-center items-center gap-2 shadow-sm"
                >
                    <span wire:loading.remove wire:target="resetPassword">Simpan Kata Sandi Baru</span>
                    <span wire:loading wire:target="resetPassword">Menyimpan...</span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-[#1677ff] hover:text-[#0958d9] font-medium">
                &larr; Kembali ke halaman masuk
            </a>
        </div>
    </div>
</div>
