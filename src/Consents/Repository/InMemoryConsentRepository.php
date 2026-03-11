<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Repository;

use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Models\Consent;

/**
 * In-memory implementation of ConsentRepositoryInterface.
 *
 * Intended for testing and lightweight scenarios. Replace with a
 * database-backed implementation (e.g. Doctrine, Eloquent) in production.
 */
class InMemoryConsentRepository implements ConsentRepositoryInterface
{
    /** @var array<string, Consent> */
    private array $storage = [];

    public function save(Consent $consent): void
    {
        $this->storage[$this->key($consent->getTenant(), $consent->getSessionId(), $consent->getConsentType())] = $consent;
    }

    public function findBySessionAndType(
        Tenant $tenant,
        string $sessionId,
        ConsentType $type,
    ): ?Consent {
        return $this->storage[$this->key($tenant, $sessionId, $type)] ?? null;
    }

    public function findAllBySession(
        Tenant $tenant,
        string $sessionId,
    ): array {
        $prefix = sprintf('%s|%d|%s|', $tenant->type, $tenant->id, $sessionId);

        return array_values(
            array_filter(
                $this->storage,
                static fn (Consent $c) => str_starts_with(
                    sprintf('%s|%d|%s|', $c->getTenant()->type, $c->getTenant()->id, $c->getSessionId()),
                    $prefix,
                ),
            ),
        );
    }

    public function findAllByUser(
        Tenant $tenant,
        string $userId,
    ): array {
        return array_values(
            array_filter(
                $this->storage,
                static fn (Consent $c) => $c->getTenant()->equals($tenant) && $c->getUserId() === $userId,
            ),
        );
    }

    private function key(Tenant $tenant, string $sessionId, ConsentType $type): string
    {
        return sprintf('%s|%d|%s|%s', $tenant->type, $tenant->id, $sessionId, $type->value);
    }
}
