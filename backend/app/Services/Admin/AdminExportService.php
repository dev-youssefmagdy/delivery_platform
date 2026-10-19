<?php

namespace App\Services\Admin;

use App\DataTables\Admin\AdminDataTable;
use App\Enums\Admin\AdminExportStatus;
use App\Enums\Common\AuditAction;
use App\Exceptions\ForbiddenActionException;
use App\Jobs\Admin\GenerateAdminExport;
use App\Models\AdminExport;
use App\Repositories\Contracts\Admin\AdminExportRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Auth\ActorReference;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Queued CSV exports of admin listings (Plan §5.4). The export reuses the listing's repository query with the
 * same filters, is permission-checked by the caller (route `can:export`), audited when requested, written to
 * the private exports disk and downloaded through a signed, owner-only link.
 */
final class AdminExportService
{
    public function __construct(
        private readonly AdminExportRepositoryInterface $exports,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @param array<string, mixed> $filters raw `filters[...]` shape (FilterData::toArray())
     */
    public function queue(AdminDataTable $table, array $filters, ?Authenticatable $actor): AdminExport
    {
        $actorRef = ActorReference::of($actor);

        return $this->db->transaction(function () use ($table, $filters, $actor, $actorRef): AdminExport {
            $export = $this->exports->create([
                'uuid' => (string) Str::uuid(),
                'actor_type' => $actorRef->type,
                'actor_id' => $actorRef->id,
                'table' => $table::class,
                'permission' => $table->exportPermission()?->value,
                'filters' => $filters,
                'status' => AdminExportStatus::Pending,
                'disk' => (string) config('admin.exports.disk', 'local'),
                'filename' => $table->exportFilename().'.csv',
            ]);

            $this->audit->record(AuditAction::Exported, $export, $actor, after: [
                'table' => class_basename($table),
                'filters' => $filters,
            ]);

            GenerateAdminExport::dispatch($export->id)->afterCommit();

            return $export;
        });
    }

    /**
     * Runs inside the queued job: streams every matching row into a CSV file.
     */
    public function generate(int $exportId): void
    {
        $export = $this->exports->find($exportId);

        if (! $export instanceof AdminExport || $export->status->isFinished()) {
            return;
        }

        $this->exports->update($export, ['status' => AdminExportStatus::Processing]);

        try {
            $table = $this->resolveTable($export->table)->usingFilters($export->filters ?? []);
            $path = trim((string) config('admin.exports.directory', 'exports'), '/').'/'.$export->uuid.'.csv';
            $rows = $this->writeCsv($table, $export->disk, $path);

            $this->exports->update($export, [
                'status' => AdminExportStatus::Completed,
                'path' => $path,
                'rows' => $rows,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Admin export failed', ['export' => $export->uuid, 'exception' => $e::class, 'message' => $e->getMessage()]);

            $this->exports->update($export, ['status' => AdminExportStatus::Failed, 'error_code' => 'export_failed', 'completed_at' => now()]);
        }
    }

    /**
     * Status payload for the polling client; includes a signed download link once completed.
     *
     * @return array{uuid: string, status: string, status_label: string, rows: int, finished: bool, download_url: string|null}
     */
    public function status(AdminExport $export, ?Authenticatable $actor): array
    {
        $this->assertOwner($export, $actor);

        return [
            'uuid' => $export->uuid,
            'status' => $export->status->value,
            'status_label' => $export->status->label(),
            'rows' => $export->rows,
            'finished' => $export->status->isFinished(),
            'download_url' => $export->status === AdminExportStatus::Completed
                ? URL::temporarySignedRoute('admin.exports.download', now()->addMinutes((int) config('admin.exports.link_ttl_minutes', 1440)), ['export' => $export->uuid])
                : null,
        ];
    }

    /**
     * Ensures the export is downloadable by this actor; returns [disk, path, filename].
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function downloadable(AdminExport $export, ?Authenticatable $actor): array
    {
        $this->assertOwner($export, $actor);

        if ($export->status !== AdminExportStatus::Completed || $export->path === null || ! Storage::disk($export->disk)->exists($export->path)) {
            throw ForbiddenActionException::make('export_not_ready');
        }

        return [$export->disk, $export->path, $export->filename];
    }

    private function assertOwner(AdminExport $export, ?Authenticatable $actor): void
    {
        if (! ActorReference::of($actor)->is($export->actor_type, $export->actor_id)) {
            throw ForbiddenActionException::make('forbidden');
        }
    }

    private function resolveTable(string $class): AdminDataTable
    {
        $table = app($class);

        if (! $table instanceof AdminDataTable) {
            throw new InvalidArgumentException("[{$class}] is not an admin DataTable.");
        }

        return $table;
    }

    /**
     * Writes a UTF-8 (BOM) CSV; non-numeric cells starting with = + - @ are prefixed to prevent formula injection.
     */
    private function writeCsv(AdminDataTable $table, string $disk, string $path): int
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new InvalidArgumentException('Unable to open a temporary stream.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, array_map($this->safeCell(...), array_values($table->exportHeadings())), escape: '');

        $rows = 0;

        foreach ($table->query()->lazyById((int) config('admin.exports.chunk', 500)) as $row) {
            /** @var Model $row */
            fputcsv($stream, array_map($this->safeCell(...), $table->exportRow($row)), escape: '');
            $rows++;
        }

        rewind($stream);
        Storage::disk($disk)->put($path, $stream);
        fclose($stream);

        return $rows;
    }

    private function safeCell(string $value): string
    {
        if ($value === '' || is_numeric($value)) {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
