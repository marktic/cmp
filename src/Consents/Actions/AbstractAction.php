<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Actions;

use Bytic\Actions\Action;
use Bytic\Actions\Behaviours\Entities\HasRepository;
use Marktic\Cmp\Consents\Models\Consents;
use Marktic\Cmp\Utility\CmpModels;
use Nip\Records\AbstractModels\RecordManager;

/**
 * @method Consents getRepository()
 */
abstract class AbstractAction extends Action
{
    use HasRepository;

    protected function generateRepository(): RecordManager
    {
        return CmpModels::consents();
    }
}
