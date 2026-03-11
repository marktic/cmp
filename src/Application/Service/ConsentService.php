<?php

declare(strict_types=1);

namespace Marktic\CMP\Application\Service;

use InvalidArgumentException;
use Marktic\CMP\Domain\Consent;
use Marktic\CMP\Domain\ConsentLog;
use Marktic\CMP\Domain\Enum\ConsentSource;
use Marktic\CMP\Domain\Enum\ConsentStatus;
use Marktic\CMP\Domain\Enum\ConsentType;
use Marktic\CMP\Domain\Repository\ConsentLogRepositoryInterface;
use Marktic\CMP\Domain\Repository\ConsentRepositoryInterface;
use Marktic\CMP\Domain\Tenant;

class ConsentService
{
    public function __construct(
        private readonly ConsentRepositoryInterface $consentRepository,
        private readonly ConsentLogRepositoryInterface $consentLogRepository,
    ) {}

    /**
     * Record or update consent values for a session.
     *
     * @param array<string, string> $consents  Keyed by ConsentType value, valued by ConsentStatus value.
     *
     * @throws InvalidArgumentException When an unknown consent type or status is provided.
     */
    public function recordConsent(
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
     * Retrieve the current consent status for a specific type and session.
     */
    public function getConsent(
        Tenant $tenant,
        string $sessionId,
        ConsentType $type,
    ): ?Consent {
        return $this->consentRepository->findBySessionAndType($tenant, $sessionId, $type);
    }

    /**
     * Retrieve all current consent states for a session.
     *
     * @return Consent[]
     */
    public function getAllConsentsForSession(
        Tenant $tenant,
        string $sessionId,
    ): array {
        return $this->consentRepository->findAllBySession($tenant, $sessionId);
    }

    /**
     * Validate the raw consent input array.
     *
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
            consentId: \Ramsey\Uuid\Uuid::fromString($consentId),
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
