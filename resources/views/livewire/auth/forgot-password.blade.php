<div class="min-h-screen bg-[#f0f2f5] flex items-center justify-center p-4">
    <div class="bg-white p-8 sm:p-10 rounded-xl shadow-sm border border-gray-100 w-full max-w-md">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-[#e6f4ff] rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[#1677ff]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Lupa Kata Sandi</h1>
            <p class="text-sm text-gray-500 mt-2">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
        </div>

        @if ($sent)
            {{-- Neutral confirmation (shown regardless of whether the email exists) --}}
            <div class="bg-[#f6ffed] border border-[#b7eb8f] text-[#389e0d] px-4 py-3 rounded-md mb-6 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm">Jika email tersebut terdaftar dan aktif, kami telah mengirim tautan atur ulang kata sandi ke kotak masuk Anda.</span>
            </div>
        @else
            {{-- Error alert --}}
            @error('email')
                <div class="bg-[#fff2f0] border border-[#ffccc7] text-[#cf1322] px-4 py-3 rounded-md mb-6 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm">{{ $message }}</span>
                </div>
            @enderror

            <form wire:submit="sendResetLink" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Email</label>
                    <input
                        type="email"
                        wire:model="email"
                        required
                        class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:border-[#1677ff] focus:ring-2 focus:ring-[#1677ff]/20 bg-white"
                        placeholder="nama@app.com"
                    />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full bg-[#1677ff] text-white py-2.5 px-4 rounded-md font-medium hover:bg-[#0958d9] disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200 flex justify-center items-center gap-2 shadow-sm"
                    >
                        <span wire:loading.remove wire:target="sendResetLink">Kirim Tautan Atur Ulang</span>
                        <span wire:loading wire:target="sendResetLink">Mengirim...</span>
                    </button>
                </div>
            </form>
        @endif

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-[#1677ff] hover:text-[#0958d9] font-medium">
                &larr; Kembali ke halaman masuk
            </a>
        </div>
    </div>
</div>
