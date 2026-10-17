<?php

namespace Warext\PrivateModeration\Service;

use XF\Mvc\Entity\Entity;

class ContextAccess
{
    public const DEFAULT_DEPTH = 5;
    public const MAX_DEPTH = 50;
    public const LIMITED_TTL = 1800;
    public const EXTENDED_TTL = 3600;
    public const ACCESS_RETENTION = 86400;
    public const AUDIT_RETENTION = 180 * 86400;

    public function canReview(): bool
    {
        return \XF::visitor()->hasPermission('forum', 'warextPmViewReported');
    }

    public function canOpenContext(): bool
    {
        return \XF::visitor()->hasPermission('forum', 'warextPmOpenContext');
    }

    public function canRequestExtended(): bool
    {
        return \XF::visitor()->hasPermission('forum', 'warextPmRequestExtended');
    }

    public function canApproveExtended(): bool
    {
        return \XF::visitor()->hasPermission('forum', 'warextPmApproveExtended')
            && \XF::visitor()->hasPermission('general', 'bypassUserPrivacy');
    }

    public function canViewAudit(): bool
    {
        return \XF::visitor()->hasPermission('forum', 'warextPmViewAudit');
    }

    public function isConversationReport(Entity $report): bool
    {
        return $report->content_type === 'conversation_message' && (int) $report->content_id > 0;
    }

    public function getMessage(Entity $report): ?Entity
    {
        if (!$this->isConversationReport($report))
        {
            return null;
        }

        return \XF::em()->find('XF:ConversationMessage', (int) $report->content_id);
    }

    public function getAdjacentMessages(Entity $message, int $depth = self::DEFAULT_DEPTH): array
    {
        $depth = max(1, min($depth, self::MAX_DEPTH));
        $db = \XF::db();
        $conversationId = (int) $message->conversation_id;
        $messageId = (int) $message->message_id;

        $before = $db->fetchAll(
            $db->limit(
                'SELECT * FROM xf_conversation_message
                 WHERE conversation_id = ? AND message_id < ?
                 ORDER BY message_id DESC',
                $depth
            ),
            [$conversationId, $messageId]
        );

        $after = $db->fetchAll(
            $db->limit(
                'SELECT * FROM xf_conversation_message
                 WHERE conversation_id = ? AND message_id > ?
                 ORDER BY message_id ASC',
                $depth
            ),
            [$conversationId, $messageId]
        );

        $rows = array_merge(array_reverse($before), [$message->toArray()], $after);
        return array_map(function (array $row)
        {
            return \XF::em()->hydrateEntity('XF:ConversationMessage', $row);
        }, $rows);
    }

    public function grantLimitedAccess(Entity $report, Entity $message, string $reason, int $depth = self::DEFAULT_DEPTH): int
    {
        $depth = min(max(1, $depth), self::DEFAULT_DEPTH);
        $accessId = $this->grantAccess($report, $message, 'limited_context', $reason, $depth, self::LIMITED_TTL);
        $this->recordAudit('context_granted', $report, $message, [
            'access_log_id' => $accessId,
            'access_type' => 'limited_context',
            'context_depth' => $depth,
            'reason' => $reason
        ]);

        return $depth;
    }

    public function createExtendedRequest(Entity $report, Entity $message, string $reason, int $depth): int
    {
        $depth = max(self::DEFAULT_DEPTH + 1, min($depth, self::MAX_DEPTH));
        $db = \XF::db();
        $userId = (int) \XF::visitor()->user_id;

        $existing = $db->fetchOne(
            'SELECT request_id FROM xf_warext_pm_context_request
             WHERE report_id = ? AND requester_user_id = ? AND status = ?',
            [(int) $report->report_id, $userId, 'pending']
        );
        if ($existing)
        {
            return (int) $existing;
        }

        $db->insert('xf_warext_pm_context_request', [
            'report_id' => (int) $report->report_id,
            'conversation_id' => (int) $message->conversation_id,
            'message_id' => (int) $message->message_id,
            'requester_user_id' => $userId,
            'requested_depth' => $depth,
            'reason' => $reason,
            'status' => 'pending',
            'created_date' => \XF::$time
        ]);

        $requestId = (int) $db->lastInsertId();
        $this->recordAudit('context_requested', $report, $message, [
            'request_id' => $requestId,
            'requested_depth' => $depth,
            'reason' => $reason
        ]);

        return $requestId;
    }

    public function getPendingRequests(int $reportId): array
    {
        return \XF::db()->fetchAll(
            'SELECT * FROM xf_warext_pm_context_request
             WHERE report_id = ? AND status = ?
             ORDER BY created_date ASC',
            [$reportId, 'pending']
        );
    }

    public function approveExtendedRequest(int $requestId, ?int $expectedReportId = null): bool
    {
        if (!$this->canApproveExtended())
        {
            return false;
        }

        $db = \XF::db();
        $request = $db->fetchRow(
            'SELECT * FROM xf_warext_pm_context_request
             WHERE request_id = ? AND status = ?',
            [$requestId, 'pending']
        );

        if (!$request || ($expectedReportId && (int) $request['report_id'] !== $expectedReportId))
        {
            return false;
        }

        $report = \XF::em()->find('XF:Report', (int) $request['report_id']);
        $message = \XF::em()->find('XF:ConversationMessage', (int) $request['message_id']);
        if (!$report || $report->report_state === 'closed' || !$message || !$this->isConversationReport($report))
        {
            return false;
        }

        if ((int) $report->content_id !== (int) $message->message_id
            || (int) $request['conversation_id'] !== (int) $message->conversation_id)
        {
            return false;
        }

        $reviewerId = (int) \XF::visitor()->user_id;
        $expires = \XF::$time + self::EXTENDED_TTL;
        $updated = $db->update(
            'xf_warext_pm_context_request',
            [
                'status' => 'approved',
                'reviewer_user_id' => $reviewerId,
                'reviewed_date' => \XF::$time,
                'expires_date' => $expires
            ],
            'request_id = ? AND status = ?',
            [$requestId, 'pending']
        );

        if (!$updated)
        {
            return false;
        }

        $accessId = $this->grantAccess(
            $report,
            $message,
            'extended_context',
            (string) $request['reason'],
            (int) $request['requested_depth'],
            self::EXTENDED_TTL,
            $reviewerId,
            (int) $request['requester_user_id'],
            $requestId
        );

        $this->recordAudit('request_approved', $report, $message, [
            'request_id' => $requestId,
            'access_log_id' => $accessId,
            'approved_by_user_id' => $reviewerId,
            'requested_depth' => (int) $request['requested_depth']
        ]);

        return true;
    }

    public function denyExtendedRequest(int $requestId, string $note = '', ?int $expectedReportId = null): bool
    {
        if (!$this->canApproveExtended())
        {
            return false;
        }

        $db = \XF::db();
        $request = $db->fetchRow(
            'SELECT * FROM xf_warext_pm_context_request
             WHERE request_id = ? AND status = ?',
            [$requestId, 'pending']
        );
        if (!$request || ($expectedReportId && (int) $request['report_id'] !== $expectedReportId))
        {
            return false;
        }

        $reviewerId = (int) \XF::visitor()->user_id;
        $updated = $db->update(
            'xf_warext_pm_context_request',
            [
                'status' => 'denied',
                'reviewer_user_id' => $reviewerId,
                'reviewer_note' => $note,
                'reviewed_date' => \XF::$time
            ],
            'request_id = ? AND status = ?',
            [$requestId, 'pending']
        );
        if (!$updated)
        {
            return false;
        }

        $this->recordAudit('request_denied', null, null, [
            'report_id' => (int) $request['report_id'],
            'conversation_id' => (int) $request['conversation_id'],
            'message_id' => (int) $request['message_id'],
            'request_id' => $requestId,
            'reviewer_note' => $note
        ]);

        return true;
    }

    public function hasActiveAccess(Entity $report, Entity $message): ?array
    {
        return \XF::db()->fetchRow(
            'SELECT * FROM xf_warext_pm_access_log
             WHERE report_id = ? AND conversation_id = ? AND message_id = ?
               AND viewer_user_id = ? AND expires_date >= ?
             ORDER BY access_log_id DESC',
            [
                (int) $report->report_id,
                (int) $message->conversation_id,
                (int) $message->message_id,
                (int) \XF::visitor()->user_id,
                \XF::$time
            ]
        ) ?: null;
    }

    public function revokeReportAccess(int $reportId, string $reason = 'manual_revoke'): int
    {
        $db = \XF::db();
        $count = $db->update(
            'xf_warext_pm_access_log',
            ['expires_date' => \XF::$time],
            'report_id = ? AND expires_date > ?',
            [$reportId, \XF::$time]
        );
        $this->recordAudit('access_revoked', null, null, [
            'report_id' => $reportId,
            'reason' => $reason,
            'revoked_count' => (int) $count
        ]);

        return (int) $count;
    }

    public function closeReportAccess(int $reportId): void
    {
        $this->revokeReportAccess($reportId, 'report_closed');
        \XF::db()->update(
            'xf_warext_pm_context_request',
            [
                'status' => 'cancelled',
                'reviewed_date' => \XF::$time,
                'reviewer_note' => 'Rapor kapatıldığı için otomatik iptal edildi.'
            ],
            'report_id = ? AND status = ?',
            [$reportId, 'pending']
        );
        $this->recordAudit('report_closed', null, null, ['report_id' => $reportId]);
    }

    public function recordAudit(string $eventType, ?Entity $report, ?Entity $message, array $metadata = []): int
    {
        $reportId = (int) ($metadata['report_id'] ?? ($report ? $report->report_id : 0));
        $messageId = (int) ($metadata['message_id'] ?? ($message ? $message->message_id : 0));
        $conversationId = (int) ($metadata['conversation_id'] ?? ($message ? $message->conversation_id : 0));
        $messageIds = $metadata['message_ids'] ?? ($messageId ? [$messageId] : []);
        $reason = (string) ($metadata['reason'] ?? '');

        unset($metadata['report_id'], $metadata['message_id'], $metadata['conversation_id'], $metadata['message_ids'], $metadata['reason']);

        $db = \XF::db();
        $db->insert('xf_warext_pm_audit_log', [
            'event_type' => $eventType,
            'report_id' => $reportId,
            'request_id' => (int) ($metadata['request_id'] ?? 0),
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'message_ids' => json_encode(array_values(array_unique(array_map('intval', (array) $messageIds))), JSON_UNESCAPED_SLASHES),
            'actor_user_id' => (int) \XF::visitor()->user_id,
            'access_type' => (string) ($metadata['access_type'] ?? ''),
            'context_depth' => (int) ($metadata['context_depth'] ?? 0),
            'reason' => $reason,
            'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'user_agent_hash' => hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
            'created_date' => \XF::$time
        ]);

        return (int) $db->lastInsertId();
    }

    protected function grantAccess(
        Entity $report,
        Entity $message,
        string $accessType,
        string $reason,
        int $depth,
        int $ttl,
        int $approverId = 0,
        int $viewerId = 0,
        int $requestId = 0
    ): int
    {
        $now = \XF::$time;
        $db = \XF::db();

        $db->insert('xf_warext_pm_access_log', [
            'report_id' => (int) $report->report_id,
            'request_id' => $requestId,
            'conversation_id' => (int) $message->conversation_id,
            'message_id' => (int) $message->message_id,
            'viewer_user_id' => $viewerId ?: (int) \XF::visitor()->user_id,
            'access_type' => $accessType,
            'context_depth' => $depth,
            'reason' => $reason,
            'created_date' => $now,
            'expires_date' => $now + $ttl,
            'approved_by_user_id' => $approverId
        ]);

        return (int) $db->lastInsertId();
    }
}
