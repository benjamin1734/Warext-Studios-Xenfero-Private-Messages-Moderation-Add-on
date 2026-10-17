<?php

namespace Warext\PrivateModeration\Cron;

use Warext\PrivateModeration\Service\ContextAccess;

class PurgeExpired
{
    public static function run()
    {
        $now = \XF::$time;
        $db = \XF::db();

        $db->delete(
            'xf_warext_pm_access_log',
            'expires_date > 0 AND expires_date < ?',
            $now - ContextAccess::ACCESS_RETENTION
        );
        $db->delete(
            'xf_warext_pm_context_request',
            "status IN ('approved', 'denied', 'cancelled') AND reviewed_date > 0 AND reviewed_date < ?",
            $now - ContextAccess::AUDIT_RETENTION
        );
        $db->delete(
            'xf_warext_pm_audit_log',
            'created_date < ?',
            $now - ContextAccess::AUDIT_RETENTION
        );
    }
}
