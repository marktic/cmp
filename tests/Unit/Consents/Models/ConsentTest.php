<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Consents\Models;

use Marktic\CMP\Consents\Enums\ConsentSource;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Models\Consent;
use PHPUnit\Framework\TestCase;

class ConsentTest extends TestCase
{
    private function makeConsent(
        string $consentType = 'analytics_storage',
        string $consentValue = 'granted',
        string $tenant = 'organization',
        int $tenantId = 10,
    ): Consent {
        $consent = new Consent();
        $consent->tenant = $tenant;
        $consent->tenant_id = $tenantId;
        $consent->session_id = 'sess-abc';
        $consent->user_id = null;
        $consent->consent_type = $consentType;
        $consent->consent_value = $consentValue;
        return $consent;
    }

    public function testGetConsentType(): void
    {
        $consent = $this->makeConsent(consentType: 'analytics_storage');
        $this->assertSame(ConsentType::ANALYTICS_STORAGE, $consent->getConsentType());
    }

    public function testGetConsentStatus(): void
    {
        $consent = $this->makeConsent(consentValue: 'granted');
        $this->assertSame(ConsentStatus::GRANTED, $consent->getConsentStatus());
    }

    public function testIsGranted(): void
    {
        $granted = $this->makeConsent(consentValue: 'granted');
        $denied = $this->makeConsent(consentValue: 'denied');

        $this->assertTrue($granted->isGranted());
        $this->assertFalse($granted->isDenied());
        $this->assertTrue($denied->isDenied());
        $this->assertFalse($denied->isGranted());
    }

    public function testTenantFields(): void
    {
        $consent = $this->makeConsent(tenant: 'project', tenantId: 42);

        $this->assertSame('project', $consent->tenant);
        $this->assertSame(42, $consent->tenant_id);
    }

    public function testAllConsentTypes(): void
    {
        foreach (ConsentType::cases() as $type) {
            $consent = $this->makeConsent(consentType: $type->value);
            $this->assertSame($type, $consent->getConsentType());
        }
    }

    public function testAllConsentStatuses(): void
    {
        foreach (ConsentStatus::cases() as $status) {
            $consent = $this->makeConsent(consentValue: $status->value);
            $this->assertSame($status, $consent->getConsentStatus());
        }
    }
}
