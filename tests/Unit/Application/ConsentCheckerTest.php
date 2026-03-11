<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Application;

use Marktic\CMP\Application\Query\ConsentChecker;
use Marktic\CMP\Application\Service\ConsentService;
use Marktic\CMP\Domain\Enum\ConsentType;
use Marktic\CMP\Domain\Tenant;
use Marktic\CMP\Infrastructure\Repository\InMemoryConsentLogRepository;
use Marktic\CMP\Infrastructure\Repository\InMemoryConsentRepository;
use PHPUnit\Framework\TestCase;

class ConsentCheckerTest extends TestCase
{
    private ConsentService $service;
    private InMemoryConsentRepository $repo;
    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->repo = new InMemoryConsentRepository();
        $this->service = new ConsentService($this->repo, new InMemoryConsentLogRepository());
        $this->tenant = new Tenant('organization', 10);
    }

    private function checker(string $sessionId): ConsentChecker
    {
        return new ConsentChecker($this->repo, $this->tenant, $sessionId);
    }

    // -------------------------------------------------------------------------
    // isGranted / isDenied
    // -------------------------------------------------------------------------

    public function testIsGrantedReturnsTrueWhenGranted(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, ['analytics_storage' => 'granted']);

        $this->assertTrue($this->checker('sess-1')->isGranted(ConsentType::ANALYTICS_STORAGE));
    }

    public function testIsGrantedReturnsFalseWhenDenied(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, ['analytics_storage' => 'denied']);

        $this->assertFalse($this->checker('sess-1')->isGranted(ConsentType::ANALYTICS_STORAGE));
    }

    public function testIsGrantedReturnsFalseWhenNotRecorded(): void
    {
        $this->assertFalse($this->checker('sess-1')->isGranted(ConsentType::AD_STORAGE));
    }

    public function testIsDeniedReturnsTrueWhenDenied(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, ['ad_storage' => 'denied']);

        $this->assertTrue($this->checker('sess-1')->isDenied(ConsentType::AD_STORAGE));
    }

    public function testIsDeniedReturnsFalseWhenGranted(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, ['ad_storage' => 'granted']);

        $this->assertFalse($this->checker('sess-1')->isDenied(ConsentType::AD_STORAGE));
    }

    public function testIsDeniedReturnsFalseWhenNotRecorded(): void
    {
        // Not recorded — isDenied should NOT report it as denied (it is simply unknown)
        $this->assertFalse($this->checker('sess-1')->isDenied(ConsentType::AD_STORAGE));
    }

    // -------------------------------------------------------------------------
    // hasConsent (string-based API)
    // -------------------------------------------------------------------------

    public function testHasConsentByStringGranted(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, ['ad_personalization' => 'granted']);

        $this->assertTrue($this->checker('sess-1')->hasConsent('ad_personalization'));
    }

    public function testHasConsentByStringDenied(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, ['ad_personalization' => 'denied']);

        $this->assertFalse($this->checker('sess-1')->hasConsent('ad_personalization'));
    }

    // -------------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------------

    public function testGetAllReturnsAllRecordedConsents(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-1', null, [
            'analytics_storage' => 'granted',
            'ad_storage' => 'denied',
        ]);

        $all = $this->checker('sess-1')->getAll();

        $this->assertArrayHasKey('analytics_storage', $all);
        $this->assertArrayHasKey('ad_storage', $all);
        $this->assertSame('granted', $all['analytics_storage']);
        $this->assertSame('denied', $all['ad_storage']);
    }

    public function testGetAllReturnsEmptyArrayWhenNothingRecorded(): void
    {
        $this->assertSame([], $this->checker('sess-empty')->getAll());
    }

    // -------------------------------------------------------------------------
    // Session isolation via checker
    // -------------------------------------------------------------------------

    public function testCheckerSessionIsolation(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-A', null, ['analytics_storage' => 'granted']);
        $this->service->recordConsent($this->tenant, 'sess-B', null, ['analytics_storage' => 'denied']);

        $this->assertTrue($this->checker('sess-A')->isGranted(ConsentType::ANALYTICS_STORAGE));
        $this->assertFalse($this->checker('sess-B')->isGranted(ConsentType::ANALYTICS_STORAGE));
    }
}
