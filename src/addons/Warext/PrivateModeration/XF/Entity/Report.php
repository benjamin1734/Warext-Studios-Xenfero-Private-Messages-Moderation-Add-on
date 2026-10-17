<?php

namespace Warext\PrivateModeration\XF\Entity;

use Warext\PrivateModeration\Service\ContextAccess;

class Report extends XFCP_Report
{
    protected function _postSave()
    {
        parent::_postSave();

        if ($this->isChanged('report_state') && $this->report_state === 'closed')
        {
            (new ContextAccess())->closeReportAccess((int) $this->report_id);
        }
    }
}
