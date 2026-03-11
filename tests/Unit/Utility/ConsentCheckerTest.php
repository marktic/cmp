<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Utility;

use Marktic\CMP\Base\Tenant;
use Marktic\CMP\ConsentLogs\Repository\InMemoryConsentLogRepository;
use Marktic\CMP\Consents\Actions\RecordConsent;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Repository\InMemoryConsentRepository;
use Marktic\CMP\Utility\ConsentChecker;
use PHPUnit\Framework\TestCase;

class ConsentCheckerTest extends TestCase
{
    private RecordConsent $action;
    private InMemoryConsentRepository $repo;
    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->repo = new InMemoryConsentRepository();
        $this->action = new RecordConsent($this->repo, new InMemoryConsentLogRepository());
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
        $this->action->execute($this->tenant, 'sess-1', null, ['analytics_storage' => 'granted']);

        $this->assertTrue($this->checker('sess-1')->isGranted(ConsentType::ANALYTICS_STORAGE));
    }

    public function testIsGrantedReturnsFalseWhenDenied(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, ['analytics_storage' => 'denied']);

        $this->assertFalse($this->checker('sess-1')->isGranted(ConsentType::ANALYTICS_STORAGE));
    }

    public function testIsGrantedReturnsFalseWhenNotRecorded(): void
    {
        $this->assertFalse($this->checker('sess-1')->isGranted(ConsentType::AD_STORAGE));
    }

    public function testIsDeniedReturnsTrueWhenDenied(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, ['ad_storage' => 'denied']);

        $this->assertTrue($this->checker('sess-1')->isDenied(ConsentType::AD_STORAGE));
    }

    public function testIsDeniedReturnsFalseWhenGranted(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, ['ad_storage' => 'granted']);

        $this->assertFalse($this->checker('sess-1')->isDenied(ConsentType::AD_STORAGE));
    }

    public function testIsDeniedReturnsFalseWhenNotRecorded(): void
    {
        $this->assertFalse($this->checker('sess-1')->isDenied(ConsentType::AD_STORAGE));
    }

    // -------------------------------------------------------------------------
    // hasConsent (string-based API)
    // -------------------------------------------------------------------------

    public function testHasConsentByStringGranted(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, ['ad_personalization' => 'granted']);

        $this->assertTrue($this->checker('sess-1')->hasConsent('ad_personalization'));
    }

    public function testHasConsentByStringDenied(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, ['ad_personalization' => 'denied']);

        $this->assertFalse($this->checker('sess-1')->hasConsent('ad_personalization'));
    }

    // -------------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------------

    public function testGetAllReturnsAllRecordedConsents(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, [
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
        $this->action->execute($this->tenant, 'sess-A', null, ['analytics_storage' => 'granted']);
        $this->action->execute($this->tenant, 'sess-B', null, ['analytics_storage' => 'denied']);

        $this->assertTrue($this->checker('sess-A')->isGranted(ConsentType::ANALYTICS_STORAGE));
        $this->assertFalse($this->checker('sess-B')->isGranted(ConsentType::ANALYTICS_STORAGE));
    }
}
