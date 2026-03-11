<?php

declare(strict_types=1);

namespace Marktic\Cmp\Tests\Unit\Base;

use InvalidArgumentException;
use Marktic\Cmp\Base\Tenant;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    public function testCreatesValidTenant(): void
    {
        $tenant = new Tenant('organization', 10);

        $this->assertSame('organization', $tenant->type);
        $this->assertSame(10, $tenant->id);
    }

    public function testToStringFormat(): void
    {
        $tenant = new Tenant('project', 44);

        $this->assertSame('project/44', (string) $tenant);
    }

    public function testEquality(): void
    {
        $a = new Tenant('workspace', 3);
        $b = new Tenant('workspace', 3);
        $c = new Tenant('workspace', 4);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testThrowsOnEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Tenant('', 1);
    }

    public function testThrowsOnZeroId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Tenant('organization', 0);
    }

    public function testThrowsOnNegativeId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Tenant('organization', -5);
    }
}
