<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Models;

use Marktic\Cmp\Base\Models\HasTenant\HasTenantRepository;
use Marktic\Cmp\Utility\PackageConfig;
use Nip\Records\Collections\Collection;

trait UsersTrait
{
    use HasTenantRepository;

    /**
     * Find a user record by tenant and session ID.
     */
    public function findByTenantAndSession(
        string $tenant,
        int $tenantId,
        string $sessionId,
    ): ?User {
        return $this->findOneByParams([
            'where' => [
                ['tenant = ?', $tenant],
                ['tenant_id = ?', $tenantId],
                ['session_id = ?', $sessionId],
            ],
        ]);
    }

    /**
     * Find a user record by tenant and external user ID.
     */
    public function findByTenantAndUserId(
        string $tenant,
        int $tenantId,
        string $userId,
    ): ?User {
        return $this->findOneByParams([
            'where' => [
                ['tenant = ?', $tenant],
                ['tenant_id = ?', $tenantId],
                ['user_id = ?', $userId],
            ],
        ]);
    }

    /**
     * Return all user records for a given tenant.
     *
     * @return User[]|Collection
     */
    public function findAllByTenant(
        string $tenant,
        int $tenantId,
    ): array|Collection {
        return $this->findByParams([
            'where' => [
                ['tenant = ?', $tenant],
                ['tenant_id = ?', $tenantId],
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
