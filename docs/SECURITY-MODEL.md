# Security model

## Access levels

1. **Reported message**: the reporter's selected conversation message only.
2. **Limited context**: a bounded number of adjacent messages in the same conversation, opened with a reason.
3. **Extended context**: only after a separate senior-review approval and with a stated maximum depth.

## Hard boundaries

- Never expose arbitrary conversations through this addon.
- Never expose a user's other conversations.
- Never copy message body content into addon-owned tables.
- Never grant access solely because a user is an administrator; the feature must check explicit addon permissions.
- Every context request, approval, denial, report view, message-ID list, revocation, and moderation action must be auditable.
- Access must expire when the report is closed or the configured review window ends.

## Initial defaults

- Limited context depth: 5 messages before and after the reported message.
- Extended context: disabled until a senior-review permission is explicitly granted.
- Audit records: contain identifiers and action metadata, not message bodies.
- Audit records: keep message IDs, actor ID, report ID, timestamp, reason, IP and a user-agent hash; message bodies are never copied.
- Retention defaults: audit records 180 days; expired access records one additional day.

These defaults are implementation requirements, not a substitute for the forum's privacy notice, retention policy, or legal review.
