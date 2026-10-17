<?php

namespace Warext\\PrivateModeration\\Cron;

class PurgeExpired
{
    public static function run()
    {
        $cutoff = \\XF::$time - 86400;
        \\XF::db()->delete('xf_warext_pm_access_log', 'expires_date > 0 AND expires_date < ?', $cutoff);
        \\XF::db()->delete('xf_warext_pm_context_request', "status IN ('approved', 'denied') AND reviewed_date > 0 AND reviewed_date < ?", $cutoff);
    }
}