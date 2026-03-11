<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Models;

use Marktic\Cmp\Base\Models\CmpRecord;

/**
 * Class Consent
 * @package Marktic\Cmp\Consents\Models
 */
class Consent extends CmpRecord
{
    use ConsentTrait;

    public function getRegistry()
    {
        return null;
    }
}
