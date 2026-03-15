<?php

declare(strict_types=1);

namespace Marktic\Cmp\Tests\Unit\Consents\Dto;

use Marktic\Cmp\Consents\Dto\ConsentData;
use Marktic\Cmp\Consents\Enums\ConsentType;
use PHPUnit\Framework\TestCase;

class ConsentDataTest extends TestCase
{
    public function testCreateFromPayloadMapsAllTypes(): void
    {
        $payload = [
            'ad_storage'              => 'granted',
            'analytics_storage'       => 'denied',
            'ad_user_data'            => 'granted',
            'ad_personalization'      => 'denied',
            'functionality_storage'   => 'granted',
            'security_storage'        => 'granted',
            'personalization_storage' => 'denied',
        ];

        $dto = ConsentData::createFromPayload($payload);

        $this->assertSame('granted', $dto->adStorage);
        $this->assertSame('denied', $dto->analyticsStorage);
        $this->assertSame('granted', $dto->adUserData);
        $this->assertSame('denied', $dto->adPersonalization);
        $this->assertSame('granted', $dto->functionalityStorage);
        $this->assertSame('granted', $dto->securityStorage);
        $this->assertSame('denied', $dto->personalizationStorage);
    }

    public function testCreateFromPayloadStoresOriginalPayload(): void
    {
        $payload = ['ad_storage' => 'granted'];
        $dto = ConsentData::createFromPayload($payload);

        $this->assertSame($payload, $dto->payload);
    }

    public function testCreateFromPayloadWithPartialData(): void
    {
        $payload = ['analytics_storage' => 'granted'];
        $dto = ConsentData::createFromPayload($payload);

        $this->assertSame('granted', $dto->analyticsStorage);
        $this->assertNull($dto->adStorage);
        $this->assertNull($dto->adUserData);
    }

    public function testGetConsentsFiltersNulls(): void
    {
        $payload = ['ad_storage' => 'granted', 'analytics_storage' => 'denied'];
        $dto = ConsentData::createFromPayload($payload);

        $consents = $dto->getConsents();

        $this->assertCount(2, $consents);
        $this->assertSame('granted', $consents[ConsentType::AD_STORAGE->value]);
        $this->assertSame('denied', $consents[ConsentType::ANALYTICS_STORAGE->value]);
        $this->assertArrayNotHasKey(ConsentType::AD_USER_DATA->value, $consents);
    }

    public function testTenantAndTenantIdAreSetByController(): void
    {
        $dto = ConsentData::createFromPayload([]);
        $dto->tenant = 'organization';
        $dto->tenantId = 42;

        $this->assertSame('organization', $dto->tenant);
        $this->assertSame(42, $dto->tenantId);
    }

    public function testGetConsentsReturnsEmptyWhenNoConsentsSet(): void
    {
        $dto = new ConsentData();
        $this->assertSame([], $dto->getConsents());
    }
}
