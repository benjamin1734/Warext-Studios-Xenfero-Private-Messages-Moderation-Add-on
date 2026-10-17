<?php

namespace Warext\PrivateModeration\Admin\Controller;

use Warext\PrivateModeration\Service\ContextAccess;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Moderation extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        parent::preDispatchController($action, $params);
        if (!\XF::visitor()->is_admin)
        {
            throw $this->exception($this->noPermission());
        }
    }

    protected function access(): ContextAccess
    {
        return new ContextAccess();
    }

    protected function assertAuditPermission(): void
    {
        if (!$this->access()->canViewAudit())
        {
            throw $this->exception($this->noPermission());
        }
    }

    protected function assertApprovalPermission(): void
    {
        if (!$this->access()->canApproveExtended())
        {
            throw $this->exception($this->noPermission());
        }
    }

    public function actionIndex()
    {
        $this->assertAuditPermission();
        $db = \XF::db();
        $stats = [
            'pending' => (int) $db->fetchOne('SELECT COUNT(*) FROM xf_warext_pm_context_request WHERE status = ?', 'pending'),
            'active' => (int) $db->fetchOne('SELECT COUNT(*) FROM xf_warext_pm_access_log WHERE expires_date >= ?', \XF::$time),
            'logs' => (int) $db->fetchOne('SELECT COUNT(*) FROM xf_warext_pm_audit_log')
        ];

        return $this->view('Warext\PrivateModeration:Admin\Dashboard', 'warext_pm_admin_dashboard', compact('stats'));
    }

    public function actionRequests()
    {
        $this->assertApprovalPermission();
        $requests = \XF::db()->fetchAll(
            'SELECT * FROM xf_warext_pm_context_request ORDER BY created_date DESC LIMIT 100'
        );

        return $this->view('Warext\PrivateModeration:Admin\Requests', 'warext_pm_admin_requests', compact('requests'));
    }

    public function actionLogs()
    {
        $this->assertAuditPermission();
        $page = max(1, (int) $this->filter('page', 'uint'));
        $perPage = 100;
        $offset = ($page - 1) * $perPage;
        $reportId = (int) $this->filter('report_id', 'uint');
        $where = $reportId ? ' WHERE report_id = ' . $reportId : '';
        $logs = \XF::db()->fetchAll(
            'SELECT * FROM xf_warext_pm_audit_log' . $where . ' ORDER BY created_date DESC LIMIT ' . $offset . ', ' . $perPage
        );

        return $this->view('Warext\PrivateModeration:Admin\Logs', 'warext_pm_admin_logs', compact('logs', 'page', 'reportId'));
    }

    public function actionApprove()
    {
        $this->assertPostOnly();
        $this->assertApprovalPermission();
        $requestId = (int) $this->filter('request_id', 'uint');
        $this->access()->approveExtendedRequest($requestId);

        return $this->redirect($this->buildLink('warext-pm-moderation/requests'));
    }

    public function actionDeny()
    {
        $this->assertPostOnly();
        $this->assertApprovalPermission();
        $requestId = (int) $this->filter('request_id', 'uint');
        $note = trim((string) $this->filter('note', 'str'));
        $this->access()->denyExtendedRequest($requestId, $note);

        return $this->redirect($this->buildLink('warext-pm-moderation/requests'));
    }

    public function actionRevoke()
    {
        $this->assertPostOnly();
        $this->assertApprovalPermission();
        $reportId = (int) $this->filter('report_id', 'uint');
        $this->access()->revokeReportAccess($reportId, 'admin_revoke');

        return $this->redirect($this->buildLink('warext-pm-moderation'));
    }
}
