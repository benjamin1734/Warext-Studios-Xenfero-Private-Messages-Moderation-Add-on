<?php

namespace Warext\\PrivateModeration\\XF\\Entity;

class Report extends XFCP_Report
{
    protected function _postSave()
    {
        parent::_postSave();

        if ($this->isChanged('report_state') && $this->report_state === 'closed')
        {
            \\XF::db()->update(
                'xf_warext_pm_access_log',
                ['expires_date' => \\XF::$time],
                'report_id = ? AND expires_date > ?',
                [(int)$this->report_id, \\XF::$time]
            );
        }
    }
}