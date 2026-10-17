<?php

namespace Warext\\PrivateModeration\\Admin\\Controller;

use XF\\Admin\\Controller\\AbstractController;
use XF\\Mvc\\ParameterBag;

class Moderation extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        parent::preDispatchController($action, $params);
        if (!\\XF::visitor()->is_admin)
        {
            throw $this->exception($this->noPermission());
        }
    }

    public function actionIndex()
    {
        $db = \\XF::db();
        $stats = [
            'pending' => (int)$db->fetchOne('SELECT COUNT(*) FROM xf_warext_pm_context_request WHERE status = ?', 'pending'),
            'active' => (int)$db->fetchOne('SELECT COUNT(*) FROM xf_warext_pm_access_log WHERE expires_date >= ?', \\XF::$time),
            'logs' => (int)$db->fetchOne('SELECT COUNT(*) FROM xf_warext_pm_access_log')
        ];

        return $this->view('Warext\\PrivateModeration:Admin\\Dashboard', 'warext_pm_admin_dashboard', compact('stats'));
    }

    public function actionRequests()
    {
        $requests = \\XF::db()->fetchAll(
            'SELECT * FROM xf_warext_pm_context_request ORDER BY created_date DESC LIMIT 100'
        );

        return $this->view('Warext\\PrivateModeration:Admin\\Requests', 'warext_pm_admin_requests', compact('requests'));
    }

    public function actionLogs()
    {
        $logs = \\XF::db()->fetchAll(
            'SELECT * FROM xf_warext_pm_access_log ORDER BY created_date DESC LIMIT 200'
        );

        return $this->view('Warext\\PrivateModeration:Admin\\Logs', 'warext_pm_admin_logs', compact('logs'));
    }

    public function actionApprove()
    {
        $this->assertPostOnly();
        $requestId = $this->filter('request_id', 'uint');
        $db = \\XF::db();
        $request = $db->fetchRow('SELECT * FROM xf_warext_pm_context_request WHERE request_id = ? AND status = ?', [$requestId, 'pending']);
        if ($request)
        {
            $db->insert('xf_warext_pm_access_log', [
                'report_id' => (int)$request['report_id'],
                'request_id' => $requestId,
                'conversation_id' => (int)$request['conversation_id'],
                'message_id' => (int)$request['message_id'],
                'viewer_user_id' => (int)$request['requester_user_id'],
                'access_type' => 'extended_context',
                'context_depth' => (int)$request['requested_depth'],
                'reason' => $request['reason'],
                'created_date' => \\XF::$time,
                'expires_date' => \\XF::$time + 3600,
                'approved_by_user_id' => (int)\\XF::visitor()->user_id
            ]);
            $db->update('xf_warext_pm_context_request', [
                'status' => 'approved',
                'reviewer_user_id' => (int)\\XF::visitor()->user_id,
                'reviewed_date' => \\XF::$time
            ], 'request_id = ? AND status = ?', [$requestId, 'pending']);
        }

        return $this->redirect($this->buildLink('warext-pm-moderation/requests'));
    }

    public function actionDeny()
    {
        $this->assertPostOnly();
        $requestId = $this->filter('request_id', 'uint');
        \\XF::db()->update('xf_warext_pm_context_request', [
            'status' => 'denied',
            'reviewer_user_id' => (int)\\XF::visitor()->user_id,
            'reviewed_date' => \\XF::$time
        ], 'request_id = ? AND status = ?', [$requestId, 'pending']);

        return $this->redirect($this->buildLink('warext-pm-moderation/requests'));
    }

    public function actionRevoke()
    {
        $this->assertPostOnly();
        $reportId = $this->filter('report_id', 'uint');

        \\XF::db()->update(
            'xf_warext_pm_access_log',
            ['expires_date' => \\XF::$time],
            'report_id = ? AND expires_date > ?',
            [$reportId, \\XF::$time]
        );

        return $this->redirect($this->buildLink('warext-pm-moderation'));
    }
}