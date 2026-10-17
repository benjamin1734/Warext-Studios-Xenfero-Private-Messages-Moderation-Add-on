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