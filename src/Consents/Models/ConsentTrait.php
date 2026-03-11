<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Models;

use Marktic\CMP\Base\Models\HasTenant\HasTenantRecord;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;

trait ConsentTrait
{
    use HasTenantRecord;

    public string $session_id;
    public ?string $user_id;
    public string $consent_type;
    public string $consent_value;
    public string $created_at;
    public string $updated_at;

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
