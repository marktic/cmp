<?php

declare(strict_types=1);

namespace Marktic\Cmp\ConsentLogs\Models;

use Marktic\Cmp\Base\Models\CmpRecord;

/**
 * Class ConsentLog
 * @package Marktic\Cmp\ConsentLogs\Models
 */
class ConsentLog extends CmpRecord
{
    use ConsentLogTrait;

    public function getRegistry()
    {
        return null;
    }
}
