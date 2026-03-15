<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CmpUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('mkt_cmp_users');

        $table
            ->addColumn('tenant', 'string', ['limit' => 100])
            ->addColumn('tenant_id', 'biginteger', ['signed' => false])
            ->addColumn('session_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('user_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['tenant', 'tenant_id'])
            ->addIndex(['tenant', 'tenant_id', 'session_id'])
            ->addIndex(['tenant', 'tenant_id', 'user_id'])
            ->create();
    }
}
