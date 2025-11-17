<?php

namespace App\Enums;

enum TreeCo2Absorption: string
{
    case JATI = 'jati';
    case MAHONI = 'mahoni';
    case TREMBESI = 'trembesi';
    case BAMBU = 'bambu';
    case MANGROVE = 'mangrove';
    case PINUS = 'pinus';
    case EUCALYPTUS = 'eucalyptus';

    public function getValue(): float
    {
        return match($this) {
            self::JATI => 22.0,
            self::MAHONI => 25.0,
            self::TREMBESI => 28.5,
            self::BAMBU => 12.0,
            self::MANGROVE => 18.0,
            self::PINUS => 20.0,
            self::EUCALYPTUS => 24.0,
        };
    }

    public static function default(): self
    {
        return self::JATI;
    }
}