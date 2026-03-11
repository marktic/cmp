<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Actions;

use InvalidArgumentException;
use Marktic\CMP\ConsentLogs\Models\ConsentLogs;
use Marktic\CMP\Consents\Enums\ConsentSource;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Models\Consent;
use Marktic\CMP\Utility\CmpModels;

/**
 * Records or updates user consent values for a session.
 *
 * For each consent type in the provided map:
 *   - Creates a new Consent record if none exists.
 *   - Updates the existing record and writes an audit log when the value changes.
 *   - Silently skips unchanged values (no log entry written).
 */
class RecordConsent extends AbstractAction
{
    /**
     * @param array<string, string> $consents  Keyed by ConsentType value, valued by ConsentStatus value.
     *
     * @throws InvalidArgumentException When an unknown consent type or status is provided.
     */
    public function handle(
        string $tenant,
        int $tenantId,
        string $sessionId,
        ?string $userId,
        array $consents,
        ConsentSource $source = ConsentSource::API,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $validated = $this->validateConsents($consents);
        $consentLogs = CmpModels::consentLogs();

        foreach ($validated as $type => $status) {
            $consentType = ConsentType::from($type);
            $consentStatus = ConsentStatus::from($status);

            $existing = $this->getRepository()->findBySessionAndType(
                $tenant,
                $tenantId,
                $sessionId,
                $consentType,
            );

            if ($existing === null) {
                $consent = $this->createConsentRecord(
                    $tenant, $tenantId, $sessionId, $userId, $consentType, $consentStatus
                );

                $this->writeLog(
                    consentLogs: $consentLogs,
                    consentId: (int) $consent->id,
                    tenant: $tenant,
                    tenantId: $tenantId,
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
                $existing->consent_value = $consentStatus->value;
                $this->getRepository()->save($existing);

                $this->writeLog(
                    consentLogs: $consentLogs,
                    consentId: (int) $existing->id,
                    tenant: $tenant,
                    tenantId: $tenantId,
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

    private function createConsentRecord(
        string $tenant,
        int $tenantId,
        string $sessionId,
        ?string $userId,
        ConsentType $consentType,
        ConsentStatus $consentStatus,
    ): Consent {
        /** @var Consent $consent */
        $consent = $this->getRepository()->getNewRecord([
            'tenant' => $tenant,
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'consent_type' => $consentType->value,
            'consent_value' => $consentStatus->value,
        ]);
        $this->getRepository()->save($consent);
        return $consent;
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
        ConsentLogs $consentLogs,
        int $consentId,
        string $tenant,
        int $tenantId,
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

        $log = $consentLogs->getNewRecord([
            'consent_id' => $consentId,
            'tenant' => $tenant,
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'payload' => $payload,
            'source' => $source->value,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $consentLogs->save($log);
    }
}
