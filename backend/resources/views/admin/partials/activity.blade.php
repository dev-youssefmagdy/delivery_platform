{{-- Audit timeline of one record (lazy-loaded "Activity" tab). $logs: Collection<AdminAuditLog>. --}}
<x-admin.timeline :items="$logs->map(fn (\App\Models\AdminAuditLog $log) => [
    'title' => $log->action->label(),
    'badge' => $log->action,
    'time' => $log->created_at->copy()->setTimezone((string) config('admin.display_timezone'))->translatedFormat('Y-m-d H:i'),
    'actor' => \App\Enums\Common\ActorType::tryFrom($log->actor_type)?->label() ?? ($log->actor_id !== null ? '#'.$log->actor_id : null),
    'description' => $log->after !== null || $log->before !== null
        ? implode(', ', array_keys(($log->after ?? []) + ($log->before ?? [])))
        : null,
    'reason' => $log->reason,
])->all()" />
