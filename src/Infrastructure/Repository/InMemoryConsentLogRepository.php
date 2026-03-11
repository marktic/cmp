<?php

declare(strict_types=1);

namespace Marktic\CMP\Infrastructure\Repository;

use Marktic\CMP\Domain\ConsentLog;
use Marktic\CMP\Domain\Repository\ConsentLogRepositoryInterface;
use Marktic\CMP\Domain\Tenant;

/**
 * In-memory implementation of ConsentLogRepositoryInterface.
 *
 * Intended for testing and lightweight scenarios. Replace with a
 * database-backed implementation (e.g. Doctrine, Eloquent) in production.
 */
class InMemoryConsentLogRepository implements ConsentLogRepositoryInterface
{
    /** @var ConsentLog[] */
    private array $storage = [];

    public function save(ConsentLog $log): void
    {
        $this->storage[] = $log;
    }

    public function findAllBySession(
        Tenant $tenant,
        string $sessionId,
    ): array {
        return array_values(
            array_filter(
                $this->storage,
                static fn (ConsentLog $l) => $l->getTenant()->equals($tenant) && $l->getSessionId() === $sessionId,
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
                static fn (ConsentLog $l) => $l->getTenant()->equals($tenant) && $l->getUserId() === $userId,
            ),
        );
    }
}
