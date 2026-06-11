<?php

namespace App\Livewire\AuditLog;

use App\Models\Permohonan;
use App\Models\PermohonanAuditLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/audit_log.py (read-only). Shows the status-change
 * history a permohonan accumulates via Permohonan::changeStatus(). petugas_id
 * is a plain UUID column (no FK), so officer names are resolved from a users map.
 */
#[Layout('components.layouts.app')]
class AuditLogTimeline extends Component
{
    public string $selectedPermohonan = '';

    public function render()
    {
        $logs = $this->selectedPermohonan
            ? PermohonanAuditLog::where('permohonan_id', $this->selectedPermohonan)
                ->orderByDesc('created_at')
                ->orderByDesc('id') // deterministic tiebreaker when timestamps match
                ->get()
            : collect();

        $petugas = $logs->pluck('petugas_id')->filter()->unique();
        $users = $petugas->isNotEmpty()
            ? User::whereIn('id', $petugas)->pluck('name', 'id')
            : collect();

        return view('livewire.audit-log.audit-log-timeline', [
            'permohonanList' => Permohonan::with('pemohon')->latest('created_at')->get(),
            'logs' => $logs,
            'users' => $users,
        ]);
    }
}
