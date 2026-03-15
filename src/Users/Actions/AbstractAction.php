<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Actions;

use Bytic\Actions\Action;
use Bytic\Actions\Behaviours\Entities\HasRepository;
use Marktic\Cmp\Users\Models\Users;
use Marktic\Cmp\Utility\CmpModels;
use Nip\Records\AbstractModels\RecordManager;

/**
 * @method Users getRepository()
 */
abstract class AbstractAction extends Action
{
    use HasRepository;

    protected function generateRepository(): RecordManager
    {
        return CmpModels::users();
    }
}
