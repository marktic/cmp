<?php

declare(strict_types=1);

namespace Marktic\Cmp\Users\Models;

use Marktic\Cmp\Base\Models\HasTenant\HasTenantRecord;

trait UserTrait
{
    use HasTenantRecord;

    public ?string $session_id;
    public ?string $user_id;
    public string $created_at;
    public string $updated_at;

    public function getSessionId(): ?string
    {
        return $this->session_id ?? null;
    }

    public function getExternalUserId(): ?string
    {
        return $this->user_id ?? null;
    }
}
