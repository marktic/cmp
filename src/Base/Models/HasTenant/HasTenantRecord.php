<?php

declare(strict_types=1);

namespace Marktic\Cmp\Base\Models\HasTenant;

use Nip\Records\Record;

/**
 * Trait HasTenantRecord
 *
 * @method Record getTenant()
 */
trait HasTenantRecord
{
    public string|int $tenant_id;
    public string $tenant;

    /**
     * @param Record $record
     */
    public function populateFromTenant($record): void
    {
        $this->tenant_id = $record->id;
        $this->tenant = $record->getManager()->getMorphName();
    }
}
