<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Domain;

use Marktic\CMP\Domain\Consent;
use Marktic\CMP\Domain\Enum\ConsentStatus;
use Marktic\CMP\Domain\Enum\ConsentType;
use Marktic\CMP\Domain\Tenant;
use PHPUnit\Framework\TestCase;

class ConsentTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->tenant = new Tenant('organization', 10);
    }

    public function testCreateConsent(): void
    {
        $consent = Consent::create(
            tenant: $this->tenant,
            sessionId: 'sess-abc',
            userId: 'user-1',
            consentType: ConsentType::ANALYTICS_STORAGE,
            consentStatus: ConsentStatus::GRANTED,
        );

        $this->assertSame('sess-abc', $consent->getSessionId());
        $this->assertSame('user-1', $consent->getUserId());
        $this->assertSame(ConsentType::ANALYTICS_STORAGE, $consent->getConsentType());
        $this->assertSame(ConsentStatus::GRANTED, $consent->getConsentStatus());
        $this->assertTrue($consent->isGranted());
        $this->assertFalse($consent->isDenied());
        $this->assertNotNull($consent->getId());
    }

    public function testCreateConsentWithNullUserId(): void
    {
        $consent = Consent::create(
            tenant: $this->tenant,
            sessionId: 'sess-xyz',
            userId: null,
            consentType: ConsentType::AD_STORAGE,
            consentStatus: ConsentStatus::DENIED,
        );

        $this->assertNull($consent->getUserId());
        $this->assertTrue($consent->isDenied());
        $this->assertFalse($consent->isGranted());
    }

    public function testUpdateConsent(): void
    {
        $consent = Consent::create(
            tenant: $this->tenant,
            sessionId: 'sess-abc',
            userId: null,
            consentType: ConsentType::AD_STORAGE,
            consentStatus: ConsentStatus::DENIED,
        );

        $originalUpdatedAt = $consent->getUpdatedAt();

        // Ensure enough time passes so updatedAt changes
        usleep(1000);

        $consent->update(ConsentStatus::GRANTED);

        $this->assertSame(ConsentStatus::GRANTED, $consent->getConsentStatus());
        $this->assertTrue($consent->isGranted());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $consent->getUpdatedAt());
    }

    public function testGetTenant(): void
    {
        $consent = Consent::create(
            tenant: $this->tenant,
            sessionId: 'sess-abc',
            userId: null,
            consentType: ConsentType::ANALYTICS_STORAGE,
            consentStatus: ConsentStatus::GRANTED,
        );

        $this->assertTrue($this->tenant->equals($consent->getTenant()));
    }
}
