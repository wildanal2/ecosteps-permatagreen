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

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-amber-100 dark:bg-amber-900/40',
            self::DIVERIFIKASI => 'bg-emerald-100 dark:bg-emerald-900/40',
            self::DITOLAK => 'bg-red-100 dark:bg-red-900/40',
        };
    }

    public function textColor(): string
    {
        return match($this) {
            self::PENDING => 'text-amber-500',
            self::DIVERIFIKASI => 'text-emerald-500',
            self::DITOLAK => 'text-red-500',
        };
    }

    public function canUpdate(): bool
    {
        return $this !== self::DIVERIFIKASI;
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
