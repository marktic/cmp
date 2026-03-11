<?php

declare(strict_types=1);

namespace Marktic\CMP\Base\Models\HasTenant;

/**
 * Trait HasTenantRepository
 */
trait HasTenantRepository
{
    public function initRelations(): void
    {
        parent::initRelations();
        $this->initRelationsCmp();
    }

    protected function initRelationsCmp(): void
    {
        $this->initRelationsCmpTenant();
    }

    protected function initRelationsCmpTenant(): void
    {
        $this->morphTo('Tenant', ['morphPrefix' => 'tenant', 'morphTypeField' => 'tenant']);
    }
}
