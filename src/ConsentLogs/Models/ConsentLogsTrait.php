<?php

declare(strict_types=1);

namespace Marktic\Cmp\ConsentLogs\Models;

use Marktic\Cmp\Utility\PackageConfig;
use Nip\Records\Collections\Collection;

trait ConsentLogsTrait
{
    /**
     * Return all log entries for a given user.
     *
     * @return ConsentLog[]|Collection
     */
    public function findAllByUser(int $userId): array|Collection
    {
        return $this->findByParams([
            'where' => [
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
