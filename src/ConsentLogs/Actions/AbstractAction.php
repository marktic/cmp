<?php

declare(strict_types=1);

namespace Marktic\CMP\ConsentLogs\Actions;

use Bytic\Actions\Action;
use Bytic\Actions\Behaviours\Entities\HasRepository;
use Marktic\CMP\ConsentLogs\Models\ConsentLogs;
use Marktic\CMP\Utility\CmpModels;
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
