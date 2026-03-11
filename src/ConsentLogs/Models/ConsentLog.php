<?php

declare(strict_types=1);

namespace Marktic\CMP\ConsentLogs\Models;

use DateTimeImmutable;
use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Enums\ConsentSource;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ConsentLog
{
    public function __construct(
        private readonly UuidInterface $id,
        private readonly ?UuidInterface $consentId,
        private readonly Tenant $tenant,
        private readonly string $sessionId,
        private readonly ?string $userId,
        private readonly string $payload,
        private readonly ConsentSource $source,
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        ?UuidInterface $consentId,
        Tenant $tenant,
        string $sessionId,
        ?string $userId,
        string $payload,
        ConsentSource $source,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            id: Uuid::uuid4(),
            consentId: $consentId,
            tenant: $tenant,
            sessionId: $sessionId,
            userId: $userId,
            payload: $payload,
            source: $source,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getConsentId(): ?UuidInterface
    {
        return $this->consentId;
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

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getDecodedPayload(): array
    {
        return json_decode($this->payload, true) ?? [];
    }

    public function getSource(): ConsentSource
    {
        return $this->source;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
