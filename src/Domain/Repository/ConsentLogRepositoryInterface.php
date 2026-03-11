<?php

declare(strict_types=1);

namespace Marktic\CMP\Domain\Repository;

use Marktic\CMP\Domain\ConsentLog;
use Marktic\CMP\Domain\Tenant;

interface ConsentLogRepositoryInterface
{
    /**
     * Persist a consent log entry.
     */
    public function save(ConsentLog $log): void;

    /**
     * Return all log entries for a given session.
     *
     * @return ConsentLog[]
     */
    public function findAllBySession(
        Tenant $tenant,
        string $sessionId,
    ): array;

    /**
     * Return all log entries for a given user.
     *
     * @return ConsentLog[]
     */
    public function findAllByUser(
        Tenant $tenant,
        string $userId,
    ): array;
}
