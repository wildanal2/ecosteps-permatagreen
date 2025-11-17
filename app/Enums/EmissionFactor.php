<?php

namespace App\Enums;

enum EmissionFactor: string
{
    case MOBIL_PETROL = 'mobil_petrol';
    case MOTOR_BENSIN = 'motor_bensin';
    case BUS_KOTA = 'bus_kota';
    case KERETA_LISTRIK = 'kereta_listrik';
    case MOBIL_EV = 'mobil_ev';

    public function getValue(): float
    {
        return match($this) {
            self::MOBIL_PETROL => 0.111,
            self::MOTOR_BENSIN => 0.098,
            self::BUS_KOTA => 0.109,
            self::KERETA_LISTRIK => 0.035,
            self::MOBIL_EV => 0.099,
        };
    }

    public static function default(): self
    {
        return self::MOBIL_PETROL;
    }
}
