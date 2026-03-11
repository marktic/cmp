# marktic/cmp — Consent Mode Package

A modern, framework-agnostic PHP 8.3+ Composer package for **server-side Consent Mode management** in multi-tenant SaaS applications.

Designed to record, audit, and query user consent for **Google Consent Mode** categories (analytics, ads, etc.) with a clean feature-based architecture built on the **bytic/orm**, **bytic/actions** and **bytic/migrations** ecosystem.

---

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [Multi-Tenant Model](#multi-tenant-model)
- [Consent Types & Values](#consent-types--values)
- [Usage Examples](#usage-examples)
  - [Recording Consent](#recording-consent)
  - [Querying Consent](#querying-consent)
  - [Using ConsentChecker](#using-consentchecker)
- [API Endpoint (ConsentApiControllerTrait)](#api-endpoint-consentapicontrollertrait)
- [Running Migrations](#running-migrations)
- [Running Tests](#running-tests)

---

## Features

- ✅ PHP 8.3+ with strict types
- ✅ PSR-4 autoloading, PSR-12 coding standard
- ✅ Feature-based architecture (`Base`, `Consents`, `ConsentLogs`, `Utility`, database migrations)
- ✅ Built on **bytic/orm** (`Nip\Records` ORM) for model persistence
- ✅ **bytic/actions** as the base for all action classes
- ✅ **bytic/migrations** (Phinx) for database schema migrations
- ✅ Framework-agnostic core
- ✅ Multi-tenant support (`tenant` type string + `tenant_id`)
- ✅ Session-based and user-based consent tracking
- ✅ Consent state table (`mkt_cmp_consents`)
- ✅ Full audit log table (`mkt_cmp_consent_logs`)
- ✅ All 7 Google Consent Mode v2 categories
- ✅ Reusable `ConsentApiControllerTrait` for HTTP controllers
- ✅ `ConsentChecker` helper for querying permissions
- ✅ PHPUnit test suite

---

## Installation

```bash
composer require marktic/cmp
```

### Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.3    |
| bytic/orm  | ^2.0    |
| bytic/actions | ^1.0 |
| bytic/migrations | ~0.13 |
| bytic/package-base | ^1.0 |

---

## Configuration

Register the service provider in your application bootstrap:

```php
use Marktic\CMP\CmpServiceProvider;

// Laravel / similar
$app->register(CmpServiceProvider::class);
```

### Enabling Migrations

By default, migrations are **not** run automatically. Enable them in your `config/mkt_cmp.php`:

```php
return [
    'database' => [
        'migrations' => true,
        'connection' => env('DB_CONNECTION', 'default'),
    ],
    'tables' => [
        'consents' => 'mkt_cmp_consents',
        'consent_logs' => 'mkt_cmp_consent_logs',
    ],
];
```

---

## Architecture

The package follows a **feature-based structure** consistent with other packages in the marktic organization. Models extend `Nip\Records\Record` (bytic/orm), actions extend `Bytic\Actions\Action`, and migrations are Phinx files.

```
src/
├── Base/                              # Shared cross-cutting types
│   ├── Tenant.php                     # Lightweight tenant DTO
│   └── Models/
│       ├── CmpRecord.php              # Base Record (extends Nip\Records\Record)
│       ├── CmpRecords.php             # Base RecordManager
│       ├── HasTenant/
│       │   ├── HasTenantRecord.php    # Adds $tenant + $tenant_id to records
│       │   └── HasTenantRepository.php # morphTo Tenant relation
│       └── Traits/
│           ├── BaseRepositoryTrait.php
│           └── HasDatabaseConnectionTrait.php
│
├── Consents/                          # Feature: mkt_cmp_consents table
│   ├── Enums/
│   │   ├── ConsentType.php
│   │   ├── ConsentStatus.php
│   │   └── ConsentSource.php
│   ├── Models/
│   │   ├── Consent.php                # ORM Record
│   │   ├── ConsentTrait.php           # Column getters
│   │   ├── Consents.php               # ORM RecordManager
│   │   └── ConsentsTrait.php          # Query methods
│   └── Actions/
│       ├── AbstractAction.php         # Extends Bytic\Actions\Action
│       ├── RecordConsent.php
│       ├── GetConsent.php
│       └── GetAllConsentsForSession.php
│
├── ConsentLogs/                       # Feature: mkt_cmp_consent_logs table
│   ├── Models/
│   │   ├── ConsentLog.php             # ORM Record
│   │   ├── ConsentLogTrait.php
│   │   ├── ConsentLogs.php            # ORM RecordManager
│   │   └── ConsentLogsTrait.php
│   └── Actions/
│       └── AbstractAction.php
│
├── CmpServiceProvider.php             # Registers migrations path
│
├── Utility/
│   ├── CmpModels.php                  # ModelFinder (resolves Consents / ConsentLogs)
│   ├── PackageConfig.php
│   └── ConsentChecker.php
│
└── Http/
    └── Trait/
        └── ConsentApiControllerTrait.php

database/
└── migrations/
    ├── 20260311000001_cmp_consents.php
    └── 20260311000002_cmp_consent_logs.php
```

---

## Database Schema

### Overview

| Table | Purpose |
|-------|---------|
| `mkt_cmp_consents` | Current consent state per session/type |
| `mkt_cmp_consent_logs` | Immutable audit log of all consent changes |

### mkt_cmp_consents

Stores the **current** consent state. One row per tenant + session + consent type.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT auto-increment | Primary key |
| `tenant` | VARCHAR(100) | Morphic type, e.g. `organization`, `project` |
| `tenant_id` | BIGINT UNSIGNED | Numeric tenant identifier |
| `session_id` | VARCHAR(255) | Browser/cookie session identifier |
| `user_id` | VARCHAR(255) nullable | Authenticated user identifier |
| `consent_type` | VARCHAR(100) | One of the 7 consent type values |
| `consent_value` | VARCHAR(10) | `granted` or `denied` |
| `created_at` | TIMESTAMP | Record creation time |
| `updated_at` | TIMESTAMP | Last update time |

**Unique constraint:** `(tenant, tenant_id, session_id, consent_type)`

### mkt_cmp_consent_logs

Stores **every** consent change for auditing. Rows are append-only.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT auto-increment | Primary key |
| `consent_id` | BIGINT nullable | Reference to `mkt_cmp_consents.id` |
| `tenant` | VARCHAR(100) | Morphic tenant type |
| `tenant_id` | BIGINT UNSIGNED | Tenant ID |
| `session_id` | VARCHAR(255) | Session identifier |
| `user_id` | VARCHAR(255) nullable | User identifier |
| `payload` | TEXT | JSON with `consent_type`, `previous_status`, `new_status` |
| `source` | VARCHAR(20) | `api`, `frontend`, `import`, or `admin` |
| `ip_address` | VARCHAR(45) nullable | Client IP |
| `user_agent` | TEXT nullable | Browser user-agent |
| `created_at` | TIMESTAMP | Log entry creation time |

---

## Multi-Tenant Model

The package is designed for multi-tenant SaaS applications. Every consent record and log entry is scoped to a tenant using a morphic pair:

- `tenant` (string) — the morphic type name of the owning entity (e.g. `organization`, `project`)
- `tenant_id` (int) — the numeric ID of the owning entity

```php
// Records scoped to organization/10
$consents->findAllBySession('organization', 10, $sessionId);

// Records scoped to project/44
$consents->findAllBySession('project', 44, $sessionId);
```

The `Base\Tenant` value object is also available as a lightweight DTO to group these two values when passing them through application layers:

```php
use Marktic\CMP\Base\Tenant;

$tenant = new Tenant('organization', 10);
$consents->findAllBySession($tenant->type, $tenant->id, $sessionId);
```

---

## Consent Types & Values

### Types (ConsentType enum)

| Value | Enum Case |
|-------|-----------|
| `ad_storage` | `ConsentType::AD_STORAGE` |
| `analytics_storage` | `ConsentType::ANALYTICS_STORAGE` |
| `ad_user_data` | `ConsentType::AD_USER_DATA` |
| `ad_personalization` | `ConsentType::AD_PERSONALIZATION` |
| `functionality_storage` | `ConsentType::FUNCTIONALITY_STORAGE` |
| `security_storage` | `ConsentType::SECURITY_STORAGE` |
| `personalization_storage` | `ConsentType::PERSONALIZATION_STORAGE` |

### Status (ConsentStatus enum)

| Value | Enum Case |
|-------|-----------|
| `granted` | `ConsentStatus::GRANTED` |
| `denied` | `ConsentStatus::DENIED` |

### Source (ConsentSource enum)

| Value | When to use |
|-------|-------------|
| `api` | Consent received via REST API |
| `frontend` | Consent received directly from frontend |
| `import` | Bulk data import |
| `admin` | Set by an admin user |

---

## Usage Examples

### Setup

Resolve the managers through `CmpModels` or via dependency injection:

```php
use Marktic\CMP\Consents\Actions\RecordConsent;
use Marktic\CMP\Utility\CmpModels;

$consents    = CmpModels::consents();
$consentLogs = CmpModels::consentLogs();
$record      = new RecordConsent();
```

### Recording Consent

```php
use Marktic\CMP\Consents\Enums\ConsentSource;

$record->handle(
    tenant:    'organization',
    tenantId:  10,
    sessionId: 'sess_abc123',
    userId:    'user_42',          // null for anonymous
    consents: [
        'ad_storage'              => 'granted',
        'analytics_storage'       => 'granted',
        'ad_user_data'            => 'denied',
        'ad_personalization'      => 'denied',
        'functionality_storage'   => 'granted',
        'security_storage'        => 'granted',
        'personalization_storage' => 'denied',
    ],
    source:    ConsentSource::FRONTEND,
    ipAddress: '192.0.2.1',
    userAgent: 'Mozilla/5.0 ...',
);
```

When called again with the same session, only **changed** values are updated and logged. Unchanged values are silently ignored.

### Querying Consent

```php
use Marktic\CMP\Consents\Actions\GetConsent;
use Marktic\CMP\Consents\Actions\GetAllConsentsForSession;
use Marktic\CMP\Consents\Enums\ConsentType;

// Get a single consent
$getConsent = new GetConsent();
$consent = $getConsent->handle('organization', 10, 'sess_abc123', ConsentType::ANALYTICS_STORAGE);

if ($consent !== null && $consent->isGranted()) {
    // analytics is allowed
}

// Get all consents for a session
$getAll = new GetAllConsentsForSession();
$consents = $getAll->handle('organization', 10, 'sess_abc123');
```

### Using ConsentChecker

`ConsentChecker` provides a more expressive API for checking permissions in application code.

```php
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Utility\CmpModels;
use Marktic\CMP\Utility\ConsentChecker;

$checker = new ConsentChecker(CmpModels::consents(), 'organization', 10, 'sess_abc123');

// Enum-based check
$checker->isGranted(ConsentType::ANALYTICS_STORAGE); // true / false
$checker->isDenied(ConsentType::AD_STORAGE);         // true / false

// String-based check
$checker->hasConsent('analytics_storage');            // true / false

// Get all recorded consents as a map
$all = $checker->getAll();
// ['analytics_storage' => 'granted', 'ad_storage' => 'denied', ...]
```

---

## API Endpoint (ConsentApiControllerTrait)

The `ConsentApiControllerTrait` provides a ready-to-use `handleConsentUpdate()` method. Mix it into your framework controller and implement the abstract bridge methods.

### Endpoint specification

```
POST /consent
Content-Type: application/json
X-Tenant-Type: organization
X-Tenant-Id:   10

{
  "consent": {
    "ad_storage":              "granted",
    "analytics_storage":       "granted",
    "ad_user_data":            "denied",
    "ad_personalization":      "denied",
    "functionality_storage":   "granted",
    "security_storage":        "granted",
    "personalization_storage": "denied"
  }
}
```

### Framework integration example (pseudo-framework)

```php
use Marktic\CMP\Consents\Actions\RecordConsent;
use Marktic\CMP\Http\Trait\ConsentApiControllerTrait;

class ConsentController
{
    use ConsentApiControllerTrait;

    public function __construct(
        private readonly RecordConsent $recordConsent,
        private readonly Request $request,
    ) {}

    public function update(): JsonResponse
    {
        $result = $this->handleConsentUpdate($this->recordConsent);
        $statusCode = $result['status'] === 'ok' ? 200 : 422;
        return new JsonResponse($result, $statusCode);
    }

    protected function resolveTenantType(): ?string
    {
        return $this->request->header('X-Tenant-Type');
    }

    protected function resolveTenantId(): ?int
    {
        $raw = $this->request->header('X-Tenant-Id');
        return $raw !== null ? (int) $raw : null;
    }

    protected function resolveSessionId(): ?string
    {
        return $this->request->cookie('session_id')
            ?? $this->request->header('X-Session-Id');
    }

    protected function resolveUserId(): ?string { return auth()->id(); }

    protected function resolveIpAddress(): ?string { return $this->request->ip(); }

    protected function resolveUserAgent(): ?string { return $this->request->userAgent(); }

    protected function resolveConsents(): array { return $this->request->input('consent', []); }
}
```

---

## Running Migrations

Migrations are standard Phinx files located in `database/migrations/`. When using a framework that integrates with `CmpServiceProvider`, migrations run automatically if enabled in config.

To run manually via Phinx CLI:

```bash
vendor/bin/phinx migrate --configuration=phinx.php
```

The two tables created are:
- `mkt_cmp_consents` — current consent state
- `mkt_cmp_consent_logs` — audit log

---

## Running Tests

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT License. See [LICENSE](LICENSE) for details.
