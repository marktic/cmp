<?php

declare(strict_types=1);

namespace Marktic\CMP\Consents\Models;

use DateTimeImmutable;
use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Enums\ConsentStatus;
use Marktic\CMP\Consents\Enums\ConsentType;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Consent
{
    public function __construct(
        private readonly UuidInterface $id,
        private readonly Tenant $tenant,
        private readonly string $sessionId,
        private readonly ?string $userId,
        private readonly ConsentType $consentType,
        private ConsentStatus $consentStatus,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        Tenant $tenant,
        string $sessionId,
        ?string $userId,
        ConsentType $consentType,
        ConsentStatus $consentStatus,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: Uuid::uuid4(),
            tenant: $tenant,
            sessionId: $sessionId,
            userId: $userId,
            consentType: $consentType,
            consentStatus: $consentStatus,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getConsentType(): ConsentType
    {
        return $this->consentType;
    }

    public function getConsentStatus(): ConsentStatus
    {
        return $this->consentStatus;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(ConsentStatus $newStatus): void
    {
        $this->consentStatus = $newStatus;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isGranted(): bool
    {
        return $this->consentStatus->isGranted();
    }

    public function isDenied(): bool
    {
        return $this->consentStatus->isDenied();
    }
}
