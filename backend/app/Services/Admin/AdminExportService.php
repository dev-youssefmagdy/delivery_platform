<?php

namespace App\Services\Admin;

use App\DataTables\Admin\AdminDataTable;
use App\Enums\Admin\AdminExportStatus;
use App\Enums\Admin\ExportFormat;
use App\Enums\Common\AuditAction;
use App\Enums\Notification\NotificationEvent;
use App\Exceptions\ForbiddenActionException;
use App\Jobs\Admin\GenerateAdminExport;
use App\Models\AdminExport;
use App\Models\User;
use App\DTOs\Notification\NotificationMessage;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminExportRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Customers\PersonalDataExporter;
use App\Services\Notification\NotificationService;
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
        private readonly PersonalDataExporter $personalData,
        private readonly CustomerRepositoryInterface $customers,
        private readonly NotificationService $notifications,
        private readonly AdminRepositoryInterface $admins,
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
     * Queues the JSON personal-data export of one customer (data-subject access request). The audit entry is written
     * against the customer so it shows in their restrictions/audit tab; the caller checks `customers.export`.
     */
    public function queuePersonalData(User $customer, ?Authenticatable $actor): AdminExport
    {
        $actorRef = ActorReference::of($actor);

        return $this->db->transaction(function () use ($customer, $actor, $actorRef): AdminExport {
            $export = $this->exports->create([
                'uuid' => (string) Str::uuid(),
                'actor_type' => $actorRef->type,
                'actor_id' => $actorRef->id,
                'table' => 'customer_personal_data',
                'format' => ExportFormat::Json,
                'subject_id' => $customer->id,
                'permission' => 'customers.export',
                'filters' => null,
                'status' => AdminExportStatus::Pending,
                'disk' => (string) config('admin.exports.disk', 'local'),
                'filename' => 'customer-'.substr($customer->uuid, 0, 8).'-personal-data-'.now()->format('Ymd-His').'.json',
            ]);

            $this->audit->record(AuditAction::Exported, $customer, $actor, meta: ['export' => $export->uuid, 'format' => ExportFormat::Json->value]);

            GenerateAdminExport::dispatch($export->id)->afterCommit();

            return $export;
        });
    }

    /**
     * Runs inside the queued job: writes the export file (CSV of a listing, or JSON of one customer).
     */
    public function generate(int $exportId): void
    {
        $export = $this->exports->find($exportId);

        if (! $export instanceof AdminExport || $export->status->isFinished()) {
            return;
        }

        $this->exports->update($export, ['status' => AdminExportStatus::Processing]);

        try {
            $path = trim((string) config('admin.exports.directory', 'exports'), '/').'/'.$export->uuid.'.'.$export->format->value;
            $rows = $export->format === ExportFormat::Json
                ? $this->writePersonalData($export, $path)
                : $this->writeCsv($this->resolveTable($export->table)->usingFilters($export->filters ?? []), $export->disk, $path);

            $this->exports->update($export, [
                'status' => AdminExportStatus::Completed,
                'path' => $path,
                'rows' => $rows,
                'completed_at' => now(),
            ]);
            $this->notifyOwner($export, NotificationEvent::AdminExportReady);
        } catch (Throwable $e) {
            Log::error('Admin export failed', ['export' => $export->uuid, 'exception' => $e::class, 'message' => $e->getMessage()]);

            $this->exports->update($export, ['status' => AdminExportStatus::Failed, 'error_code' => 'export_failed', 'completed_at' => now()]);
            $this->notifyOwner($export, NotificationEvent::AdminExportFailed);
        }
    }

    /**
     * The admin who asked for the export finds the result in the notification bell (the download link is signed and
     * built when they open it). The listing's polling toast keeps working for an admin who is still on the page.
     */
    private function notifyOwner(AdminExport $export, NotificationEvent $event): void
    {
        if ($export->actor_type !== 'admin' || $export->actor_id === null) {
            return;
        }

        $admin = $this->admins->find($export->actor_id);

        if ($admin instanceof Admin) {
            $this->notifications->send(NotificationMessage::toAdmin($event, $admin, ['file' => $export->filename], $export->uuid, $event->value.':'.$export->uuid));
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

    private function writePersonalData(AdminExport $export, string $path): int
    {
        $customer = $export->subject_id === null ? null : $this->customers->find($export->subject_id);

        if (! $customer instanceof User || $customer->isAnonymised()) {
            throw new InvalidArgumentException('The customer of this export no longer exists.');
        }

        Storage::disk($export->disk)->put($path, (string) json_encode($this->personalData->build($customer), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return 1;
    }

    private function safeCell(string $value): string
    {
        if ($value === '' || is_numeric($value)) {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
