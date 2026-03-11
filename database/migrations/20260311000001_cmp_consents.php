<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CmpConsents extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('mkt_cmp_consents');

        $table
            ->addColumn('tenant', 'string', ['limit' => 100])
            ->addColumn('tenant_id', 'biginteger', ['signed' => false])
            ->addColumn('session_id', 'string', ['limit' => 255])
            ->addColumn('user_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('consent_type', 'string', ['limit' => 100])
            ->addColumn('consent_value', 'string', ['limit' => 10])
            ->addTimestamps()
            ->addIndex(['tenant', 'tenant_id'])
            ->addIndex(['tenant', 'tenant_id', 'session_id'])
            ->addIndex(['tenant', 'tenant_id', 'user_id'])
            ->addIndex(
                ['tenant', 'tenant_id', 'session_id', 'consent_type'],
                ['unique' => true, 'name' => 'uq_mkt_cmp_consents_session_type']
            )
            ->create();
    }
}
