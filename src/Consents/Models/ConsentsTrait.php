<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Models;

use Marktic\CMP\Base\Models\HasTenant\HasTenantRepository;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Utility\PackageConfig;
use Nip\Records\Collections\Collection;

trait ConsentsTrait
{
    use HasTenantRepository;

    /**
     * Find a consent record by session ID, tenant context, and consent type.
     */
    public function findBySessionAndType(
        string $tenant,
        int $tenantId,
        string $sessionId,
        ConsentType $type,
    ): ?Consent {
        return $this->findOneByParams([
            'where' => [
                ['tenant = ?', $tenant],
                ['tenant_id = ?', $tenantId],
                ['session_id = ?', $sessionId],
                ['consent_type = ?', $type->value],
            ],
        ]);
    }

    /**
     * Return all consent records for a given session.
     *
     * @return Consent[]|Collection
     */
    public function findAllBySession(
        string $tenant,
        int $tenantId,
        string $sessionId,
    ): array|Collection {
        return $this->findByParams([
            'where' => [
                ['tenant = ?', $tenant],
                ['tenant_id = ?', $tenantId],
                ['session_id = ?', $sessionId],
            ],
        ]);
    }

    /**
     * Return all consent records for a given user.
     *
     * @return Consent[]|Collection
     */
    public function findAllByUser(
        string $tenant,
        int $tenantId,
        string $userId,
    ): array|Collection {
        return $this->findByParams([
            'where' => [
                ['tenant = ?', $tenant],
                ['tenant_id = ?', $tenantId],
                ['user_id = ?', $userId],
            ],
        ]);
    }

    public function getTable(): string
    {
        return PackageConfig::tableName(static::TABLE, static::TABLE);
    }

    public function getModelNamespace(): string
    {
        return __NAMESPACE__;
    }
}
