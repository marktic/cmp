<?php

declare(strict_types=1);

namespace Marktic\Cmp\Consents\Enums;

enum ConsentSource: string
{
    case API = 'api';
    case FRONTEND = 'frontend';
    case IMPORT = 'import';
    case ADMIN = 'admin';
}
