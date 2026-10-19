<?php

namespace App\Services\Admin;

use App\DTOs\Admin\AuditDiffRow;
use App\DTOs\Admin\AuditLogDetails;
use App\Enums\Common\ActorType;
use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\View\Components\Admin\JsonViewer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Read side of the audit log screen: target type options and the before/after comparison of one entry.
 */
final class AuditLogService
{
    public function __construct(private readonly AuditLogRepositoryInterface $logs) {}

    public function details(int $id): AuditLogDetails
    {
        $log = $this->logs->findWithActor($id) ?? throw (new ModelNotFoundException)->setModel(AdminAuditLog::class);

        return new AuditLogDetails($log, $this->diff($log->before, $log->after), $this->actorName($log));
    }

    /**
     * @return array<string, string> alias => alias (select options)
     */
    public function targetTypeOptions(): array
    {
        $types = $this->logs->targetTypes();

        return array_combine($types, $types);
    }

    public function actorName(AdminAuditLog $log): ?string
    {
        if ($log->actor_type === 'admin') {
            return $log->actorAdmin->name ?? ($log->actor_id !== null ? '#'.$log->actor_id : null);
        }

        return ActorType::tryFrom($log->actor_type)?->label();
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @return list<AuditDiffRow>
     */
    private function diff(?array $before, ?array $after): array
    {
        $before ??= [];
        $after ??= [];
        $rows = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $key = (string) $key;
            $old = array_key_exists($key, $before) ? $this->display($key, $before[$key]) : null;
            $new = array_key_exists($key, $after) ? $this->display($key, $after[$key]) : null;

            // Compare the raw values: masked secrets look identical but a change must still be flagged.
            $changed = array_key_exists($key, $before) !== array_key_exists($key, $after) || ($before[$key] ?? null) !== ($after[$key] ?? null);

            $rows[] = new AuditDiffRow($key, $old, $new, $changed);
        }

        return $rows;
    }

    private function display(string $key, mixed $value): string
    {
        foreach (JsonViewer::SENSITIVE as $fragment) {
            if (str_contains(strtolower($key), $fragment)) {
                return '••••••';
            }
        }

        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR),
        };
    }
}
