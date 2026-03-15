<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CmpConsentLogsUpdate extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('mkt_cmp_consent_logs');

        // Drop indexes that reference columns being removed
        $table
            ->removeIndex(['tenant', 'tenant_id'])
            ->removeIndex(['tenant', 'tenant_id', 'session_id'])
            ->removeIndex(['tenant', 'tenant_id', 'user_id'])
            ->removeIndex(['consent_id'])
            ->save();

        // Remove tenant columns and the old string user_id
        $table
            ->removeColumn('tenant')
            ->removeColumn('tenant_id')
            ->removeColumn('user_id')
            ->save();

        // Rename consent_id to user_id (FK to mkt_cmp_users)
        $table
            ->renameColumn('consent_id', 'user_id')
            ->save();

        // Add index on new user_id FK
        $table
            ->addIndex(['user_id'])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('mkt_cmp_consent_logs');

        $table
            ->removeIndex(['user_id'])
            ->save();

        $table
            ->renameColumn('user_id', 'consent_id')
            ->save();

        $table
            ->addColumn('tenant', 'string', ['limit' => 100, 'after' => 'consent_id'])
            ->addColumn('tenant_id', 'biginteger', ['signed' => false, 'after' => 'tenant'])
            ->addColumn('user_id', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'session_id'])
            ->save();

        $table
            ->addIndex(['tenant', 'tenant_id'])
            ->addIndex(['tenant', 'tenant_id', 'session_id'])
            ->addIndex(['tenant', 'tenant_id', 'user_id'])
            ->addIndex(['consent_id'])
            ->save();
    }
}
