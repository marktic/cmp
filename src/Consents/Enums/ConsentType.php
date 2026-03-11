<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Enums;

enum ConsentType: string
{
    case AD_STORAGE = 'ad_storage';
    case ANALYTICS_STORAGE = 'analytics_storage';
    case AD_USER_DATA = 'ad_user_data';
    case AD_PERSONALIZATION = 'ad_personalization';
    case FUNCTIONALITY_STORAGE = 'functionality_storage';
    case SECURITY_STORAGE = 'security_storage';
    case PERSONALIZATION_STORAGE = 'personalization_storage';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::AD_STORAGE => 'Ad Storage',
            self::ANALYTICS_STORAGE => 'Analytics Storage',
            self::AD_USER_DATA => 'Ad User Data',
            self::AD_PERSONALIZATION => 'Ad Personalization',
            self::FUNCTIONALITY_STORAGE => 'Functionality Storage',
            self::SECURITY_STORAGE => 'Security Storage',
            self::PERSONALIZATION_STORAGE => 'Personalization Storage',
        };
    }
}
