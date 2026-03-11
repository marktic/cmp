# marktic/cmp — Consent Mode Package

A modern, framework-agnostic PHP 8.3+ Composer package for **server-side Consent Mode management** in multi-tenant SaaS applications.

Designed to record, audit, and query user consent for **Google Consent Mode** categories (analytics, ads, etc.) with a clean feature-based architecture.

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
- [Running Tests](#running-tests)
- [Inspiration](#inspiration)

---

## Features

- ✅ PHP 8.3+ with strict types
- ✅ PSR-4 autoloading, PSR-12 coding standard
- ✅ Feature-based architecture (`Base`, `Consents`, `ConsentLogs`, `Utility`, `Migration`)
- ✅ Framework-agnostic core
- ✅ Multi-tenant support (`tenant_type` / `tenant_id`)
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
| ramsey/uuid | ^4.7   |

---

## Configuration

### Table Prefix

All database tables are prefixed with `mkt_cmp_` by default. To use a custom prefix, set the static property on `SchemaDefinition` before generating your migrations:

```php
use Marktic\CMP\Migration\SchemaDefinition;

SchemaDefinition::$prefix = 'my_app_cmp_';
```

---

## Architecture

The package follows a **feature-based structure** consistent with other packages in the marktic organization. Code is organized by domain feature rather than by architectural layer.

```
src/
├── Base/                              # Shared cross-cutting types
│   └── Tenant.php                     # Tenant value object
│
├── Consents/                          # Feature: Consent records (mkt_cmp_consents)
│   ├── Enums/
│   │   ├── ConsentType.php            # All 7 consent type values
│   │   ├── ConsentStatus.php          # granted | denied
│   │   └── ConsentSource.php          # api | frontend | import | admin
│   ├── Models/
│   │   └── Consent.php                # Consent entity
│   ├── Repository/
│   │   ├── ConsentRepositoryInterface.php
│   │   └── InMemoryConsentRepository.php
│   └── Actions/
│       ├── RecordConsent.php          # Records or updates consent + writes audit log
│       ├── GetConsent.php             # Retrieves a single consent
│       └── GetAllConsentsForSession.php
│
├── ConsentLogs/                       # Feature: Audit log (mkt_cmp_consent_logs)
│   ├── Models/
│   │   └── ConsentLog.php             # Immutable audit log entry
│   └── Repository/
│       ├── ConsentLogRepositoryInterface.php
│       └── InMemoryConsentLogRepository.php
│
├── Migration/
│   └── SchemaDefinition.php           # DDL SQL templates
│
├── Utility/
│   └── ConsentChecker.php             # Convenient query helper
│
└── Http/
    └── Trait/
        └── ConsentApiControllerTrait.php  # Framework bridge for POST /consent
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
| `id` | CHAR(36) UUID | Primary key |
| `tenant_type` | VARCHAR(100) | e.g. `organization`, `project`, `workspace` |
| `tenant_id` | INT UNSIGNED | Numeric tenant identifier |
| `session_id` | VARCHAR(255) | Browser/cookie session identifier |
| `user_id` | VARCHAR(255) nullable | Authenticated user identifier |
| `consent_type` | VARCHAR(100) | One of the 7 consent type values |
| `consent_value` | VARCHAR(10) | `granted` or `denied` |
| `created_at` | DATETIME | Record creation time |
| `updated_at` | DATETIME | Last update time |

**Unique constraint:** `(tenant_type, tenant_id, session_id, consent_type)`

**Indexes:** `(tenant_type, tenant_id)`, `(tenant_type, tenant_id, session_id)`, `(tenant_type, tenant_id, user_id)`

### mkt_cmp_consent_logs

Stores **every** consent change for auditing. Rows are append-only.

| Column | Type | Description |
|--------|------|-------------|
| `id` | CHAR(36) UUID | Primary key |
| `consent_id` | CHAR(36) UUID nullable | Reference to `mkt_cmp_consents.id` |
| `tenant_type` | VARCHAR(100) | Tenant type |
| `tenant_id` | INT UNSIGNED | Tenant ID |
| `session_id` | VARCHAR(255) | Session identifier |
| `user_id` | VARCHAR(255) nullable | User identifier |
| `payload` | TEXT | JSON with `consent_type`, `previous_status`, `new_status` |
| `source` | VARCHAR(20) | `api`, `frontend`, `import`, or `admin` |
| `ip_address` | VARCHAR(45) nullable | Client IP |
| `user_agent` | TEXT nullable | Browser user-agent |
| `created_at` | DATETIME | Log entry creation time |

### Generating DDL SQL

```php
use Marktic\CMP\Migration\SchemaDefinition;

// Get individual SQL statements
$consentsSql  = SchemaDefinition::consentsTable();
$logsSql      = SchemaDefinition::consentLogsTable();

// Or get both at once
foreach (SchemaDefinition::all() as $sql) {
    $pdo->exec($sql);
}
```

---

## Multi-Tenant Model

The package is designed for multi-tenant SaaS applications. Every consent record and log entry is scoped to a **Tenant**, which is a combination of:

- `tenant_type` (string) — the type of tenant entity, e.g. `organization`, `project`, `workspace`
- `tenant_id` (int) — the unique identifier of that tenant entity

```php
use Marktic\CMP\Base\Tenant;

$tenant = new Tenant('organization', 10);  // organization/10
$tenant = new Tenant('project', 44);       // project/44
$tenant = new Tenant('workspace', 3);      // workspace/3
```

Data from one tenant is **never** accessible by another tenant. The repository queries always filter by `(tenant_type, tenant_id)`.

---

## Consent Types & Values

### Types (ConsentType enum)

| Value | Enum Case | Description |
|-------|-----------|-------------|
| `ad_storage` | `ConsentType::AD_STORAGE` | Ad-related storage |
| `analytics_storage` | `ConsentType::ANALYTICS_STORAGE` | Analytics cookies |
| `ad_user_data` | `ConsentType::AD_USER_DATA` | Ad-related user data |
| `ad_personalization` | `ConsentType::AD_PERSONALIZATION` | Ad personalization |
| `functionality_storage` | `ConsentType::FUNCTIONALITY_STORAGE` | Functional cookies |
| `security_storage` | `ConsentType::SECURITY_STORAGE` | Security cookies |
| `personalization_storage` | `ConsentType::PERSONALIZATION_STORAGE` | Personalization cookies |

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

In production, replace `InMemory*` repositories with your framework's database implementations (Doctrine, Eloquent, etc.).

```php
use Marktic\CMP\ConsentLogs\Repository\InMemoryConsentLogRepository;
use Marktic\CMP\Consents\Actions\RecordConsent;
use Marktic\CMP\Consents\Repository\InMemoryConsentRepository;

$consentRepo = new InMemoryConsentRepository();
$logRepo     = new InMemoryConsentLogRepository();
$record      = new RecordConsent($consentRepo, $logRepo);
```

### Recording Consent

```php
use Marktic\CMP\Base\Tenant;
use Marktic\CMP\Consents\Enums\ConsentSource;

$tenant = new Tenant('organization', 10);

$record->execute(
    tenant:    $tenant,
    sessionId: 'sess_abc123',
    userId:    'user_42',          // null for anonymous
    consents: [
        'ad_storage'             => 'granted',
        'analytics_storage'      => 'granted',
        'ad_user_data'           => 'denied',
        'ad_personalization'     => 'denied',
        'functionality_storage'  => 'granted',
        'security_storage'       => 'granted',
        'personalization_storage'=> 'denied',
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
$getConsent = new GetConsent($consentRepo);
$consent = $getConsent->execute($tenant, 'sess_abc123', ConsentType::ANALYTICS_STORAGE);

if ($consent !== null && $consent->isGranted()) {
    // analytics is allowed
}

// Get all consents for a session
$getAll = new GetAllConsentsForSession($consentRepo);
$consents = $getAll->execute($tenant, 'sess_abc123');
```

### Using ConsentChecker

`ConsentChecker` provides a more expressive API for checking permissions in application code.

```php
use Marktic\CMP\Consents\Enums\ConsentType;
use Marktic\CMP\Utility\ConsentChecker;

$checker = new ConsentChecker($consentRepo, $tenant, 'sess_abc123');

// Enum-based check
$checker->isGranted(ConsentType::ANALYTICS_STORAGE); // true / false
$checker->isDenied(ConsentType::AD_STORAGE);         // true / false (false if not recorded)

// String-based check (useful when the type comes from config/request)
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

    // --- Bridge methods ---

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

    protected function resolveUserId(): ?string
    {
        return auth()->id();
    }

    protected function resolveIpAddress(): ?string
    {
        return $this->request->ip();
    }

    protected function resolveUserAgent(): ?string
    {
        return $this->request->userAgent();
    }

    protected function resolveConsents(): array
    {
        return $this->request->input('consent', []);
    }
}
```

### Success response

```json
{
  "status": "ok",
  "message": "Consent recorded successfully."
}
```

### Error response

```json
{
  "status": "error",
  "errors": "Missing or empty X-Tenant-Type header."
}
```

---

## Running Tests

```bash
composer install
composer test
# or directly:
vendor/bin/phpunit
```

Test coverage includes:

- Consent recording (new and update)
- Consent updates with correct state transitions
- Audit log creation on new and changed consent
- Audit log NOT created when consent is unchanged
- Multi-tenant isolation (same session, different tenants)
- Different tenant type isolation
- Session isolation (same tenant, different sessions)
- `ConsentChecker` — `isGranted`, `isDenied`, `hasConsent`, `getAll`
- `Tenant` value object validation

---

## Inspiration

This package was designed with inspiration from the following open-source projects:

- [jostkleigrewe/cookie-consent-bundle](https://github.com/jostkleigrewe/cookie-consent-bundle)
- [wireboard/laravel-cmp](https://github.com/wireboard/laravel-cmp)
- [vallonic/consent-studio-laravel](https://github.com/vallonic/consent-studio-laravel)
- [68publishers/consent-management-platform](https://github.com/68publishers/consent-management-platform)

---

## License

MIT License. See [LICENSE](LICENSE) for details.
