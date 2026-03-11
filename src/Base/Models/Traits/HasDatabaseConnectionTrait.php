<?php

declare(strict_types=1);

namespace Marktic\CMP\Base\Models\Traits;

use Marktic\CMP\Utility\PackageConfig;

trait HasDatabaseConnectionTrait
{
    public function getDbConnection(): ?string
    {
        return PackageConfig::databaseConnection() ?: null;
    }
}
