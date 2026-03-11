<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Actions;

use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Models\Consent;
use Marktic\CMP\Consents\Repository\ConsentRepositoryInterface;

/**
 * Retrieves all current consent states for a given session.
 */
class GetAllConsentsForSession
{
    public function __construct(
        private readonly ConsentRepositoryInterface $repository,
    ) {}

    /**
     * @return Consent[]
     */
    public function execute(
        Tenant $tenant,
        string $sessionId,
    ): array {
        return $this->repository->findAllBySession($tenant, $sessionId);
    }
}
