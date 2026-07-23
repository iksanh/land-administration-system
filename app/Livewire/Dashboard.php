<?php

namespace App\Livewire;

use App\Enums\PermohonanStatusEnum;
use App\Models\MstLayanan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard pemantauan pekerjaan: selain ringkasan jumlah, menampilkan
 * berkas yang paling lama tertahan di tahapnya (dari audit log), aktivitas
 * status terbaru, sebaran tahapan, dan tren permohonan masuk 6 bulan.
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $byStatus = Permohonan::selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $total = (int) $byStatus->sum();
        $draft = (int) ($byStatus[PermohonanStatusEnum::DRAFT->value] ?? 0);
        $selesai = (int) ($byStatus[PermohonanStatusEnum::LOKET_PENYERAHAN->value] ?? 0);
        $ditolak = (int) ($byStatus[PermohonanStatusEnum::DITOLAK->value] ?? 0);

        // Berkas aktif yang paling lama diam di tahapnya: acuan waktunya adalah
        // perubahan status terakhir (audit log); fallback created_at bila belum
        // pernah berubah status.
        $lastChange = PermohonanAuditLog::selectRaw('permohonan_id, MAX(created_at) as last_at')
            ->groupBy('permohonan_id');

        $perluPerhatian = Permohonan::with('pemohon')
            ->whereNotIn('status', [
                PermohonanStatusEnum::LOKET_PENYERAHAN->value,
                PermohonanStatusEnum::DITOLAK->value,
            ])
            ->leftJoinSub($lastChange, 'lc', 'lc.permohonan_id', '=', 'permohonan.id')
            ->select('permohonan.*', DB::raw('COALESCE(lc.last_at, permohonan.created_at) as tahap_sejak'))
            ->orderBy('tahap_sejak')
            ->limit(6)
            ->get();

        // Permohonan masuk per bulan, 6 bulan terakhir (termasuk bulan kosong).
        $perBulan = Permohonan::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->pluck('c', 'ym');

        $bulanan = collect(range(5, 0))->map(function ($i) use ($perBulan) {
            $m = now()->subMonths($i);

            return [
                'label' => $m->locale('id')->isoFormat('MMM'),
                'count' => (int) ($perBulan[$m->format('Y-m')] ?? 0),
            ];
        })->values();

        return view('livewire.dashboard', [
            'totalPermohonan' => $total,
            'dalamProses' => $total - $draft - $selesai - $ditolak,
            'praDaftar' => $draft,
            'selesai' => $selesai,
            'ditolak' => $ditolak,
            'byStatus' => $byStatus,
            'statuses' => PermohonanStatusEnum::cases(),
            'perluPerhatian' => $perluPerhatian,
            'aktivitas' => PermohonanAuditLog::with('permohonan.pemohon')->latest('created_at')->limit(8)->get(),
            'userNames' => User::pluck('name', 'id'),
            'bulanan' => $bulanan,
            'bulananMax' => max(1, $bulanan->max('count')),
            'totalPemohon' => Pemohon::count(),
            'totalTanah' => Tanah::count(),
            'totalLayanan' => MstLayanan::where('is_active', true)->count(),
        ]);
    }
}
