<?php

namespace Warext\PrivateModeration;

use XF\AddOn\AbstractSetup;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    public function installStep1()
    {
        $this->createAccessLog();
        $this->createContextRequest();
        $this->createAuditLog();
    }

    public function upgrade1000020Step1()
    {
        $this->schemaManager()->alterTable('xf_warext_pm_access_log', function (Alter $table)
        {
            $table->addColumn('context_depth', 'smallint')->unsigned()->setDefault(5);
        });
    }

    public function upgrade1000030Step1()
    {
        $this->createAuditLog();
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_warext_pm_audit_log');
        $this->schemaManager()->dropTable('xf_warext_pm_context_request');
        $this->schemaManager()->dropTable('xf_warext_pm_access_log');
    }

    protected function createAccessLog(): void
    {
        $this->schemaManager()->createTable('xf_warext_pm_access_log', function (Create $table)
        {
            $table->addColumn('access_log_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('report_id', 'int')->unsigned();
            $table->addColumn('request_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('conversation_id', 'int')->unsigned();
            $table->addColumn('message_id', 'int')->unsigned();
            $table->addColumn('viewer_user_id', 'int')->unsigned();
            $table->addColumn('access_type', 'varchar', 32);
            $table->addColumn('context_depth', 'smallint')->unsigned()->setDefault(5);
            $table->addColumn('reason', 'text')->nullable();
            $table->addColumn('created_date', 'int')->unsigned();
            $table->addColumn('expires_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('approved_by_user_id', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('access_log_id');
            $table->addKey(['report_id', 'viewer_user_id'], 'report_viewer');
            $table->addKey(['conversation_id', 'message_id'], 'conversation_message');
            $table->addKey('created_date');
        });
    }

    protected function createContextRequest(): void
    {
        $this->schemaManager()->createTable('xf_warext_pm_context_request', function (Create $table)
        {
            $table->addColumn('request_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('report_id', 'int')->unsigned();
            $table->addColumn('conversation_id', 'int')->unsigned();
            $table->addColumn('message_id', 'int')->unsigned();
            $table->addColumn('requester_user_id', 'int')->unsigned();
            $table->addColumn('requested_depth', 'smallint')->unsigned();
            $table->addColumn('reason', 'text');
            $table->addColumn('status', 'varchar', 16)->setDefault('pending');
            $table->addColumn('reviewer_user_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('reviewer_note', 'text')->nullable();
            $table->addColumn('created_date', 'int')->unsigned();
            $table->addColumn('reviewed_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('expires_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('request_id');
            $table->addKey(['report_id', 'status'], 'report_status');
            $table->addKey(['conversation_id', 'message_id'], 'conversation_message');
            $table->addKey('requester_user_id');
        });
    }

    protected function createAuditLog(): void
    {
        $this->schemaManager()->createTable('xf_warext_pm_audit_log', function (Create $table)
        {
            $table->addColumn('audit_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('event_type', 'varchar', 40);
            $table->addColumn('report_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('request_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('conversation_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('message_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('message_ids', 'text')->nullable();
            $table->addColumn('actor_user_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('access_type', 'varchar', 32)->nullable();
            $table->addColumn('context_depth', 'smallint')->unsigned()->setDefault(0);
            $table->addColumn('reason', 'text')->nullable();
            $table->addColumn('metadata', 'text')->nullable();
            $table->addColumn('ip_address', 'varchar', 45)->nullable();
            $table->addColumn('user_agent_hash', 'char', 64)->nullable();
            $table->addColumn('created_date', 'int')->unsigned();
            $table->addPrimaryKey('audit_id');
            $table->addKey(['report_id', 'created_date'], 'report_date');
            $table->addKey(['actor_user_id', 'created_date'], 'actor_date');
            $table->addKey('event_type');
        });
    }
}
