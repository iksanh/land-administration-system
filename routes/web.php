<?php

use App\Http\Controllers\BeritaAcaraPrintController;
use App\Http\Controllers\BeritaAcaraWordController;
use App\Http\Controllers\PemeriksaanPrintController;
use App\Livewire\AuditLog\AuditLogTimeline;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ManageMfa;
use App\Livewire\Berkas\ManageBerkasItem;
use App\Livewire\BeritaAcara\ManageBeritaAcara;
use App\Livewire\Berkas\ManageMapLayananBerkas;
use App\Livewire\Catatan\ManageMstCatatan;
use App\Livewire\Dashboard;
use App\Livewire\Layanan\ManageLayanan;
use App\Livewire\Pemeriksaan\ManagePemeriksaanBerkas;
use App\Livewire\Pemohon\ManagePemohon;
use App\Livewire\Panitia\ManagePanitia;
use App\Livewire\Permohonan\ManagePermohonan;
use App\Livewire\Tanah\ManageTanah;
use App\Livewire\Users\ManageUsers;
use App\Livewire\Wilayah\ManageWilayah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/layanan', ManageLayanan::class)->name('layanan');
    Route::get('/berkas-item', ManageBerkasItem::class)->name('berkas-item');
    Route::get('/map-layanan-berkas', ManageMapLayananBerkas::class)->name('map-layanan-berkas');
    Route::get('/master-catatan', ManageMstCatatan::class)->name('master-catatan');
    Route::get('/wilayah', ManageWilayah::class)->name('wilayah');
    Route::get('/pemohon', ManagePemohon::class)->name('pemohon');
    Route::get('/tanah', ManageTanah::class)->name('tanah');
    Route::get('/permohonan', ManagePermohonan::class)->name('permohonan');
    Route::get('/pemeriksaan-berkas', ManagePemeriksaanBerkas::class)->name('pemeriksaan-berkas');
    Route::get('/permohonan/{permohonan}/cetak-pemeriksaan', PemeriksaanPrintController::class)->name('pemeriksaan.print');
    Route::get('/audit-log', AuditLogTimeline::class)->name('audit-log');

    // Berita Acara Pemeriksaan Lapang (Panitia A)
    Route::get('/panitia', ManagePanitia::class)->name('panitia');
    Route::get('/berita-acara', ManageBeritaAcara::class)->name('berita-acara');
    Route::get('/berita-acara/{beritaAcara}/cetak', BeritaAcaraPrintController::class)->name('berita-acara.print');
    Route::get('/berita-acara/{beritaAcara}/word', BeritaAcaraWordController::class)->name('berita-acara.word');

    // Account security — per-user, opt-in TOTP MFA
    Route::get('/keamanan', ManageMfa::class)->name('mfa');

    // Admin-only (ports get_current_admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', ManageUsers::class)->name('users');
    });

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
