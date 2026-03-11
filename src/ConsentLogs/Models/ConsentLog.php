<?php

declare(strict_types=1);

namespace Marktic\CMP\ConsentLogs\Models;

use Marktic\CMP\Base\Models\CmpRecord;

/**
 * Class ConsentLog
 * @package Marktic\CMP\ConsentLogs\Models
 */
class ConsentLog extends CmpRecord
{
    use ConsentLogTrait;

    public function getRegistry()
    {
        return null;
    }
}
