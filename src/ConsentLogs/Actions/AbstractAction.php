<?php

declare(strict_types=1);

namespace Marktic\Cmp\ConsentLogs\Actions;

use Bytic\Actions\Action;
use Bytic\Actions\Behaviours\Entities\HasRepository;
use Marktic\Cmp\ConsentLogs\Models\ConsentLogs;
use Marktic\Cmp\Utility\CmpModels;
use Nip\Records\AbstractModels\RecordManager;

/**
 * @method ConsentLogs getRepository()
 */
abstract class AbstractAction extends Action
{
    use HasRepository;

    protected function generateRepository(): RecordManager
    {
        return CmpModels::consentLogs();
    }
}
