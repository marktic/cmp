<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CmpConsentLogs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('mkt_cmp_consent_logs');

        $table
            ->addColumn('consent_id', 'biginteger', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('tenant', 'string', ['limit' => 100])
            ->addColumn('tenant_id', 'biginteger', ['signed' => false])
            ->addColumn('session_id', 'string', ['limit' => 255])
            ->addColumn('user_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('payload', 'text')
            ->addColumn('source', 'string', ['limit' => 20])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'default' => null])
            ->addColumn('user_agent', 'text', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['tenant', 'tenant_id'])
            ->addIndex(['tenant', 'tenant_id', 'session_id'])
            ->addIndex(['tenant', 'tenant_id', 'user_id'])
            ->addIndex(['consent_id'])
            ->create();
    }
}
