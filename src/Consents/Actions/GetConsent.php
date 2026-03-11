<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Actions;

use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Models\Consent;
use Marktic\CMP\Consents\Repository\ConsentRepositoryInterface;

/**
 * Retrieves the current consent state for a specific session and consent type.
 */
class GetConsent
{
    public function __construct(
        private readonly ConsentRepositoryInterface $repository,
    ) {}

    public function execute(
        Tenant $tenant,
        string $sessionId,
        ConsentType $type,
    ): ?Consent {
        return $this->repository->findBySessionAndType($tenant, $sessionId, $type);
    }
}
