<?php

declare(strict_types=1);

namespace Marktic\CMP\Base\Models;

use Nip\Records\RecordManager;

class CmpRecords extends RecordManager
{
    use Traits\BaseRepositoryTrait;

    public function getRootNamespace(): string
    {
        return 'Marktic\CMP\Models\\';
    }
}
