<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — SIP Bone Bolango</title>
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-[#f0f2f5] min-h-screen" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

        {{-- Sidebar (off-canvas drawer on mobile, static on large screens) --}}
        <aside x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-[#001529] text-gray-300 flex flex-col shrink-0
                   transform transition-transform duration-200 ease-in-out
                   lg:translate-x-0 lg:static lg:z-auto">
            <div class="h-16 flex items-center gap-2.5 px-5 border-b border-white/10">
                <div class="w-9 h-9 rounded-lg bg-[#1677ff] flex items-center justify-center text-white font-bold shrink-0">P</div>
                <div class="leading-tight min-w-0">
                    <p class="text-white font-semibold text-sm truncate">SIP Bone Bolango</p>
                    <p class="text-[10px] text-gray-400 truncate">Sistem Informasi Pertanahan</p>
                </div>
                <button type="button" @click="sidebarOpen = false" class="ml-auto lg:hidden text-gray-400 hover:text-white p-1 -mr-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 py-3 text-sm overflow-y-auto" @click="sidebarOpen = false">
                @php
                    $isActive = fn ($r) => request()->routeIs($r);
                    $cls = fn ($r) => 'flex items-center gap-3 px-5 py-2.5 transition-colors '
                        .($isActive($r) ? 'bg-[#1677ff] text-white font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white');
                    $heading = 'px-5 pt-5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-500';
                @endphp

                <a href="{{ route('dashboard') }}" wire:navigate class="{{ $cls('dashboard') }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                    Dashboard
                </a>

                <p class="{{ $heading }}">Alur Permohonan</p>
                @foreach ([['Data Pemohon', 'pemohon'], ['Data Tanah', 'tanah'], ['Permohonan', 'permohonan'], ['Pemeriksaan Berkas', 'pemeriksaan-berkas'], ['Berita Acara Lapang', 'berita-acara'], ['Risalah Panitia A', 'risalah']] as $i => [$label, $r])
                    <a href="{{ route($r) }}" wire:navigate class="{{ $cls($r) }}">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 {{ $isActive($r) ? 'bg-white text-[#1677ff]' : 'bg-white/10 text-gray-300' }}">{{ $i + 1 }}</span>
                        {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('audit-log') }}" wire:navigate class="{{ $cls('audit-log') }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Riwayat Status
                </a>
                <a href="{{ route('cek-typo') }}" wire:navigate class="{{ $cls('cek-typo') }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Cek Typo (AI)
                </a>

                <p class="{{ $heading }}">Master Data</p>
                @foreach ([['Master Layanan', 'layanan'], ['Master Berkas', 'berkas-item'], ['Pemetaan Berkas', 'map-layanan-berkas'], ['Master Catatan', 'master-catatan'], ['Master Wilayah', 'wilayah'], ['Panitia Pemeriksa', 'panitia']] as [$label, $r])
                    <a href="{{ route($r) }}" wire:navigate class="{{ $cls($r) }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-50 shrink-0 mx-1.5"></span>
                        {{ $label }}
                    </a>
                @endforeach

                @if (auth()->user()->isAdmin())
                    <p class="{{ $heading }}">Pengaturan</p>
                    <a href="{{ route('users') }}" wire:navigate class="{{ $cls('users') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                        Manajemen User
                    </a>
                @endif
            </nav>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white shadow-[0_1px_4px_rgba(0,21,41,0.08)] px-4 sm:px-6 h-16 flex justify-between items-center sticky top-0 z-20 gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 p-1 -ml-1 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Sistem Informasi Pertanahan</h1>
                </div>
                <div class="flex items-center gap-3 sm:gap-6 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-sm font-medium text-gray-700 leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-xs text-gray-500 leading-tight">{{ auth()->user()->roleLabels() }}</span>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-blue-100 text-[#1677ff] flex items-center justify-center font-bold border border-blue-200 shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                    <a href="{{ route('mfa') }}" wire:navigate title="Keamanan akun"
                        class="text-sm px-3 sm:px-4 py-1.5 rounded border transition-all duration-200 inline-flex items-center gap-1.5
                            {{ request()->routeIs('mfa') ? 'text-[#1677ff] border-[#1677ff] bg-[#e6f4ff]' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 5.591-3.824 10.29-9 11.622C6.824 22.29 3 17.591 3 12V6.633c0-.55.32-1.052.82-1.282A11.96 11.96 0 0 1 12 3.25c2.95 0 5.66 1.067 7.18 2.101.5.23.82.732.82 1.282V12Z"/></svg>
                        <span class="hidden sm:inline">Keamanan</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm px-3 sm:px-4 py-1.5 rounded text-red-500 border border-red-500 hover:bg-red-50 hover:text-red-600 hover:border-red-600 transition-all duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <main class="p-4 sm:p-6 flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
