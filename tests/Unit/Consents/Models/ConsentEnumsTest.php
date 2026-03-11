<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Consents\Models;

use Marktic\CMP\Consents\Enums\ConsentSource;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;
use PHPUnit\Framework\TestCase;

class ConsentEnumsTest extends TestCase
{
    // ConsentType

    public function testConsentTypeValues(): void
    {
        $values = ConsentType::values();

        $this->assertContains('ad_storage', $values);
        $this->assertContains('analytics_storage', $values);
        $this->assertContains('ad_user_data', $values);
        $this->assertContains('ad_personalization', $values);
        $this->assertContains('functionality_storage', $values);
        $this->assertContains('security_storage', $values);
        $this->assertContains('personalization_storage', $values);
        $this->assertCount(7, $values);
    }

    public function testConsentTypeFromString(): void
    {
        $this->assertSame(ConsentType::ANALYTICS_STORAGE, ConsentType::from('analytics_storage'));
    }

    public function testConsentTypeLabel(): void
    {
        $this->assertSame('Analytics Storage', ConsentType::ANALYTICS_STORAGE->label());
        $this->assertSame('Ad Storage', ConsentType::AD_STORAGE->label());
    }

    // ConsentStatus

    public function testConsentStatusIsGranted(): void
    {
        $this->assertTrue(ConsentStatus::GRANTED->isGranted());
        $this->assertFalse(ConsentStatus::DENIED->isGranted());
    }

    public function testConsentStatusIsDenied(): void
    {
        $this->assertTrue(ConsentStatus::DENIED->isDenied());
        $this->assertFalse(ConsentStatus::GRANTED->isDenied());
    }

    // ConsentSource

    public function testConsentSourceValues(): void
    {
        $this->assertSame('api', ConsentSource::API->value);
        $this->assertSame('frontend', ConsentSource::FRONTEND->value);
        $this->assertSame('import', ConsentSource::IMPORT->value);
        $this->assertSame('admin', ConsentSource::ADMIN->value);
    }
}
