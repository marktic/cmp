<?php

declare(strict_types=1);

namespace Marktic\Cmp\Bundle\Modules\Api\Controllers;

use InvalidArgumentException;
use Marktic\Cmp\Base\Tenant;
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
 *       use ConsentControllerTrait;
 *
 *       protected function getCmpTenant(): Tenant
 *       {
 *           return new Tenant(
 *               type: $this->request->header('X-Tenant-Type'),
 *               id:   (int) $this->request->header('X-Tenant-Id'),
 *           );
 *       }
 *
 *       protected function getCmpUser(): ?object
 *       {
 *           return auth()->user(); // object with ->name and ->id
 *       }
 *
 *       protected function resolveConsents(): array
 *       {
 *           return $this->request->input('consent', []);
 *       }
 *   }
 *
 * POST /consent
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
     * Creates ConsentData from the request POST payload, resolves the tenant
     * via getCmpTenant() and the user via getCmpUser(), then records the consent.
     *
     * Returns an array that should be encoded as a JSON response by the
     * concrete controller using its framework's response utilities.
     *
     * @return array{status: string, message: string}|array{status: string, errors: mixed}
     */
    public function record(): array
    {
        try {
            $cmpTenant = $this->getCmpTenant();
            $tenantName = $cmpTenant->type;
            $tenantId = $cmpTenant->id;

            $consentsPayload = $this->resolveConsents();

            if (empty($consentsPayload)) {
                throw new InvalidArgumentException('Missing or empty "consent" payload.');
            }

            $consentData = ConsentData::createFromPayload($consentsPayload);
            $consentData->tenant = $tenantName;
            $consentData->tenantId = $tenantId;

            $userFinder = new FindOrCreateUser($tenantName, $tenantId);

            $request = $this->resolveRequest();
            if ($request !== null) {
                $userFinder = $userFinder->withRequest($request);
            }

            $cmpUser = $this->getCmpUser();
            if ($cmpUser !== null && isset($cmpUser->id)) {
                $userId = (string) $cmpUser->id;
                if ($userId !== '') {
                    $userFinder = $userFinder->withUser($userId);
                }
            }

            $user = $userFinder->find();

            (new RecordConsent())->handle(
                user: $user,
                consentData: $consentData,
                request: $request,
                source: $this->resolveConsentSource(),
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
     * Return the CMP tenant for the current request.
     *
     * The tenant's type (name) and id are used to scope consent records.
     */
    abstract protected function getCmpTenant(): Tenant;

    /**
     * Return the CMP user for the current request, or null for anonymous requests.
     *
     * The returned object should expose a non-null, non-empty `id` property
     * (string or integer) that identifies the user as an external user identifier.
     * Optionally it may expose a `name` property for display purposes.
     */
    abstract protected function getCmpUser(): ?object;

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
     * Return the parsed consent map from the request POST payload.
     *
     * Expected format: ['ad_storage' => 'granted', 'analytics_storage' => 'denied', ...]
     *
     * @return array<string, string>
     */
    abstract protected function resolveConsents(): array;
}
