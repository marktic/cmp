<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Models;

use Marktic\Cmp\Base\Models\Behaviours\HasTenant\HasTenantRecord;
use Marktic\Cmp\Base\Models\Behaviours\Timestampable\TimestampableTrait;

trait UserTrait
{
    use HasTenantRecord;
    use TimestampableTrait;

    public ?string $session_id = null;
    public ?string $user_id = null;

    public function getSessionId(): ?string
    {
        return $this->session_id ?? null;
    }

    public function getExternalUserId(): ?string
    {
        return $this->user_id ?? null;
    }
}
