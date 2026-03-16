<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Models;

use Marktic\Cmp\Base\Models\Behaviours\Timestampable\TimestampableManagerTrait;
use Marktic\Cmp\Consents\Enums\ConsentType;
use Marktic\Cmp\Utility\PackageConfig;
use Nip\Records\Collections\Collection;

trait ConsentsTrait
{
    use TimestampableManagerTrait;
    /**
     * Find a consent record by user ID and consent type.
     */
    public function findByUserAndType(
        int $userId,
        ConsentType $type,
    ): ?Consent {
        return $this->findOneByParams([
            'where' => [
                ['user_id = ?', $userId],
                ['consent_type = ?', $type->value],
            ],
        ]);
    }

    /**
     * Return all consent records for a given user.
     *
     * @return Consent[]|Collection
     */
    public function findAllByUser(int $userId): array|Collection
    {
        return $this->findByParams([
            'where' => [
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
