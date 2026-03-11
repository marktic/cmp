<?php

declare(strict_types=1);

namespace Marktic\CMP\Migration;

/**
 * Database schema definitions for Marktic CMP.
 *
 * These SQL statements are database-agnostic DDL templates.
 * Use them as a reference when building framework-specific migrations
 * (Laravel, Symfony Doctrine, etc.).
 *
 * The table prefix defaults to "mkt_cmp_" and is configurable via the
 * static $prefix property.
 */
final class SchemaDefinition
{
    public static string $prefix = 'mkt_cmp_';

    public static function consentsTable(): string
    {
        $prefix = self::$prefix;

        return <<<SQL
        CREATE TABLE IF NOT EXISTS {$prefix}consents (
            id            CHAR(36)     NOT NULL,
            tenant_type   VARCHAR(100) NOT NULL,
            tenant_id     INT UNSIGNED NOT NULL,
            session_id    VARCHAR(255) NOT NULL,
            user_id       VARCHAR(255)     NULL,
            consent_type  VARCHAR(100) NOT NULL,
            -- Matches the problem spec column name; domain model uses ConsentStatus enum
            consent_value VARCHAR(10)  NOT NULL,
            created_at    DATETIME     NOT NULL,
            updated_at    DATETIME     NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY uq_{$prefix}consents_session_type (tenant_type, tenant_id, session_id, consent_type),

            INDEX idx_{$prefix}consents_tenant          (tenant_type, tenant_id),
            INDEX idx_{$prefix}consents_tenant_session  (tenant_type, tenant_id, session_id),
            INDEX idx_{$prefix}consents_tenant_user     (tenant_type, tenant_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
    }

    public static function consentLogsTable(): string
    {
        $prefix = self::$prefix;

        return <<<SQL
        CREATE TABLE IF NOT EXISTS {$prefix}consent_logs (
            id          CHAR(36)     NOT NULL,
            consent_id  CHAR(36)         NULL,
            tenant_type VARCHAR(100) NOT NULL,
            tenant_id   INT UNSIGNED NOT NULL,
            session_id  VARCHAR(255) NOT NULL,
            user_id     VARCHAR(255)     NULL,
            payload     TEXT         NOT NULL,
            source      VARCHAR(20)  NOT NULL,
            ip_address  VARCHAR(45)      NULL,
            user_agent  TEXT             NULL,
            created_at  DATETIME     NOT NULL,

            PRIMARY KEY (id),

            INDEX idx_{$prefix}consent_logs_tenant          (tenant_type, tenant_id),
            INDEX idx_{$prefix}consent_logs_tenant_session  (tenant_type, tenant_id, session_id),
            INDEX idx_{$prefix}consent_logs_tenant_user     (tenant_type, tenant_id, user_id),
            INDEX idx_{$prefix}consent_logs_consent_id      (consent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
    }

    /**
     * Return both DDL statements in order (consents first, then logs).
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::consentsTable(),
            self::consentLogsTable(),
        ];
    }
}
