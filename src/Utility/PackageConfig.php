<?php

declare(strict_types=1);

namespace Marktic\Cmp\Utility;

use Marktic\Cmp\CmpServiceProvider;
use Nip\Utility\Traits\SingletonTrait;

/**
 * Class PackageConfig.
 */
class PackageConfig extends \ByTIC\PackageBase\Utility\PackageConfig
{
    use SingletonTrait;

    protected $name = CmpServiceProvider::NAME;

    public static function configPath(): string
    {
        return __DIR__ . '/../../config/mkt_cmp.php';
    }

    public static function tableName(string $name, ?string $default = null): ?string
    {
        return static::instance()->get('tables.' . $name, $default);
    }

    public static function databaseConnection(): ?string
    {
        return (string) static::instance()->get('database.connection');
    }

    public static function shouldRunMigrations(): bool
    {
        return false !== static::instance()->get('database.migrations', false);
    }
}
