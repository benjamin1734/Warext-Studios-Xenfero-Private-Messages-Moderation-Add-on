<?php

namespace Warext\PrivateModeration\Pub\Controller;

use Warext\PrivateModeration\Service\ContextAccess;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

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
        $report = $this->getReport((int) $params->report_id);
        $message = $this->access()->getMessage($report);

        if (!$message)
        {
            throw $this->exception($this->notFound());
        }

        $activeAccess = $this->access()->hasActiveAccess($report, $message);
        $context = [];
        if ($activeAccess)
        {
            $context = $this->access()->getAdjacentMessages($message, (int) $activeAccess['context_depth']);
        }

        $messageIds = array_map(static function ($item): int
        {
            return (int) $item->message_id;
        }, $context ?: [$message]);
        $this->access()->recordAudit('report_viewed', $report, $message, [
            'message_ids' => $messageIds,
            'access_type' => $activeAccess ? (string) $activeAccess['access_type'] : 'reported_message',
            'context_depth' => $activeAccess ? (int) $activeAccess['context_depth'] : 0
        ]);

        return $this->view(
            'Warext\PrivateModeration:Report',
            'warext_pm_moderation_report',
            [
                'report' => $report,
                'message' => $message,
                'messageHtml' => $this->renderMessage($message),
                'context' => $context,
                'activeAccess' => $activeAccess,
                'pendingRequests' => $this->access()->getPendingRequests((int) $report->report_id),
                'canApprove' => $this->access()->canApproveExtended(),
                'canClose' => $report->report_state !== 'closed',
                'canOpenContext' => $this->access()->canOpenContext(),
                'canRequestExtended' => $this->access()->canRequestExtended(),
                'canModerateMessage' => $this->access()->canModerateMessage()
            ]
        );
    }

    public function actionContext(ParameterBag $params)
    {
        $this->assertPostOnly();
        $report = $this->getReport((int) $params->report_id);
        $message = $this->access()->getMessage($report);
        $reason = trim((string) $this->filter('reason', 'str'));
        $depth = max(1, min((int) ($this->filter('depth', 'uint') ?: ContextAccess::DEFAULT_DEPTH), ContextAccess::MAX_DEPTH));

        if (!$message || utf8_strlen($reason) < 10)
        {
            return $this->error('Bağlam erişimi için en az 10 karakterlik gerekçe yazmalısınız.');
        }

        if ($depth <= ContextAccess::DEFAULT_DEPTH)
        {
            if (!$this->access()->canOpenContext())
            {
                throw $this->exception($this->noPermission());
            }
            $this->access()->grantLimitedAccess($report, $message, $reason, $depth);
        }
        else
        {
            if (!$this->access()->canRequestExtended())
            {
                throw $this->exception($this->noPermission());
            }
            $this->access()->createExtendedRequest($report, $message, $reason, $depth);
        }

        return $this->redirect($this->buildLink('warext-pm-moderation/reports', $report));
    }

    public function actionApprove(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!$this->access()->canApproveExtended())
        {
            throw $this->exception($this->noPermission());
        }

        $report = $this->getReport((int) $params->report_id);
        $requestId = (int) $this->filter('request_id', 'uint');
        if (!$this->access()->approveExtendedRequest($requestId, (int) $report->report_id))
        {
            return $this->error('Bağlam talebi onaylanamadı veya artık geçerli değil.');
        }

        return $this->redirect($this->buildLink('warext-pm-moderation/reports', $report));
    }

    public function actionDeny(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!$this->access()->canApproveExtended())
        {
            throw $this->exception($this->noPermission());
        }

        $report = $this->getReport((int) $params->report_id);
        $requestId = (int) $this->filter('request_id', 'uint');
        $note = trim((string) $this->filter('note', 'str'));
        if (!$this->access()->denyExtendedRequest($requestId, $note, (int) $report->report_id))
        {
            return $this->error('Bağlam talebi reddedilemedi veya artık geçerli değil.');
        }

        return $this->redirect($this->buildLink('warext-pm-moderation/reports', $report));
    }

    public function actionClose(ParameterBag $params)
    {
        $this->assertPostOnly();
        $report = $this->getReport((int) $params->report_id);
        $report->report_state = 'closed';
        $report->save();
        $this->access()->recordAudit('moderation_action', $report, $this->access()->getMessage($report), [
            'action' => 'close_report',
            'reason' => trim((string) $this->filter('reason', 'str'))
        ]);

        return $this->redirect($this->buildLink('reports'));
    }

    public function actionDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $report = $this->getReport((int) $params->report_id);
        if (!$this->access()->canModerateMessage())
        {
            throw $this->exception($this->noPermission());
        }

        $message = $this->access()->getMessage($report);
        $reason = trim((string) $this->filter('reason', 'str'));
        if (!$message || utf8_strlen($reason) < 10)
        {
            return $this->error('Mesaj işlemi için en az 10 karakterlik gerekçe yazmalısınız.');
        }

        if (!$this->access()->deleteReportedMessage($report, $message, $reason))
        {
            return $this->error('Mesaj silinemedi. XenForo içerik silme yetkisini ve rapor durumunu kontrol edin.');
        }

        return $this->redirect($this->buildLink('reports'));
    }

    protected function renderMessage($message): string
    {
        return \XF::app()->bbCode()->render(
            $message->message,
            'html',
            'conversation_message',
            $message
        );
    }
}

