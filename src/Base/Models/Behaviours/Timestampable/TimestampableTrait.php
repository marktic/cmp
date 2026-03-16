<?php

namespace Marktic\Cmp\Base\Models\Behaviours\Timestampable;

trait TimestampableTrait
{
    use \ByTIC\DataObjects\Behaviors\Timestampable\TimestampableTrait;

    /**
     * @var string
     */
    protected static $createTimestamps = ['created_at'];

    /**
     * @var string
     */
    protected static $updateTimestamps = ['updated_at'];

}