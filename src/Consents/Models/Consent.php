<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Models;

use Marktic\CMP\Base\Models\CmpRecord;

/**
 * Class Consent
 * @package Marktic\CMP\Consents\Models
 */
class Consent extends CmpRecord
{
    use ConsentTrait;

    public function getRegistry()
    {
        return null;
    }
}
