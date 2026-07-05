<?php

namespace App\Livewire\Concerns;

use App\Models\RiwayatPenguasaan;
use App\Services\GeminiTypoCheckerService;
use App\Support\TypoHighlighter;
use RuntimeException;

/**
 * Editor "Riwayat Penguasaan" yang dapat dipakai ulang oleh komponen Livewire
 * mana pun yang membuat dokumen per-permohonan (Berita Acara, Risalah, SK).
 * Menyediakan daftar poin terurut (tambah/hapus/geser) + pemeriksa typo AI
 * (Gemini) per poin. Datanya disimpan 1:1 per permohonan di tabel
 * `riwayat_penguasaan`, jadi seluruh dokumen dari permohonan yang sama berbagi
 * teks yang identik.
 *
 * Pasangkan dengan partial view `livewire.riwayat-tanah._editor` (nama wire:click
 * sudah cocok). Cara memasang di komponen host:
 *   - edit()/createFor(): $this->loadRiwayat($permohonanId);
 *   - save():             di dalam DB::transaction, $this->saveRiwayat($permohonanId);
 *   - resetForm():        $this->resetRiwayat();
 *   - rules():            array_merge(parent-rules, $this->riwayatRules());
 */
trait WithRiwayatPenguasaan
{
    /** @var array<int, string> daftar poin riwayat penguasaan */
    public array $riwayat_penguasaan = [];

    /** Index poin riwayat yang sedang direview typo-nya (null = tidak ada). */
    public ?int $typoIndex = null;

    /** @var array<int, array{original: string, suggestion: string, reason: string, accepted: bool}> */
    public array $typoResults = [];

    public ?string $typoError = null;

    /** Aturan validasi riwayat, digabung ke rules() host. */
    public function riwayatRules(): array
    {
        return [
            'riwayat_penguasaan' => ['array'],
            'riwayat_penguasaan.*' => ['nullable', 'string'],
        ];
    }

    /** Isi daftar poin dari record permohonan (fallback satu poin kosong). */
    public function loadRiwayat(string $permohonanId): void
    {
        $rp = RiwayatPenguasaan::where('permohonan_id', $permohonanId)->first();
        $poin = $rp && ! empty($rp->poin) ? $rp->poin : [''];
        $this->riwayat_penguasaan = array_values($poin);
    }

    /** Simpan daftar poin (buang poin kosong) sebagai record 1:1 per permohonan. */
    public function saveRiwayat(string $permohonanId): void
    {
        $poin = array_values(array_filter(
            array_map('trim', $this->riwayat_penguasaan),
            fn ($v) => $v !== '',
        ));

        RiwayatPenguasaan::updateOrCreate(
            ['permohonan_id' => $permohonanId],
            ['poin' => $poin ?: null],
        );
    }

    public function resetRiwayat(): void
    {
        $this->reset(['riwayat_penguasaan', 'typoIndex', 'typoResults', 'typoError']);
    }

    /** Tambah satu poin riwayat penguasaan kosong di akhir. */
    public function addRiwayat(): void
    {
        $this->riwayat_penguasaan[] = '';
    }

    public function removeRiwayat(int $index): void
    {
        unset($this->riwayat_penguasaan[$index]);
        $this->riwayat_penguasaan = array_values($this->riwayat_penguasaan);
        $this->cancelTypo(); // index bergeser — tutup review agar tidak salah sasaran
    }

    /** Geser poin ke atas (-1) atau ke bawah (+1). */
    public function moveRiwayat(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->riwayat_penguasaan[$index], $this->riwayat_penguasaan[$target])) {
            return;
        }

        [$this->riwayat_penguasaan[$index], $this->riwayat_penguasaan[$target]]
            = [$this->riwayat_penguasaan[$target], $this->riwayat_penguasaan[$index]];
        $this->cancelTypo();
    }

    // ---- Cek typo per poin riwayat penguasaan (AI Gemini) -------------------

    /** Periksa typo pada satu poin riwayat dan buka panel review di bawahnya. */
    public function checkTypo(int $index, GeminiTypoCheckerService $checker): void
    {
        $this->typoError = null;
        $this->typoResults = [];
        $this->typoIndex = $index;

        $text = trim($this->riwayat_penguasaan[$index] ?? '');
        if ($text === '') {
            $this->typoError = 'Poin ini masih kosong.';

            return;
        }

        try {
            $this->typoResults = array_map(
                fn (array $r): array => $r + ['accepted' => true],
                $checker->check($text),
            );
        } catch (RuntimeException $e) {
            $this->typoError = 'Gagal memeriksa typo: '.$e->getMessage();
        }
    }

    /** Konfirmasi per kata: ganti (accepted) atau biarkan. */
    public function toggleTypo(int $i): void
    {
        if (isset($this->typoResults[$i])) {
            $this->typoResults[$i]['accepted'] = ! $this->typoResults[$i]['accepted'];
        }
    }

    /** Terapkan koreksi yang disetujui ke poin terkait, lalu tutup panel. */
    public function applyTypo(): void
    {
        if ($this->typoIndex !== null && isset($this->riwayat_penguasaan[$this->typoIndex])) {
            $this->riwayat_penguasaan[$this->typoIndex] = TypoHighlighter::apply(
                $this->riwayat_penguasaan[$this->typoIndex],
                $this->typoResults,
            );
        }
        $this->cancelTypo();
    }

    public function cancelTypo(): void
    {
        $this->reset(['typoIndex', 'typoResults', 'typoError']);
    }

    /**
     * Segmen highlight untuk poin yang sedang direview (dipanggil dari view).
     *
     * @return array<int, array<string, mixed>>
     */
    public function typoSegments(): array
    {
        if ($this->typoIndex === null) {
            return [];
        }

        return TypoHighlighter::segments($this->riwayat_penguasaan[$this->typoIndex] ?? '', $this->typoResults);
    }
}
