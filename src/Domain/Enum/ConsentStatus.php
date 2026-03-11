<?php

declare(strict_types=1);

namespace Marktic\CMP\Domain\Enum;

enum ConsentStatus: string
{
    case GRANTED = 'granted';
    case DENIED = 'denied';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function isGranted(): bool
    {
        return $this === self::GRANTED;
    }

    public function isDenied(): bool
    {
        return $this === self::DENIED;
    }
}
