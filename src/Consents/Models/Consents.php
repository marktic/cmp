<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Models;

use Marktic\CMP\Base\Models\CmpRecords;

/**
 * Class Consents
 * @package Marktic\CMP\Consents\Models
 *
 * @method Consent getNewRecord($data = [])
 * @method Consent|null findOneByParams($params)
 */
class Consents extends CmpRecords
{
    use ConsentsTrait;

    public const TABLE = 'mkt_cmp_consents';
    public const CONTROLLER = 'mkt_cmp-consents';
}
