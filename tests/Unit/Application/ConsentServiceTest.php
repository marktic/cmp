<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Application;

use InvalidArgumentException;
use Marktic\CMP\Application\Service\ConsentService;
use Marktic\CMP\Domain\Enum\ConsentSource;
use Marktic\CMP\Domain\Enum\ConsentStatus;
use Marktic\CMP\Domain\Enum\ConsentType;
use Marktic\CMP\Domain\Tenant;
use Marktic\CMP\Infrastructure\Repository\InMemoryConsentLogRepository;
use Marktic\CMP\Infrastructure\Repository\InMemoryConsentRepository;
use PHPUnit\Framework\TestCase;

class ConsentServiceTest extends TestCase
{
    private ConsentService $service;
    private InMemoryConsentRepository $consentRepo;
    private InMemoryConsentLogRepository $logRepo;
    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->consentRepo = new InMemoryConsentRepository();
        $this->logRepo = new InMemoryConsentLogRepository();
        $this->service = new ConsentService($this->consentRepo, $this->logRepo);
        $this->tenant = new Tenant('organization', 10);
    }

    // -------------------------------------------------------------------------
    // Recording consent
    // -------------------------------------------------------------------------

    public function testRecordNewConsent(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'granted'],
        );

        $consent = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-1', ConsentType::ANALYTICS_STORAGE);

        $this->assertNotNull($consent);
        $this->assertSame(ConsentStatus::GRANTED, $consent->getConsentStatus());
    }

    public function testRecordMultipleConsentTypes(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: [
                'ad_storage' => 'granted',
                'analytics_storage' => 'denied',
                'ad_user_data' => 'granted',
            ],
        );

        $ad = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-1', ConsentType::AD_STORAGE);
        $analytics = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-1', ConsentType::ANALYTICS_STORAGE);
        $adUserData = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-1', ConsentType::AD_USER_DATA);

        $this->assertNotNull($ad);
        $this->assertNotNull($analytics);
        $this->assertNotNull($adUserData);

        $this->assertTrue($ad->isGranted());
        $this->assertTrue($analytics->isDenied());
        $this->assertTrue($adUserData->isGranted());
    }

    // -------------------------------------------------------------------------
    // Updating consent
    // -------------------------------------------------------------------------

    public function testUpdateExistingConsent(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'denied'],
        );

        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'granted'],
        );

        $consent = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-1', ConsentType::ANALYTICS_STORAGE);

        $this->assertNotNull($consent);
        $this->assertSame(ConsentStatus::GRANTED, $consent->getConsentStatus());
    }

    public function testNoAuditLogWhenConsentUnchanged(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'granted'],
        );

        $logsAfterFirst = $this->logRepo->findAllBySession($this->tenant, 'sess-1');
        $this->assertCount(1, $logsAfterFirst);

        // Record same value again — should not produce another log entry
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'granted'],
        );

        $logsAfterSecond = $this->logRepo->findAllBySession($this->tenant, 'sess-1');
        $this->assertCount(1, $logsAfterSecond);
    }

    // -------------------------------------------------------------------------
    // Audit log creation
    // -------------------------------------------------------------------------

    public function testAuditLogCreatedOnNewConsent(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: 'user-42',
            consents: ['ad_storage' => 'granted'],
            source: ConsentSource::FRONTEND,
            ipAddress: '127.0.0.1',
            userAgent: 'TestAgent/1.0',
        );

        $logs = $this->logRepo->findAllBySession($this->tenant, 'sess-1');

        $this->assertCount(1, $logs);
        $this->assertSame(ConsentSource::FRONTEND, $logs[0]->getSource());
        $this->assertSame('127.0.0.1', $logs[0]->getIpAddress());
        $this->assertSame('TestAgent/1.0', $logs[0]->getUserAgent());
        $this->assertSame('user-42', $logs[0]->getUserId());

        $payload = $logs[0]->getDecodedPayload();
        $this->assertSame('ad_storage', $payload['consent_type']);
        $this->assertNull($payload['previous_status']);
        $this->assertSame('granted', $payload['new_status']);
    }

    public function testAuditLogCreatedOnConsentChange(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'denied'],
        );

        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'granted'],
        );

        $logs = $this->logRepo->findAllBySession($this->tenant, 'sess-1');

        $this->assertCount(2, $logs);

        $secondLog = $logs[1];
        $payload = $secondLog->getDecodedPayload();

        $this->assertSame('analytics_storage', $payload['consent_type']);
        $this->assertSame('denied', $payload['previous_status']);
        $this->assertSame('granted', $payload['new_status']);
    }

    // -------------------------------------------------------------------------
    // Multi-tenant isolation
    // -------------------------------------------------------------------------

    public function testMultiTenantIsolation(): void
    {
        $tenantA = new Tenant('organization', 1);
        $tenantB = new Tenant('organization', 2);

        $this->service->recordConsent($tenantA, 'sess-1', null, ['analytics_storage' => 'granted']);
        $this->service->recordConsent($tenantB, 'sess-1', null, ['analytics_storage' => 'denied']);

        $consentA = $this->consentRepo->findBySessionAndType($tenantA, 'sess-1', ConsentType::ANALYTICS_STORAGE);
        $consentB = $this->consentRepo->findBySessionAndType($tenantB, 'sess-1', ConsentType::ANALYTICS_STORAGE);

        $this->assertNotNull($consentA);
        $this->assertNotNull($consentB);

        $this->assertTrue($consentA->isGranted());
        $this->assertTrue($consentB->isDenied());
    }

    public function testDifferentTenantTypesAreIsolated(): void
    {
        $project = new Tenant('project', 10);
        $workspace = new Tenant('workspace', 10);

        $this->service->recordConsent($project, 'sess-1', null, ['ad_storage' => 'granted']);
        $this->service->recordConsent($workspace, 'sess-1', null, ['ad_storage' => 'denied']);

        $projectConsent = $this->consentRepo->findBySessionAndType($project, 'sess-1', ConsentType::AD_STORAGE);
        $workspaceConsent = $this->consentRepo->findBySessionAndType($workspace, 'sess-1', ConsentType::AD_STORAGE);

        $this->assertTrue($projectConsent->isGranted());
        $this->assertTrue($workspaceConsent->isDenied());
    }

    // -------------------------------------------------------------------------
    // Session isolation
    // -------------------------------------------------------------------------

    public function testSessionIsolation(): void
    {
        $this->service->recordConsent($this->tenant, 'sess-A', null, ['analytics_storage' => 'granted']);
        $this->service->recordConsent($this->tenant, 'sess-B', null, ['analytics_storage' => 'denied']);

        $consentA = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-A', ConsentType::ANALYTICS_STORAGE);
        $consentB = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-B', ConsentType::ANALYTICS_STORAGE);

        $this->assertTrue($consentA->isGranted());
        $this->assertTrue($consentB->isDenied());
    }

    public function testGetAllConsentsForSession(): void
    {
        $this->service->recordConsent(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: [
                'ad_storage' => 'granted',
                'analytics_storage' => 'denied',
            ],
        );

        $all = $this->service->getAllConsentsForSession($this->tenant, 'sess-1');

        $this->assertCount(2, $all);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testThrowsOnUnknownConsentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown consent type');

        $this->service->recordConsent($this->tenant, 'sess-1', null, ['unknown_type' => 'granted']);
    }

    public function testThrowsOnInvalidConsentStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid consent status');

        $this->service->recordConsent($this->tenant, 'sess-1', null, ['analytics_storage' => 'maybe']);
    }
}
