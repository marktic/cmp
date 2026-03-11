<?php

declare(strict_types=1);

namespace Marktic\CMP\Domain\Repository;

use Marktic\CMP\Domain\Consent;
use Marktic\CMP\Domain\Enum\ConsentType;
use Marktic\CMP\Domain\Tenant;

interface ConsentRepositoryInterface
{
    /**
     * Persist a new or updated Consent entity.
     */
    public function save(Consent $consent): void;

    /**
     * Find a consent record for a specific session and consent type.
     */
    public function findBySessionAndType(
        Tenant $tenant,
        string $sessionId,
        ConsentType $type,
    ): ?Consent;

    /**
     * Return all consent records for a given session.
     *
     * @return Consent[]
     */
    public function findAllBySession(
        Tenant $tenant,
        string $sessionId,
    ): array;

    /**
     * Return all consent records for a given user.
     *
     * @return Consent[]
     */
    public function findAllByUser(
        Tenant $tenant,
        string $userId,
    ): array;
}
