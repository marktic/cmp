<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Models;

use Marktic\Cmp\Base\Models\Behaviours\Timestampable\TimestampableTrait;
use Marktic\Cmp\Consents\Enums\ConsentStatus;
use Marktic\Cmp\Consents\Enums\ConsentType;

trait ConsentTrait
{
    use TimestampableTrait;

    public int|string|null $user_id;
    public ?string $context;
    public string $consent_type;
    public string $consent_value;

    public function getConsentType(): ConsentType
    {
        return ConsentType::from($this->consent_type);
    }

    public function getConsentStatus(): ConsentStatus
    {
        return ConsentStatus::from($this->consent_value);
    }

    public function isGranted(): bool
    {
        return $this->getConsentStatus()->isGranted();
    }

    public function isDenied(): bool
    {
        return $this->getConsentStatus()->isDenied();
    }
}
