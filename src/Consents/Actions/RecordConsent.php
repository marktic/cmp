<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Actions;

use InvalidArgumentException;
use Marktic\CMP\Base\Tenant;
use Marktic\CMP\ConsentLogs\Models\ConsentLog;
use Marktic\CMP\ConsentLogs\Repository\ConsentLogRepositoryInterface;
use Marktic\CMP\Consents\Enums\ConsentSource;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Models\Consent;
use Marktic\CMP\Consents\Repository\ConsentRepositoryInterface;
use Ramsey\Uuid\Uuid;

/**
 * Records or updates user consent values for a session.
 *
 * For each consent type in the provided map:
 *   - Creates a new Consent record if none exists.
 *   - Updates the existing record and writes an audit log when the value changes.
 *   - Silently skips unchanged values (no log entry written).
 */
class RecordConsent
{
    public function __construct(
        private readonly ConsentRepositoryInterface $consentRepository,
        private readonly ConsentLogRepositoryInterface $consentLogRepository,
    ) {}

    /**
     * @param array<string, string> $consents  Keyed by ConsentType value, valued by ConsentStatus value.
     *
     * @throws InvalidArgumentException When an unknown consent type or status is provided.
     */
    public function execute(
        Tenant $tenant,
        string $sessionId,
        ?string $userId,
        array $consents,
        ConsentSource $source = ConsentSource::API,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $validated = $this->validateConsents($consents);

        foreach ($validated as $type => $status) {
            $consentType = ConsentType::from($type);
            $consentStatus = ConsentStatus::from($status);

            $existing = $this->consentRepository->findBySessionAndType($tenant, $sessionId, $consentType);

            if ($existing === null) {
                $consent = Consent::create($tenant, $sessionId, $userId, $consentType, $consentStatus);
                $this->consentRepository->save($consent);

                $this->writeLog(
                    consentId: $consent->getId()->toString(),
                    tenant: $tenant,
                    sessionId: $sessionId,
                    userId: $userId,
                    previousStatus: null,
                    newStatus: $consentStatus,
                    consentType: $consentType,
                    source: $source,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                );
            } elseif ($existing->getConsentStatus() !== $consentStatus) {
                $previousStatus = $existing->getConsentStatus();
                $existing->update($consentStatus);
                $this->consentRepository->save($existing);

                $this->writeLog(
                    consentId: $existing->getId()->toString(),
                    tenant: $tenant,
                    sessionId: $sessionId,
                    userId: $userId,
                    previousStatus: $previousStatus,
                    newStatus: $consentStatus,
                    consentType: $consentType,
                    source: $source,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                );
            }
        }
    }

    /**
     * @param array<string, string> $consents
     *
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    private function validateConsents(array $consents): array
    {
        $validTypes = ConsentType::values();
        $validStatuses = array_column(ConsentStatus::cases(), 'value');

        foreach ($consents as $type => $status) {
            if (!in_array($type, $validTypes, true)) {
                throw new InvalidArgumentException(sprintf('Unknown consent type "%s".', $type));
            }

            if (!in_array($status, $validStatuses, true)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid consent status "%s" for type "%s". Allowed: %s.', $status, $type, implode(', ', $validStatuses)),
                );
            }
        }

        return $consents;
    }

    private function writeLog(
        string $consentId,
        Tenant $tenant,
        string $sessionId,
        ?string $userId,
        ?ConsentStatus $previousStatus,
        ConsentStatus $newStatus,
        ConsentType $consentType,
        ConsentSource $source,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        $payload = json_encode([
            'consent_type' => $consentType->value,
            'previous_status' => $previousStatus?->value,
            'new_status' => $newStatus->value,
        ], JSON_THROW_ON_ERROR);

        $log = ConsentLog::create(
            consentId: Uuid::fromString($consentId),
            tenant: $tenant,
            sessionId: $sessionId,
            userId: $userId,
            payload: $payload,
            source: $source,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        $this->consentLogRepository->save($log);
    }
}
