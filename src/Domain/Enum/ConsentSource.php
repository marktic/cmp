<?php

declare(strict_types=1);

namespace Marktic\CMP\Domain\Enum;

enum ConsentSource: string
{
    case API = 'api';
    case FRONTEND = 'frontend';
    case IMPORT = 'import';
    case ADMIN = 'admin';
}
