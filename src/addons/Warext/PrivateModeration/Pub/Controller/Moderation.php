<?php

namespace Warext\\PrivateModeration\\Pub\\Controller;

use Warext\\PrivateModeration\\Service\\ContextAccess;
use XF\\Mvc\\ParameterBag;
use XF\\Pub\\Controller\\AbstractController;

class Moderation extends AbstractController
{
    protected function access(): ContextAccess
    {
        return new ContextAccess();
    }

    protected function assertReviewer(): void
    {
        if (!$this->access()->canReview())
        {
            throw $this->exception($this->noPermission());
        }
    }

    protected function getReport(int $reportId)
    {
        $report = $this->em()->find('XF:Report', $reportId);
        if (!$report || $report->report_state === 'closed')
        {
            throw $this->exception($this->notFound());
        }

        $this->assertReviewer();

        if (!$this->access()->isConversationReport($report))
        {
            throw $this->exception($this->notFound());
        }

        return $report;
    }

    public function actionReport(ParameterBag $params)
    {
        $report = $this->getReport((int)$params->report_id);
        $message = $this->access()->getMessage($report);

        if (!$message)
        {
            throw $this->exception($this->notFound());
        }

        $activeAccess = $this->access()->hasActiveAccess($report, $message);
        $context = [];
        if ($activeAccess)
        {
            $depth = ($activeAccess['access_type'] === 'extended_context') ? 50 : ContextAccess::DEFAULT_DEPTH;
            $context = $this->access()->getAdjacentMessages($message, $depth);
        }

        return $this->view(
            'Warext\\PrivateModeration:Report',
            'warext_pm_moderation_report',
            [
                'report' => $report,
                'message' => $message,
                'messageHtml' => $this->renderMessage($message),
                'context' => $context,
                'activeAccess' => $activeAccess
            ]
        );
    }

    public function actionContext(ParameterBag $params)
    {
        $this->assertPostOnly();
        $report = $this->getReport((int)$params->report_id);
        $message = $this->access()->getMessage($report);
        $reason = trim($this->filter('reason', 'str'));
        $depth = max(1, min($this->filter('depth', 'uint') ?: ContextAccess::DEFAULT_DEPTH, 50));

        if (utf8_strlen($reason) < 10)
        {
            return $this->error('Bağlam erişimi için en az 10 karakterlik gerekçe yazmalısınız.');
        }

        if ($depth > ContextAccess::DEFAULT_DEPTH)
        {
            $this->access()->createExtendedRequest($report, $message, $reason, $depth);
            return $this->redirect($this->buildLink('warext-pm-moderation/reports', $report->report_id));
        }

        $this->access()->grantLimitedAccess($report, $message, $reason, $depth);
        return $this->redirect($this->buildLink('warext-pm-moderation/reports', $report->report_id));
    }

    protected function renderMessage($message): string
    {
        return \\XF::app()->bbCode()->render(
            $message->message,
            'html',
            'conversation_message',
            $message
        );
    }
}