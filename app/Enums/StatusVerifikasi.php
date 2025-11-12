<?php

namespace App\Enums;

enum StatusVerifikasi: int
{
    case PENDING = 1;
    case DIVERIFIKASI = 2;
    case DITOLAK = 3;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Proses Verifikasi',
            self::DIVERIFIKASI => 'Diverifikasi',
            self::DITOLAK => 'Tidak Valid',
        };
    }

    public static function fromLabel(string $label): ?self
    {
        return match($label) {
            'Proses Verifikasi' => self::PENDING,
            'diverifikasi' => self::DIVERIFIKASI,
            'Tidak Valid' => self::DITOLAK,
            default => null,
        };
    }
}
