<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Actions;

use Marktic\Cmp\Consents\Enums\ConsentType;
use Marktic\Cmp\Consents\Models\Consent;

/**
 * Retrieves the current consent state for a specific session and consent type.
 */
class GetConsent extends AbstractAction
{
    public function handle(
        string $tenant,
        int $tenantId,
        string $sessionId,
        ConsentType $type,
    ): ?Consent {
        return $this->getRepository()->findBySessionAndType($tenant, $tenantId, $sessionId, $type);
    }
}
