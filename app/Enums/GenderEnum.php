<?php

namespace App\Enums;

enum GenderEnum: string
{
    case L = 'L';
    case P = 'P';

    /** Sapaan pada dokumen resmi: "Sdr." (laki-laki) / "Sdri." (perempuan). */
    public function sapaan(): string
    {
        return match ($this) {
            self::L => 'Sdr.',
            self::P => 'Sdri.',
        };
    }
}
