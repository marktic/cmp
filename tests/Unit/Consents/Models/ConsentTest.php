<?php

declare(strict_types=1);

namespace Marktic\Cmp\Tests\Unit\Consents\Models;

use Marktic\Cmp\Consents\Enums\ConsentStatus;
use Marktic\Cmp\Consents\Enums\ConsentType;
use Marktic\Cmp\Consents\Models\Consent;
use PHPUnit\Framework\TestCase;

class ConsentTest extends TestCase
{
    private function makeConsent(
        string $consentType = 'analytics_storage',
        string $consentValue = 'granted',
        int $userId = 1,
        ?string $context = null,
    ): Consent {
        $consent = new Consent();
        $consent->user_id = $userId;
        $consent->context = $context;
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

    public function testUserIdField(): void
    {
        $consent = $this->makeConsent(userId: 42);

        $this->assertSame(42, $consent->user_id);
    }

    public function testContextField(): void
    {
        $consent = $this->makeConsent(context: 'checkout');
        $this->assertSame('checkout', $consent->context);

        $noContext = $this->makeConsent(context: null);
        $this->assertNull($noContext->context);
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
