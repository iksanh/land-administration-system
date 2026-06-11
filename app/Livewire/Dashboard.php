<?php

namespace App\Livewire;

use App\Enums\PermohonanStatusEnum;
use App\Models\MstLayanan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\Tanah;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $byStatus = Permohonan::selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('livewire.dashboard', [
            'totalPermohonan' => Permohonan::count(),
            'totalPemohon' => Pemohon::count(),
            'totalTanah' => Tanah::count(),
            'totalLayanan' => MstLayanan::where('is_active', true)->count(),
            'byStatus' => $byStatus,
            'statuses' => PermohonanStatusEnum::cases(),
        ]);
    }
}
