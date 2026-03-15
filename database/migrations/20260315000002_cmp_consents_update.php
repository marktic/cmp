<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CmpConsentsUpdate extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('mkt_cmp_consents');

        // Drop indexes that reference columns being removed
        $table
            ->removeIndex(['tenant', 'tenant_id'])
            ->removeIndex(['tenant', 'tenant_id', 'session_id'])
            ->removeIndex(['tenant', 'tenant_id', 'user_id'])
            ->removeIndexByName('uq_mkt_cmp_consents_session_type')
            ->save();

        // Remove tenant/session columns; user_id will be repurposed as integer FK
        $table
            ->removeColumn('tenant')
            ->removeColumn('tenant_id')
            ->removeColumn('session_id')
            ->save();

        // Change user_id from string to biginteger FK referencing mkt_cmp_users
        $table
            ->changeColumn('user_id', 'biginteger', ['signed' => false, 'null' => true, 'default' => null])
            ->save();

        // Add nullable context column
        $table
            ->addColumn('context', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'user_id'])
            ->save();

        // Add new unique constraint and index
        $table
            ->addIndex(['user_id'])
            ->addIndex(
                ['user_id', 'consent_type'],
                ['unique' => true, 'name' => 'uq_mkt_cmp_consents_user_type']
            )
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('mkt_cmp_consents');

        $table
            ->removeIndex(['user_id'])
            ->removeIndexByName('uq_mkt_cmp_consents_user_type')
            ->save();

        $table
            ->removeColumn('context')
            ->save();

        $table
            ->changeColumn('user_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->save();

        $table
            ->addColumn('tenant', 'string', ['limit' => 100, 'after' => 'id'])
            ->addColumn('tenant_id', 'biginteger', ['signed' => false, 'after' => 'tenant'])
            ->addColumn('session_id', 'string', ['limit' => 255, 'after' => 'tenant_id'])
            ->save();

        $table
            ->addIndex(['tenant', 'tenant_id'])
            ->addIndex(['tenant', 'tenant_id', 'session_id'])
            ->addIndex(['tenant', 'tenant_id', 'user_id'])
            ->addIndex(
                ['tenant', 'tenant_id', 'session_id', 'consent_type'],
                ['unique' => true, 'name' => 'uq_mkt_cmp_consents_session_type']
            )
            ->save();
    }
}
