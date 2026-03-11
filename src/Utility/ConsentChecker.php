<?php

declare(strict_types=1);

namespace Marktic\Cmp\Utility;

use Marktic\Cmp\Consents\Enums\ConsentStatus;
use Marktic\Cmp\Consents\Enums\ConsentType;
use Marktic\Cmp\Consents\Models\Consents;

/**
 * Provides a convenient API to query consent permissions for a specific session.
 *
 * Usage:
 *
 *   $checker = new ConsentChecker($consents, 'organization', 10, $sessionId);
 *   $checker->isGranted(ConsentType::ANALYTICS_STORAGE);
 *   $checker->hasConsent('analytics_storage');
 */
class ConsentChecker
{
    public function __construct(
        private readonly Consents $consents,
        private readonly string $tenant,
        private readonly int $tenantId,
        private readonly string $sessionId,
    ) {}

    /**
     * Check if a consent type is granted using its enum case.
     */
    public function isGranted(ConsentType $type): bool
    {
        $consent = $this->consents->findBySessionAndType($this->tenant, $this->tenantId, $this->sessionId, $type);

        return $consent !== null && $consent->getConsentStatus() === ConsentStatus::GRANTED;
    }

    /**
     * Check if a consent type is explicitly denied using its enum case.
     *
     * Returns false when consent is not recorded (unknown) — use !isGranted() if you
     * want to treat absence of consent as denied.
     */
    public function isDenied(ConsentType $type): bool
    {
        $consent = $this->consents->findBySessionAndType($this->tenant, $this->tenantId, $this->sessionId, $type);

        return $consent !== null && $consent->getConsentStatus() === ConsentStatus::DENIED;
    }

    /**
     * Check consent by string key (e.g. 'analytics_storage').
     *
     * @throws \ValueError When the string does not match a valid ConsentType.
     */
    public function hasConsent(string $consentTypeValue): bool
    {
        return $this->isGranted(ConsentType::from($consentTypeValue));
    }

    /**
     * Return a map of all consent types to their current status.
     *
     * @return array<string, string>  Keys are ConsentType values, values are ConsentStatus values.
     */
    public function getAll(): array
    {
        $consents = $this->consents->findAllBySession($this->tenant, $this->tenantId, $this->sessionId);

        $result = [];

        foreach ($consents as $consent) {
            $result[$consent->getConsentType()->value] = $consent->getConsentStatus()->value;
        }

        return $result;
    }
}
