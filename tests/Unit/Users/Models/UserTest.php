<?php

declare(strict_types=1);

namespace Marktic\Cmp\Tests\Unit\Users\Models;

use Marktic\Cmp\Users\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function makeUser(
        string $tenant = 'organization',
        int $tenantId = 10,
        string $sessionId = 'sess-abc',
        ?string $userId = null,
    ): User {
        $user = new User();
        $user->tenant = $tenant;
        $user->tenant_id = $tenantId;
        $user->session_id = $sessionId;
        $user->user_id = $userId;
        return $user;
    }

    public function testGetSessionId(): void
    {
        $user = $this->makeUser(sessionId: 'my-session');
        $this->assertSame('my-session', $user->getSessionId());
    }

    public function testGetExternalUserIdWhenNull(): void
    {
        $user = $this->makeUser(userId: null);
        $this->assertNull($user->getExternalUserId());
    }

    public function testGetExternalUserIdWhenSet(): void
    {
        $user = $this->makeUser(userId: 'ext-user-123');
        $this->assertSame('ext-user-123', $user->getExternalUserId());
    }

    public function testTenantFields(): void
    {
        $user = $this->makeUser(tenant: 'workspace', tenantId: 7);
        $this->assertSame('workspace', $user->tenant);
        $this->assertSame(7, $user->tenant_id);
    }
}
