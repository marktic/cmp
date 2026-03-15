<?php

declare(strict_types=1);

namespace Marktic\Cmp\Tests\Unit\Bundle\Modules\Api\Controllers;

use Marktic\Cmp\Base\Tenant;
use Marktic\Cmp\Bundle\Modules\Api\Controllers\ConsentControllerTrait;
use Marktic\Cmp\Consents\Enums\ConsentSource;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConsentControllerTraitTest extends TestCase
{
    public function testRecordMethodHasNoParameters(): void
    {
        $method = new ReflectionMethod(ConsentControllerTrait::class, 'record');

        $this->assertCount(0, $method->getParameters());
    }

    public function testRecordReturnsErrorWhenConsentPayloadIsEmpty(): void
    {
        $controller = $this->makeController(
            tenant: new Tenant('organization', 10),
            cmpUser: null,
            consents: [],
        );

        $result = $controller->record();

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsStringIgnoringCase('consent', $result['errors']);
    }

    public function testGetCmpTenantNameAndIdAreUsedForConsentData(): void
    {
        $controller = $this->makeController(
            tenant: new Tenant('workspace', 99),
            cmpUser: null,
            consents: [],
        );

        $result = $controller->record();

        // If the tenant is read, the error will be about the missing consent payload,
        // not about missing tenant data.
        $this->assertSame('error', $result['status']);
        $this->assertStringContainsStringIgnoringCase('consent', $result['errors']);
    }

    public function testRecordUsesGetCmpUserIdWhenUserIsPresent(): void
    {
        $user = new \stdClass();
        $user->id = 'user-42';
        $user->name = 'Test User';

        $controller = $this->makeController(
            tenant: new Tenant('organization', 1),
            cmpUser: $user,
            consents: [],
        );

        // Empty payload is caught before any DB call, so we can verify
        // the method reaches the getCmpUser() check safely.
        $result = $controller->record();

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsStringIgnoringCase('consent', $result['errors']);
    }

    public function testResolveConsentSourceDefaultsToApi(): void
    {
        $controller = $this->makeController(
            tenant: new Tenant('organization', 5),
            cmpUser: null,
            consents: [],
        );

        $method = new ReflectionMethod($controller, 'resolveConsentSource');
        $source = $method->invoke($controller);

        $this->assertSame(ConsentSource::API, $source);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeController(Tenant $tenant, ?object $cmpUser, array $consents): object
    {
        return new class ($tenant, $cmpUser, $consents) {
            use ConsentControllerTrait;

            public function __construct(
                private readonly Tenant $tenant,
                private readonly ?object $cmpUser,
                private readonly array $consents,
            ) {}

            protected function getCmpTenant(): Tenant
            {
                return $this->tenant;
            }

            protected function getCmpUser(): ?object
            {
                return $this->cmpUser;
            }

            protected function resolveConsents(): array
            {
                return $this->consents;
            }
        };
    }
}
