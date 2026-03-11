<?php

declare(strict_types=1);

namespace Marktic\CMP;

use ByTIC\PackageBase\BaseBootableServiceProvider;
use Marktic\CMP\Utility\PackageConfig;

/**
 * Class CmpServiceProvider.
 */
class CmpServiceProvider extends BaseBootableServiceProvider
{
    public const NAME = 'mkt_cmp';

    public function migrations(): ?string
    {
        if (PackageConfig::shouldRunMigrations()) {
            return dirname(__DIR__) . '/database/migrations/';
        }

        return null;
    }
}
