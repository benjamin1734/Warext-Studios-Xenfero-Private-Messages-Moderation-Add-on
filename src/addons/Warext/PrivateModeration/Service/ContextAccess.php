<?php

namespace Warext\\PrivateModeration\\Service;

use XF\\Mvc\\Entity\\Entity;

class ContextAccess
{
    public const DEFAULT_DEPTH = 5;
    public const LIMITED_TTL = 1800;
    public const EXTENDED_TTL = 3600;

    public function canReview(): bool
    {
        return \\XF::visitor()->hasPermission('general', 'warn');
    }

    public function canApproveExtended(): bool
    {
        return \\XF::visitor()->hasPermission('general', 'warn')
            && \\XF::visitor()->hasPermission('general', 'bypassUserPrivacy');
    }

    public function isConversationReport(Entity $report): bool
    {
        return $report->content_type === 'conversation_message' && (int)$report->content_id > 0;
    }

    public function getMessage(Entity $report): ?Entity
    {
        if (!$this->isConversationReport($report))
        {
            return null;
        }

        return \\XF::em()->find('XF:ConversationMessage', (int)$report->content_id);
    }

    public function getAdjacentMessages(Entity $message, int $depth = self::DEFAULT_DEPTH): array
    {
        $depth = max(1, min($depth, 50));
        $db = \\XF::db();
        $conversationId = (int)$message->conversation_id;
        $messageId = (int)$message->message_id;

        $before = $db->fetchAll(
            $db->limit('
                SELECT *
                FROM xf_conversation_message
                WHERE conversation_id = ? AND message_id < ?
                ORDER BY message_id DESC
            ', $depth),
            [$conversationId, $messageId]
        );

        $after = $db->fetchAll(
            $db->limit('
                SELECT *
                FROM xf_conversation_message
                WHERE conversation_id = ? AND message_id > ?
                ORDER BY message_id ASC
            ', $depth),
            [$conversationId, $messageId]
        );

        $rows = array_merge(array_reverse($before), [$message->toArray()], $after);
        return array_map(function(array $row)
        {
            return \\XF::em()->hydrateEntity('XF:ConversationMessage', $row);
        }, $rows);
    }

    public function grantLimitedAccess(Entity $report, Entity $message, string $reason, int $depth = self::DEFAULT_DEPTH): int
    {
        return $this->grantAccess($report, $message, 'limited_context', $reason, $depth, self::LIMITED_TTL);
    }

    public function grantExtendedAccess(Entity $report, Entity $message, string $reason, int $depth, int $approverId): int
    {
        return $this->grantAccess($report, $message, 'extended_context', $reason, $depth, self::EXTENDED_TTL, $approverId);
    }

    protected function grantAccess(
        Entity $report,
        Entity $message,
        string $accessType,
        string $reason,
        int $depth,
        int $ttl,
        int $approverId = 0
    ): int
    {
        $now = \\XF::$time;
        $db = \\XF::db();

        $db->insert('xf_warext_pm_access_log', [
            'report_id' => (int)$report->report_id,
            'request_id' => 0,
            'conversation_id' => (int)$message->conversation_id,
            'message_id' => (int)$message->message_id,
            'viewer_user_id' => (int)\\XF::visitor()->user_id,
            'access_type' => $accessType,
            'reason' => $reason,
            'created_date' => $now,
            'expires_date' => $now + $ttl,
            'approved_by_user_id' => $approverId
        ]);

        return $depth;
    }

    public function createExtendedRequest(Entity $report, Entity $message, string $reason, int $depth): int
    {
        $db = \\XF::db();
        $db->insert('xf_warext_pm_context_request', [
            'report_id' => (int)$report->report_id,
            'conversation_id' => (int)$message->conversation_id,
            'message_id' => (int)$message->message_id,
            'requester_user_id' => (int)\\XF::visitor()->user_id,
            'requested_depth' => max(self::DEFAULT_DEPTH + 1, min($depth, 50)),
            'reason' => $reason,
            'status' => 'pending',
            'created_date' => \\XF::$time
        ]);

        return (int)$db->lastInsertId();
    }

    public function hasActiveAccess(Entity $report, Entity $message): ?array
    {
        return \\XF::db()->fetchRow(
            'SELECT * FROM xf_warext_pm_access_log
             WHERE report_id = ? AND conversation_id = ? AND message_id = ?
               AND viewer_user_id = ? AND expires_date >= ?
             ORDER BY access_log_id DESC',
            [
                (int)$report->report_id,
                (int)$message->conversation_id,
                (int)$message->message_id,
                (int)\\XF::visitor()->user_id,
                \\XF::$time
            ]
        ) ?: null;
    }

    public function closeReportAccess(int $reportId): void
    {
        \\XF::db()->update(
            'xf_warext_pm_access_log',
            ['expires_date' => \\XF::$time],
            'report_id = ? AND expires_date > ?',
            [$reportId, \\XF::$time]
        );
    }
}