<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Actions;

use Marktic\Cmp\Users\Models\User;

/**
 * Finds an existing user record or creates a new one.
 *
 * Mandatory parameters are tenant and tenantId, which uniquely scope the search.
 * Additional search criteria can be chained via the builder methods:
 *
 *   $user = (new FindOrCreateUser('organization', 10))
 *       ->withRequest($request)   // extract session_id (and optionally user_id) from request
 *       ->withUser($userId)       // provide an external user identifier
 *       ->find();
 *
 * The find() method applies all configured criteria in priority order:
 *   1. If an external user_id is set, search by tenant + tenantId + user_id.
 *   2. If a session_id is set, search by tenant + tenantId + session_id.
 *   3. If no matching user is found, a new record is created with the available data.
 */
class FindOrCreateUser extends AbstractAction
{
    private ?string $sessionId = null;
    private ?string $userId = null;

    public function __construct(
        private readonly string $tenant,
        private readonly int $tenantId,
    ) {}

    /**
     * Extract session_id and optionally user_id from a request object.
     *
     * Supports any object that exposes session / header data through common
     * conventions (e.g. Symfony HttpFoundation Request):
     *
     *   - $request->getSession()->getId()     → session_id
     *   - $request->headers->get('X-Session-Id') → session_id (fallback)
     *   - $request->headers->get('X-User-Id')    → user_id (if authenticated)
     */
    public function withRequest(object $request): static
    {
        $this->sessionId = session_id() ?: null;

        if (method_exists($request, 'getSession')) {
            try {
                $session = $request->getSession();
                if ($session !== null && method_exists($session, 'getId')) {
                    $this->sessionId = (string) $session->getId();
                }
            } catch (\Throwable) {
                // session not started or not available
            }
        }

        if (($this->sessionId === null || $this->sessionId === '') && isset($request->headers)) {
            $headerSessionId = $request->headers->get('X-Session-Id');
            if ($headerSessionId !== null && $headerSessionId !== '') {
                $this->sessionId = (string) $headerSessionId;
            }
        }

        if (isset($request->headers)) {
            $headerUserId = $request->headers->get('X-User-Id');
            if ($headerUserId !== null && $headerUserId !== '') {
                $this->userId = (string) $headerUserId;
            }
        }

        return $this;
    }

    /**
     * Set the external user identifier to search by.
     *
     * Accepts either a string user_id or a User model instance (whose user_id
     * field will be used).
     */
    public function withUser(object|string|int $user): static
    {
        $this->userId = is_object($user) ? $user->id : $user;

        return $this;
    }

    /**
     * Find an existing user by the configured criteria, or create a new one.
     *
     * Search priority:
     *   1. By tenant + tenantId + external user_id (if provided).
     *   2. By tenant + tenantId + session_id (if provided).
     *   3. Create a new user record with the available data.
     */
    public function find(): User
    {
        $repository = $this->getRepository();

        if ($this->userId !== null && $this->userId !== '') {
            $user = $repository->findByTenantAndUserId($this->tenant, $this->tenantId, $this->userId);
            if ($user !== null) {
                return $user;
            }
        }

        if ($this->sessionId !== null && $this->sessionId !== '') {
            $user = $repository->findByTenantAndSession($this->tenant, $this->tenantId, $this->sessionId);
            if ($user !== null) {
                return $user;
            }
        }

        return $this->createUser();
    }

    private function createUser(): User
    {
        $repository = $this->getRepository();

        /** @var User $user */
        $user = $repository->getNewRecord([
            'tenant' => $this->tenant,
            'tenant_id' => $this->tenantId,
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
        ]);

        $repository->save($user);

        return $user;
    }
}
