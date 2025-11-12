<?php

namespace App\Enums;

enum FastApiStatus: int
{
    case QUEUED = 1;
    case PROCESSING = 2;
    case DONE = 3;

    public function label(): string
    {
        return match($this) {
            self::QUEUED => 'queued',
            self::PROCESSING => 'processing',
            self::DONE => 'done',
        };
    }

    public static function fromLabel(string $label): ?self
    {
        return match($label) {
            'queued' => self::QUEUED,
            'processing' => self::PROCESSING,
            'done' => self::DONE,
            default => null,
        };
    }
}
