<?php

declare(strict_types=1);

namespace Marktic\CMP\ConsentLogs\Models;

use Marktic\CMP\Base\Models\CmpRecords;

/**
 * Class ConsentLogs
 * @package Marktic\CMP\ConsentLogs\Models
 *
 * @method ConsentLog getNewRecord($data = [])
 */
class ConsentLogs extends CmpRecords
{
    use ConsentLogsTrait;

    public const TABLE = 'mkt_cmp_consent_logs';
    public const CONTROLLER = 'mkt_cmp-consent-logs';
}
