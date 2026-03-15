<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Actions;

use Marktic\Cmp\Consents\Models\Consent;
use Nip\Records\Collections\Collection;

/**
 * Retrieves all current consent states for a given user.
 */
class GetAllConsentsForSession extends AbstractAction
{
    /**
     * @return Consent[]|Collection
     */
    public function handle(int $userId): array|Collection
    {
        return $this->getRepository()->findAllByUser($userId);
    }
}
