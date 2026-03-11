<?php

declare(strict_types=1);

namespace Marktic\CMP\ConsentLogs\Models;

use Marktic\CMP\Base\Models\HasTenant\HasTenantRecord;
use Marktic\CMP\Consents\Enums\ConsentSource;

trait ConsentLogTrait
{
    use HasTenantRecord;

    public ?int $consent_id;
    public string $session_id;
    public ?string $user_id;
    public string $payload;
    public string $source;
    public ?string $ip_address;
    public ?string $user_agent;
    public string $created_at;

    public function getConsentId(): ?int
    {
        return $this->consent_id !== null ? (int) $this->consent_id : null;
    }

    public function getSessionId(): string
    {
        return $this->session_id;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
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
        return ConsentSource::from($this->source);
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }
}
