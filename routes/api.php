<?php

use App\Http\Controllers\Api\SinkronTanahController;
use App\Http\Controllers\RiwayatTanahTypoController;
use App\Http\Middleware\CekKunciSinkron;
use Illuminate\Support\Facades\Route;

Route::post('/riwayat-tanah/check-typo', RiwayatTanahTypoController::class);

// Ditarik aplikasi BA Risalah (SIPANTAS) supaya data tidak diinput dua kali.
Route::middleware([CekKunciSinkron::class, 'throttle:60,1'])
    ->get('/sinkron/tanah', SinkronTanahController::class)
    ->name('api.sinkron.tanah');
