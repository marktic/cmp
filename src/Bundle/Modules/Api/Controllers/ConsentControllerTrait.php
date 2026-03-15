<?php

declare(strict_types=1);

namespace Marktic\Cmp\Bundle\Modules\Api\Controllers;

use InvalidArgumentException;
use Marktic\Cmp\Consents\Actions\RecordConsent;
use Marktic\Cmp\Consents\Dto\ConsentData;
use Marktic\Cmp\Consents\Enums\ConsentSource;
use Marktic\Cmp\Users\Actions\FindOrCreateUser;

/**
 * Reusable trait for framework API controllers that handle consent updates.
 *
 * The consuming controller must implement the abstract helper methods defined
 * at the bottom of this trait so that the trait remains framework-agnostic.
 *
 * Example (pseudo-framework):
 *
 *   class ConsentController
 *   {
 *       use ConsentApiControllerTrait;
 *
 *       public function __construct(private readonly RecordConsent $recordConsent) {}
 *
 *       // Implement the abstract methods for your framework.
 *   }
 *
 * POST /consent
 *
 * Headers:
 *   X-Tenant-Type: organization
 *   X-Tenant-Id:   10
 *
 * Body (JSON):
 *   {
 *     "consent": {
 *       "ad_storage": "granted",
 *       "analytics_storage": "granted",
 *       ...
 *     }
 *   }
 */
trait ConsentControllerTrait
{
    /**
     * Handle a POST /consent request.
     *
     * Returns an array that should be encoded as a JSON response by the
     * concrete controller using its framework's response utilities.
     *
     * @return array{status: string, message: string}|array{status: string, errors: mixed}
     */
    public function record(RecordConsent $recordConsent): array
    {
        try {
            $tenantType = $this->resolveTenantType();
            $tenantId = $this->resolveTenantId();
            $source = $this->resolveConsentSource();
            $consentsPayload = $this->resolveConsents();

            if ($tenantType === null || $tenantType === '') {
                throw new InvalidArgumentException('Missing or empty X-Tenant-Type header.');
            }

            if ($tenantId === null || $tenantId <= 0) {
                throw new InvalidArgumentException('Missing or invalid X-Tenant-Id header. Must be a positive integer.');
            }

            if (empty($consentsPayload)) {
                throw new InvalidArgumentException('Missing or empty "consent" payload.');
            }

            $consentData = ConsentData::createFromPayload($consentsPayload);
            $consentData->tenant = $tenantType;
            $consentData->tenantId = $tenantId;

            $userFinder = new FindOrCreateUser($tenantType, $tenantId);

            $request = $this->resolveRequest();
            if ($request !== null) {
                $userFinder = $userFinder->withRequest($request);
            }

            $userId = $this->resolveUserId();
            if ($userId !== null && $userId !== '') {
                $userFinder = $userFinder->withUser($userId);
            }

            $user = $userFinder->find();

            $recordConsent->handle(
                user: $user,
                consentData: $consentData,
                request: $request,
                source: $source,
            );

            return ['status' => 'ok', 'message' => 'Consent recorded successfully.'];
        } catch (InvalidArgumentException $e) {
            return ['status' => 'error', 'errors' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Framework bridge methods — implement these in your concrete controller
    // -------------------------------------------------------------------------

    /**
     * Return the value of the X-Tenant-Type header (or equivalent route param).
     */
    abstract protected function resolveTenantType(): ?string;

    /**
     * Return the integer value of the X-Tenant-Id header (or equivalent route param).
     */
    abstract protected function resolveTenantId(): ?int;

    /**
     * Return the authenticated user's ID, or null for anonymous requests.
     */
    abstract protected function resolveUserId(): ?string;

    /**
     * Return the current request object, or null if not available.
     *
     * When provided, it is used to extract session ID, IP address and User-Agent.
     */
    protected function resolveRequest(): ?object
    {
        return null;
    }

    /**
     * Return the source of the consent update.
     * Defaults to ConsentSource::API; override to change the default.
     */
    protected function resolveConsentSource(): ConsentSource
    {
        return ConsentSource::API;
    }

    /**
     * Return the parsed consent map from the request body.
     *
     * Expected format: ['ad_storage' => 'granted', 'analytics_storage' => 'denied', ...]
     *
     * @return array<string, string>
     */
    abstract protected function resolveConsents(): array;
}
