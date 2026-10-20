<?php

namespace App\DataTables\Admin\Provider;

use App\Enums\Settings\SettingKey;
use App\Models\OwnedDocument;
use App\Models\VehicleDocument;
use App\Services\Settings\SettingsService;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Columns and row actions shared by the provider-documents and vehicle-documents tabs: type, status, validity with an
 * expiry warning, scan result, reviewer, and permission-aware view / download / approve / reject actions. The document
 * number is never shown here (it is encrypted; the review screen reveals it with `drivers.view_pii`).
 */
trait DocumentsTableConcerns
{
    /**
     * @return list<array{0: string, 1: string, 2: bool}> data key, title, orderable
     */
    protected function documentColumnSpecs(): array
    {
        return [
            ['id', '#', true],
            ['type', (string) __('admin.documents.fields.type'), false],
            ['status', (string) __('admin.documents.fields.status'), true],
            ['expires_at', (string) __('admin.documents.fields.expires_at'), true],
            ['scan_status', (string) __('admin.documents.fields.scan'), false],
            ['reviewer', (string) __('admin.documents.fields.reviewed_by'), false],
            ['rejection_reason', (string) __('admin.documents.fields.reason'), false],
            ['created_at', (string) __('admin.documents.fields.uploaded'), true],
        ];
    }

    protected function configureDocumentColumns(EloquentDataTable $table, SettingsService $settings): void
    {
        $warningDays = $settings->int(SettingKey::ProviderDocumentExpiryWarningDays);

        $table->only(['id', 'type', 'status', 'expires_at', 'scan_status', 'reviewer', 'rejection_reason', 'created_at', 'actions']);
        $table->addColumn('type', static fn (Model $row): string => $row instanceof OwnedDocument ? (string) $row->documentType->name : '');
        $this->enumBadgeColumn($table, 'status');
        $table->editColumn('expires_at', static function (Model $row) use ($warningDays): string {
            $expires = $row->getAttribute('expires_at');

            if (! $expires instanceof CarbonInterface) {
                return '—';
            }

            $date = $expires->toDateString();
            $days = (int) CarbonImmutable::today()->diffInDays($expires, false);
            $tone = $days < 0 ? 'danger' : ($days <= $warningDays ? 'warning' : null);

            return $tone === null
                ? '<span class="ltr-only">'.e($date).'</span>'
                : Blade::renderComponent(new Badge(label: $date, color: $tone));
        });
        $this->rawColumn('expires_at');
        $table->editColumn('scan_status', static fn (Model $row): string => $row instanceof OwnedDocument ? Blade::renderComponent(new Badge($row->scan_status)) : '');
        $this->rawColumn('scan_status');
        $table->addColumn('reviewer', static fn (Model $row): string => $row instanceof OwnedDocument ? ($row->reviewer->name ?? '—') : '');
        $table->editColumn('rejection_reason', static fn (Model $row): string => $row instanceof OwnedDocument && $row->rejection_reason !== null ? Str::limit($row->rejection_reason, 120) : '—');
        $this->dateColumn($table, 'created_at');
    }

    /**
     * @return list<TableAction>
     */
    protected function documentActions(OwnedDocument $document, string $tableId): array
    {
        $vehicle = $document instanceof VehicleDocument;
        $prefix = $vehicle ? 'admin.vehicle-documents' : 'admin.provider-documents';

        return [
            TableAction::document(__('admin.documents.actions.view'), route($prefix.'.url', $document), $document),
            TableAction::document(__('admin.documents.actions.download'), route($prefix.'.url', $document), $document, download: true),
            TableAction::confirm(
                __('admin.documents.actions.approve'),
                route($prefix.'.approve', $document),
                'approve',
                $document,
                icon: 'check-circle',
                variant: 'success',
                confirm: ['title' => __('admin.documents.confirm.approve_title'), 'message' => __('admin.documents.confirm.approve_message')],
                reloadTable: $tableId,
            ),
            TableAction::confirm(
                __('admin.documents.actions.reject'),
                route($prefix.'.reject', $document),
                'reject',
                $document,
                icon: 'x-mark',
                reasonRequired: true,
                confirm: ['title' => __('admin.documents.confirm.reject_title'), 'message' => __('admin.documents.confirm.reject_message')],
                reloadTable: $tableId,
            ),
        ];
    }
}
