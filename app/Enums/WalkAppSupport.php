<?php

namespace App\Enums;

enum WalkAppSupport: int
{
    case OTHER = 1;
    case APPLE_HEALTH = 2;
    case GOOGLE_FIT = 3;
    case HUAWEI_HEALTH = 4;
    case SAMSUNG_HEALTH = 5;
    case Fitbit = 6;

    public function label(): string
    {
        return match($this) {
            self::OTHER => 'Other',
            self::APPLE_HEALTH => 'Apple Health',
            self::GOOGLE_FIT => 'Google Fit',
            self::HUAWEI_HEALTH => 'Huawei Health',
            self::SAMSUNG_HEALTH => 'Samsung Health',
            self::Fitbit => 'Fitbit',
        };
    }

    public static function fromAppClass(string $appClass): self
    {
        return match($appClass) {
            'Apple Health' => self::APPLE_HEALTH,
            'Google Fit' => self::GOOGLE_FIT,
            'Huawei Health' => self::HUAWEI_HEALTH,
            'Samsung Health' => self::SAMSUNG_HEALTH,
            'Fitbit' => self::Fitbit,
            default => self::OTHER,
        };
    }
}
