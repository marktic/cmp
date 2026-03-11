<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Actions;

use Marktic\CMP\Consents\Models\Consent;
use Nip\Records\Collections\Collection;

/**
 * Retrieves all current consent states for a given session.
 */
class GetAllConsentsForSession extends AbstractAction
{
    /**
     * @return Consent[]|Collection
     */
    public function handle(
        string $tenant,
        int $tenantId,
        string $sessionId,
    ): array|Collection {
        return $this->getRepository()->findAllBySession($tenant, $tenantId, $sessionId);
    }
}
