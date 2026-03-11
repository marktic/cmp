<?php

declare(strict_types=1);

namespace Marktic\Cmp\Utility;

use ByTIC\PackageBase\Utility\ModelFinder;
use Marktic\Cmp\CmpServiceProvider;
use Marktic\Cmp\ConsentLogs\Models\ConsentLogs;
use Marktic\Cmp\Consents\Models\Consents;
use Nip\Records\RecordManager;

/**
 * Class CmpModels.
 */
class CmpModels extends ModelFinder
{
    public const CONSENTS = 'consents';
    public const CONSENT_LOGS = 'consent_logs';

    public static function consents(): Consents|RecordManager
    {
        return static::getModels(self::CONSENTS, Consents::class);
    }

    public static function consentsClass(): string
    {
        return static::getModelsClass(self::CONSENTS, Consents::class);
    }

    public static function consentLogs(): ConsentLogs|RecordManager
    {
        return static::getModels(self::CONSENT_LOGS, ConsentLogs::class);
    }

    public static function consentLogsClass(): string
    {
        return static::getModelsClass(self::CONSENT_LOGS, ConsentLogs::class);
    }

    protected static function packageName(): string
    {
        return CmpServiceProvider::NAME;
    }
}
