<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Actions;

use Marktic\Cmp\Consents\Enums\ConsentType;
use Marktic\Cmp\Consents\Models\Consent;

/**
 * Retrieves the current consent state for a specific user and consent type.
 */
class GetConsent extends AbstractAction
{
    public function handle(int $userId, ConsentType $type): ?Consent
    {
        return $this->getRepository()->findByUserAndType($userId, $type);
    }
}
