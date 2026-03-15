<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Models;

use Marktic\Cmp\Base\Models\CmpRecords;

/**
 * Class Users
 * @package Marktic\Cmp\Users\Models
 *
 * @method User getNewRecord($data = [])
 * @method User|null findOneByParams($params)
 */
class Users extends CmpRecords
{
    use UsersTrait;

    public const TABLE = 'mkt_cmp_users';
    public const CONTROLLER = 'mkt_cmp-users';
}
