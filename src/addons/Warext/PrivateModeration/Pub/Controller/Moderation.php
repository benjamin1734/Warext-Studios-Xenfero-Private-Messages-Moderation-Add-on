<?php

namespace Warext\\PrivateModeration\\Pub\\Controller;

use Warext\\PrivateModeration\\Service\\ContextAccess;
use XF\\Mvc\\ParameterBag;
use XF\\Pub\\Controller\\AbstractController;

class Moderation extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        return $this->actionReport($params);
    }

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
            $depth = (int)$activeAccess['context_depth'];
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
                'activeAccess' => $activeAccess,
                'pendingRequests' => $this->access()->getPendingRequests((int)$report->report_id),
                'canApprove' => $this->access()->canApproveExtended()
            ]
        );
    }

    public function actionContext(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!\\XF::visitor()->hasPermission('forum', 'warextPmOpenContext'))
        {
            throw $this->exception($this->noPermission());
        }
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
        }
        else
        {
            $this->access()->grantLimitedAccess($report, $message, $reason, $depth);
        }

        return $this->redirect($this->buildLink('warext-pm-moderation/reports', $report->report_id));
    }

    public function actionApprove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $report = $this->getReport((int)$params->report_id);
        $requestId = $this->filter('request_id', 'uint');

        if (!$this->access()->canApproveExtended())
        {
            throw $this->exception($this->noPermission());
        }

        $this->access()->approveExtendedRequest($requestId);
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