<?php

use Marktic\Cmp\ConsentLogs\Models\ConsentLogs;
use Marktic\Cmp\Consents\Models\Consents;
use Marktic\Cmp\Users\Models\Users;
use Marktic\Cmp\Utility\CmpModels;

return [
    'models' => [
        CmpModels::CONSENTS => Consents::class,
        CmpModels::CONSENT_LOGS => ConsentLogs::class,
        CmpModels::USERS => Users::class,
    ],
    'tables' => [
        CmpModels::CONSENTS => Consents::TABLE,
        CmpModels::CONSENT_LOGS => ConsentLogs::TABLE,
        CmpModels::USERS => Users::TABLE,
    ],
    'database' => [
        'connection' => 'default',
        'migrations' => true,
    ],
];
