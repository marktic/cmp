<?php

declare(strict_types=1);

namespace Marktic\CMP\Tests\Unit\Consents\Actions;

use InvalidArgumentException;
use Marktic\CMP\Base\Tenant;
use Marktic\CMP\ConsentLogs\Repository\InMemoryConsentLogRepository;
use Marktic\CMP\Consents\Actions\GetAllConsentsForSession;
use Marktic\CMP\Consents\Actions\GetConsent;
use Marktic\CMP\Consents\Actions\RecordConsent;
use Marktic\CMP\Consents\Enums\ConsentSource;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Consents\Repository\InMemoryConsentRepository;
use PHPUnit\Framework\TestCase;

class RecordConsentTest extends TestCase
{
    private RecordConsent $action;
    private InMemoryConsentRepository $consentRepo;
    private InMemoryConsentLogRepository $logRepo;
    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->consentRepo = new InMemoryConsentRepository();
        $this->logRepo = new InMemoryConsentLogRepository();
        $this->action = new RecordConsent($this->consentRepo, $this->logRepo);
        $this->tenant = new Tenant('organization', 10);
    }

    // -------------------------------------------------------------------------
    // Recording consent
    // -------------------------------------------------------------------------

    public function testRecordNewConsent(): void
    {
        $this->action->execute(
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
        $this->action->execute(
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
        $this->action->execute(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'denied'],
        );

        $this->action->execute(
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
        $this->action->execute(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'granted'],
        );

        $logsAfterFirst = $this->logRepo->findAllBySession($this->tenant, 'sess-1');
        $this->assertCount(1, $logsAfterFirst);

        $this->action->execute(
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
        $this->action->execute(
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
        $this->action->execute(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: ['analytics_storage' => 'denied'],
        );

        $this->action->execute(
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

        $this->action->execute($tenantA, 'sess-1', null, ['analytics_storage' => 'granted']);
        $this->action->execute($tenantB, 'sess-1', null, ['analytics_storage' => 'denied']);

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

        $this->action->execute($project, 'sess-1', null, ['ad_storage' => 'granted']);
        $this->action->execute($workspace, 'sess-1', null, ['ad_storage' => 'denied']);

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
        $this->action->execute($this->tenant, 'sess-A', null, ['analytics_storage' => 'granted']);
        $this->action->execute($this->tenant, 'sess-B', null, ['analytics_storage' => 'denied']);

        $consentA = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-A', ConsentType::ANALYTICS_STORAGE);
        $consentB = $this->consentRepo->findBySessionAndType($this->tenant, 'sess-B', ConsentType::ANALYTICS_STORAGE);

        $this->assertTrue($consentA->isGranted());
        $this->assertTrue($consentB->isDenied());
    }

    public function testGetAllConsentsForSession(): void
    {
        $this->action->execute(
            tenant: $this->tenant,
            sessionId: 'sess-1',
            userId: null,
            consents: [
                'ad_storage' => 'granted',
                'analytics_storage' => 'denied',
            ],
        );

        $getAll = new GetAllConsentsForSession($this->consentRepo);
        $all = $getAll->execute($this->tenant, 'sess-1');

        $this->assertCount(2, $all);
    }

    public function testGetConsentAction(): void
    {
        $this->action->execute($this->tenant, 'sess-1', null, ['ad_storage' => 'granted']);

        $getConsent = new GetConsent($this->consentRepo);
        $consent = $getConsent->execute($this->tenant, 'sess-1', ConsentType::AD_STORAGE);

        $this->assertNotNull($consent);
        $this->assertTrue($consent->isGranted());
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testThrowsOnUnknownConsentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown consent type');

        $this->action->execute($this->tenant, 'sess-1', null, ['unknown_type' => 'granted']);
    }

    public function testThrowsOnInvalidConsentStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid consent status');

        $this->action->execute($this->tenant, 'sess-1', null, ['analytics_storage' => 'maybe']);
    }
}
