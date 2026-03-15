<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Actions;

use InvalidArgumentException;
use Marktic\Cmp\ConsentLogs\Actions\RecordConsentLog;
use Marktic\Cmp\Consents\Dto\ConsentData;
use Marktic\Cmp\Consents\Enums\ConsentSource;
use Marktic\Cmp\Consents\Enums\ConsentStatus;
use Marktic\Cmp\Consents\Enums\ConsentType;
use Marktic\Cmp\Consents\Models\Consent;
use Marktic\Cmp\Users\Models\User;
use Marktic\Cmp\Utility\CmpModels;

/**
 * Records or updates consent values for a user.
 *
 * For each consent type provided in the ConsentData DTO:
 *   - Creates a new Consent record if none exists for this user.
 *   - Updates the existing record when the value changes.
 *   - Silently skips unchanged values.
 *
 * A single audit log entry is written per call, containing:
 *   - original_payload: the raw payload from ConsentData.
 *   - consents_updates: a list of changes made in this call.
 */
class RecordConsent extends AbstractAction
{
    /**
     * @param User        $user        The resolved user record (use FindOrCreateUser to obtain it).
     * @param ConsentData $consentData The consent values to apply.
     * @param object|null $request     Optional request object for IP/UA resolution in the log.
     * @param ConsentSource $source    Consent source (defaults to API).
     *
     * @throws InvalidArgumentException When an unknown consent type or status is provided.
     */
    public function handle(
        User $user,
        ConsentData $consentData,
        object|null $request = null,
        ConsentSource $source = ConsentSource::API,
    ): void {
        $consents = $consentData->getConsents();
        $validated = $this->validateConsents($consents);
        $updates = [];

        foreach ($validated as $type => $status) {
            $consentType = ConsentType::from($type);
            $consentStatus = ConsentStatus::from($status);

            $existing = $this->getRepository()->findByUserAndType((int) $user->id, $consentType);

            if ($existing === null) {
                $this->createConsentRecord($user, $consentType, $consentStatus, $consentData->context ?? null);

                $updates[] = [
                    'consent_type'    => $consentType->value,
                    'previous_status' => null,
                    'new_status'      => $consentStatus->value,
                ];
            } elseif ($existing->getConsentStatus() !== $consentStatus) {
                $previousStatus = $existing->getConsentStatus();
                $existing->consent_value = $consentStatus->value;
                $this->getRepository()->save($existing);

                $updates[] = [
                    'consent_type'    => $consentType->value,
                    'previous_status' => $previousStatus->value,
                    'new_status'      => $consentStatus->value,
                ];
            }
        }

        if (!empty($updates)) {
            $logPayload = [
                'original_payload'  => $consentData->payload,
                'consents_updates'  => $updates,
            ];

            (new RecordConsentLog())->handle($user, $request, $logPayload, $source);
        }
    }

    private function createConsentRecord(
        User $user,
        ConsentType $consentType,
        ConsentStatus $consentStatus,
        ?string $context,
    ): Consent {
        /** @var Consent $consent */
        $consent = $this->getRepository()->getNewRecord([
            'user_id'       => $user->id,
            'context'       => $context,
            'consent_type'  => $consentType->value,
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
}
