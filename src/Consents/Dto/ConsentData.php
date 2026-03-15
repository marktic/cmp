<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Dto;

use Marktic\Cmp\Consents\Enums\ConsentType;

/**
 * Data Transfer Object that carries consent values for a single update request.
 *
 * Each property corresponds to a ConsentType and holds a ConsentStatus value
 * string (e.g. 'granted' or 'denied'), or null when the type is not included
 * in the current request.
 *
 * Usage:
 *
 *   $data = ConsentData::createFromPayload([
 *       'ad_storage'        => 'granted',
 *       'analytics_storage' => 'denied',
 *   ]);
 *   $data->tenant   = 'organization';
 *   $data->tenantId = 10;
 */
class ConsentData
{
    /** Tenant type string (e.g. 'organization'). Set by the controller. */
    public ?string $tenant = null;

    /** Tenant numeric ID. Set by the controller. */
    public ?int $tenantId = null;

    /** The raw payload as received (JSON-encoded string or array). */
    public null|string|array $payload = null;

    // -------------------------------------------------------------------------
    // Per-ConsentType properties
    // -------------------------------------------------------------------------

    public ?string $adStorage = null;
    public ?string $analyticsStorage = null;
    public ?string $adUserData = null;
    public ?string $adPersonalization = null;
    public ?string $functionalityStorage = null;
    public ?string $securityStorage = null;
    public ?string $personalizationStorage = null;

    /**
     * Build a ConsentData instance from a raw payload array and store the
     * original payload for audit logging.
     *
     * @param array<string, string> $payload  Keys are ConsentType values.
     */
    public static function createFromPayload(array $payload): self
    {
        $dto = new self();
        $dto->payload = $payload;

        $map = [
            ConsentType::AD_STORAGE->value            => 'adStorage',
            ConsentType::ANALYTICS_STORAGE->value     => 'analyticsStorage',
            ConsentType::AD_USER_DATA->value          => 'adUserData',
            ConsentType::AD_PERSONALIZATION->value    => 'adPersonalization',
            ConsentType::FUNCTIONALITY_STORAGE->value => 'functionalityStorage',
            ConsentType::SECURITY_STORAGE->value      => 'securityStorage',
            ConsentType::PERSONALIZATION_STORAGE->value => 'personalizationStorage',
        ];

        foreach ($map as $key => $property) {
            if (array_key_exists($key, $payload)) {
                $dto->{$property} = $payload[$key];
            }
        }

        return $dto;
    }

    /**
     * Return only the non-null consents as an array keyed by ConsentType value.
     *
     * @return array<string, string>
     */
    public function getConsents(): array
    {
        $map = [
            ConsentType::AD_STORAGE->value              => $this->adStorage,
            ConsentType::ANALYTICS_STORAGE->value       => $this->analyticsStorage,
            ConsentType::AD_USER_DATA->value            => $this->adUserData,
            ConsentType::AD_PERSONALIZATION->value      => $this->adPersonalization,
            ConsentType::FUNCTIONALITY_STORAGE->value   => $this->functionalityStorage,
            ConsentType::SECURITY_STORAGE->value        => $this->securityStorage,
            ConsentType::PERSONALIZATION_STORAGE->value => $this->personalizationStorage,
        ];

        return array_filter($map, fn(?string $v) => $v !== null);
    }
}
