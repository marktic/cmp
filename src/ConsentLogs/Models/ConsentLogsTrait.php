<?php

declare(strict_types=1);

namespace Marktic\Cmp\ConsentLogs\Models;

use Marktic\Cmp\Base\Models\HasTenant\HasTenantRepository;
use Marktic\Cmp\Utility\PackageConfig;
use Nip\Records\Collections\Collection;

trait ConsentLogsTrait
{
    use HasTenantRepository;

    /**
     * Return all log entries for a given session.
     *
     * @return ConsentLog[]|Collection
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
            'order' => 'created_at ASC',
        ]);
    }

    /**
     * Return all log entries for a given user.
     *
     * @return ConsentLog[]|Collection
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
            'order' => 'created_at ASC',
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
