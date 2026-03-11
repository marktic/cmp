<?php

declare(strict_types=1);

namespace Marktic\CMP\Http\Trait;

use InvalidArgumentException;
use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Actions\RecordConsent;
use Marktic\CMP\Consents\Enums\ConsentSource;

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
trait ConsentApiControllerTrait
{
    /**
     * Handle a POST /consent request.
     *
     * Returns an array that should be encoded as a JSON response by the
     * concrete controller using its framework's response utilities.
     *
     * @return array{status: string, message: string}|array{status: string, errors: mixed}
     */
    public function handleConsentUpdate(RecordConsent $recordConsent): array
    {
        try {
            $tenantType = $this->resolveTenantType();
            $tenantId = $this->resolveTenantId();
            $sessionId = $this->resolveSessionId();
            $userId = $this->resolveUserId();
            $source = $this->resolveConsentSource();
            $ipAddress = $this->resolveIpAddress();
            $userAgent = $this->resolveUserAgent();
            $consents = $this->resolveConsents();

            if ($tenantType === null || $tenantType === '') {
                throw new InvalidArgumentException('Missing or empty X-Tenant-Type header.');
            }

            if ($tenantId === null || $tenantId <= 0) {
                throw new InvalidArgumentException('Missing or invalid X-Tenant-Id header. Must be a positive integer.');
            }

            if ($sessionId === null || $sessionId === '') {
                throw new InvalidArgumentException('Missing session ID. Provide it via a cookie or X-Session-Id header.');
            }

            if (empty($consents)) {
                throw new InvalidArgumentException('Missing or empty "consent" payload.');
            }

            $tenant = new Tenant($tenantType, $tenantId);

            $recordConsent->execute(
                tenant: $tenant,
                sessionId: $sessionId,
                userId: $userId,
                consents: $consents,
                source: $source,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
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
     * Return the current session ID (from cookie, header, or route).
     */
    abstract protected function resolveSessionId(): ?string;

    /**
     * Return the authenticated user's ID, or null for anonymous requests.
     */
    abstract protected function resolveUserId(): ?string;

    /**
     * Return the source of the consent update.
     * Defaults to ConsentSource::API; override to change the default.
     */
    protected function resolveConsentSource(): ConsentSource
    {
        return ConsentSource::API;
    }

    /**
     * Return the client IP address, or null if not available.
     */
    abstract protected function resolveIpAddress(): ?string;

    /**
     * Return the User-Agent string, or null if not available.
     */
    abstract protected function resolveUserAgent(): ?string;

    /**
     * Return the parsed consent map from the request body.
     *
     * Expected format: ['ad_storage' => 'granted', 'analytics_storage' => 'denied', ...]
     *
     * @return array<string, string>
     */
    abstract protected function resolveConsents(): array;
}
