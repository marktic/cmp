<?php

declare(strict_types=1);

namespace Marktic\Cmp\ConsentLogs\Models;

use Marktic\Cmp\Base\Models\CmpRecords;

/**
 * Class ConsentLogs
 * @package Marktic\Cmp\ConsentLogs\Models
 *
 * @method ConsentLog getNewRecord($data = [])
 */
class ConsentLogs extends CmpRecords
{
    use ConsentLogsTrait;

    public const TABLE = 'mkt_cmp_consent_logs';
    public const CONTROLLER = 'mkt_cmp-consent-logs';
}
