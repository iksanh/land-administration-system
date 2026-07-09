<?php

namespace App\Livewire\Concerns;

/**
 * Helper generik untuk mengelola properti Livewire bertipe daftar string
 * terurut (tambah/hapus/geser) — mis. "Data Pendukung" dan "Dasar Hukum" pada
 * Risalah. Berbeda dari WithRiwayatPenguasaan yang khusus riwayat penguasaan +
 * pemeriksa typo AI, trait ini murni CRUD urutan tanpa penyimpanan tersendiri.
 *
 * Pasangkan dengan partial view `livewire._list-editor`. Nama properti dikirim
 * sebagai argumen sehingga satu komponen dapat memakai beberapa daftar.
 */
trait WithOrderedLists
{
    /** Tambah satu baris kosong di akhir daftar $prop. */
    public function addListItem(string $prop): void
    {
        $this->{$prop}[] = '';
    }

    /** Hapus baris ke-$index dan rapikan indeks daftar $prop. */
    public function removeListItem(string $prop, int $index): void
    {
        unset($this->{$prop}[$index]);
        $this->{$prop} = array_values($this->{$prop});
    }

    /** Geser baris ke atas (-1) atau ke bawah (+1) pada daftar $prop. */
    public function moveListItem(string $prop, int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->{$prop}[$index], $this->{$prop}[$target])) {
            return;
        }

        [$this->{$prop}[$index], $this->{$prop}[$target]]
            = [$this->{$prop}[$target], $this->{$prop}[$index]];
    }

    /**
     * Buang baris kosong dari sebuah daftar dan kembalikan sebagai array
     * terindeks ulang (null bila kosong) — dipakai saat menyimpan.
     *
     * @return array<int, string>|null
     */
    protected function cleanList(array $items): ?array
    {
        $items = array_values(array_filter(
            array_map('trim', $items),
            fn ($v) => $v !== '',
        ));

        return $items ?: null;
    }
}
