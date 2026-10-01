<?php

namespace App\Http\Controllers;

use App\Models\PemeriksaanBerkasFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyajikan file lampiran pemeriksaan berkas dari disk privat (storage/app/private).
 * Rute berada di grup 'auth' sehingga file hanya bisa diakses setelah login —
 * tidak seperti disk publik yang URL-nya bisa ditebak siapa saja.
 * Default menampilkan inline (pratinjau di browser); ?download=1 memaksa unduh.
 */
class PemeriksaanFileController extends Controller
{
    public function __invoke(Request $request, PemeriksaanBerkasFile $file): Response
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($file->file_path), 404, 'File tidak ditemukan.');

        return $request->boolean('download')
            ? $disk->download($file->file_path, $file->nama_asli)
            : $disk->response($file->file_path, $file->nama_asli);
    }
}
