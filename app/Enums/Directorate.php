<?php

namespace App\Enums;

enum Directorate: int
{
    case DEFAULT = 0;
    case BRANCH_NETWORK = 1;
    case COMMERCIAL_BANKING = 2;
    case CONSUMER_BANKING = 3;
    case CORPORATE_BANKING = 4;
    case CORPORATE_AFFAIRS_SUSTAINABILITY = 5;
    case FINANCE_SHARIA = 6;
    case INTERNAL_AUDIT = 7;
    case INTERNATIONAL_BUSINESS_DEVELOPMENT = 8;
    case LEGAL_COMPLIANCE = 9;
    case OPERATIONS = 10;
    case PEOPLE_CULTURE = 11;
    case RISK = 12;
    case TECHNOLOGY = 13;
    case TREASURY = 14;
    case PRESIDENT_DIRECTOR = 15;

    public function label(): string
    {
        return match($this) {
            self::DEFAULT => 'Belum Pilih',
            self::BRANCH_NETWORK => 'Branch Network',
            self::COMMERCIAL_BANKING => 'Commercial Banking',
            self::CONSUMER_BANKING => 'Consumer Banking',
            self::CORPORATE_BANKING => 'Corporate Banking',
            self::CORPORATE_AFFAIRS_SUSTAINABILITY => 'Corporate Affairs & Sustainability',
            self::FINANCE_SHARIA => 'Finance & Sharia',
            self::INTERNAL_AUDIT => 'Internal Audit',
            self::INTERNATIONAL_BUSINESS_DEVELOPMENT => 'International Business Development',
            self::LEGAL_COMPLIANCE => 'Legal & Compliance',
            self::OPERATIONS => 'Operations',
            self::PEOPLE_CULTURE => 'People & Culture',
            self::RISK => 'Risk',
            self::TECHNOLOGY => 'Technology',
            self::TREASURY => 'Treasury',
            self::PRESIDENT_DIRECTOR => "President Director's Office"
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
