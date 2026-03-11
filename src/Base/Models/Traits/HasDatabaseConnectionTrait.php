<?php

declare(strict_types=1);

namespace Marktic\Cmp\Base\Models\Traits;

use Marktic\Cmp\Utility\PackageConfig;

trait HasDatabaseConnectionTrait
{
    public function getDbConnection(): ?string
    {
        return PackageConfig::databaseConnection() ?: null;
    }
}
