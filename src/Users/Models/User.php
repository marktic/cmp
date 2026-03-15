<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Models;

use Marktic\Cmp\Base\Models\CmpRecord;

/**
 * Class User
 * @package Marktic\Cmp\Users\Models
 */
class User extends CmpRecord
{
    use UserTrait;

    public function getRegistry()
    {
        return null;
    }
}
