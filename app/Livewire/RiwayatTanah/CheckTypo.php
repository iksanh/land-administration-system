<?php

namespace App\Livewire\RiwayatTanah;

use App\Services\GeminiTypoCheckerService;
use App\Support\TypoHighlighter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

/**
 * Halaman uji coba pemeriksa typo riwayat tanah. Memanggil
 * GeminiTypoCheckerService langsung (cache & prompt yang sama seperti
 * endpoint POST /api/riwayat-tanah/check-typo).
 *
 * Alur: user tempel teks → periksa → tiap typo di-highlight inline dan
 * bisa dikonfirmasi (ganti / biarkan) sebelum diterapkan ke teks.
 */
#[Layout('components.layouts.app')]
class CheckTypo extends Component
{
    #[Validate('required|string|max:10000')]
    public string $text = '';

    /** @var array<int, array{original: string, suggestion: string, reason: string, accepted: bool}> */
    public array $results = [];

    public bool $checked = false;

    public ?string $error = null;

    public function check(GeminiTypoCheckerService $checker): void
    {
        $this->validate();

        $this->error = null;
        $this->results = [];
        $this->checked = false;

        try {
            $this->results = array_map(
                fn (array $r): array => $r + ['accepted' => true],
                $checker->check($this->text),
            );
            $this->checked = true;
        } catch (RuntimeException $e) {
            $this->error = 'Gagal memeriksa typo: '.$e->getMessage();
        }
    }

    /** Konfirmasi per kata: ganti (accepted) atau biarkan. */
    public function toggle(int $index): void
    {
        if (isset($this->results[$index])) {
            $this->results[$index]['accepted'] = ! $this->results[$index]['accepted'];
        }
    }

    /** Terima / tolak semua sekaligus. */
    public function setAll(bool $accepted): void
    {
        foreach (array_keys($this->results) as $i) {
            $this->results[$i]['accepted'] = $accepted;
        }
    }

    /** Terapkan koreksi yang disetujui ke teks, lalu kembali ke mode edit. */
    public function applyToText(): void
    {
        $this->text = $this->correctedText();
        $this->results = [];
        $this->checked = false;
        $this->error = null;
    }

    public function resetForm(): void
    {
        $this->reset(['text', 'results', 'checked', 'error']);
    }

    /**
     * Pecah teks menjadi segmen untuk highlight inline.
     *
     * @return array<int, array<string, mixed>>
     */
    public function segments(): array
    {
        return TypoHighlighter::segments($this->text, $this->results);
    }

    /** Teks hasil koreksi berdasarkan pilihan yang disetujui. */
    public function correctedText(): string
    {
        return TypoHighlighter::apply($this->text, $this->results);
    }

    public function render()
    {
        return view('livewire.riwayat-tanah.check-typo', [
            'segments' => $this->checked ? $this->segments() : [],
            'corrected' => $this->checked ? $this->correctedText() : '',
            'acceptedCount' => count(array_filter($this->results, fn ($r) => $r['accepted'])),
        ]);
    }
}
