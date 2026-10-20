<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Provider\VerificationQueueFilterData;
use App\Enums\Settings\SettingKey;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Verification queue: applications that are under review or waiting for more information, oldest first, with the SLA
 * age (the target is the setting `provider_verification_sla_hours`) and who claimed them.
 */
final class VerificationQueueDataTable extends AdminDataTable
{
    public const TABLE_ID = 'verification-queue-table';

    protected bool $globalSearch = false;

    protected array $defaultOrder = [[4, 'asc']];

    public function __construct(
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly SettingsService $settings,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ProviderProfile>
     */
    public function query(): Builder
    {
        $query = $this->providers->verificationQueueQuery(VerificationQueueFilterData::fromRequest($this->filterRequest()));

        // Oldest first unless the table asks for another order (exports and API callers send none).
        return $this->request()->input('order', []) !== [] ? $query : $query->orderBy('submitted_at')->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.providers.fields.name')),
            $this->column('provider_type', __('admin.providers.fields.provider_type'), orderable: true),
            $this->column('status', __('admin.providers.fields.status'), orderable: true),
            $this->column('submitted_at', __('admin.verification.fields.submitted'), orderable: true),
            $this->column('waiting', __('admin.verification.fields.waiting')),
            $this->column('city', __('admin.providers.fields.city')),
            $this->column('assigned', __('admin.verification.fields.assigned')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $sla = $this->settings->int(SettingKey::ProviderVerificationSlaHours);

        $table->only(['id', 'uuid', 'name', 'provider_type', 'status', 'submitted_at', 'waiting', 'city', 'assigned', 'actions']);
        $this->userColumn($table, 'name', static fn (ProviderProfile $provider): array => [ProvidersDataTable::displayName($provider), null]);
        $table->editColumn('provider_type', static fn (Model $row): string => $row instanceof ProviderProfile ? ($row->provider_type?->label() ?? '—') : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'submitted_at');
        $table->addColumn('waiting', static function (Model $row) use ($sla): string {
            if (! $row instanceof ProviderProfile || $row->submitted_at === null) {
                return '—';
            }

            $hours = (int) floor($row->submitted_at->diffInHours(now(), true));
            $tone = $hours >= $sla * 2 ? 'danger' : ($hours >= $sla ? 'warning' : 'neutral');
            $label = $hours >= 48 ? __('admin.verification.days', ['count' => intdiv($hours, 24)]) : __('admin.verification.hours', ['count' => $hours]);

            return Blade::renderComponent(new Badge(label: $label, color: $tone));
        });
        $this->rawColumn('waiting');
        $table->addColumn('city', static fn (Model $row): string => $row instanceof ProviderProfile ? (string) ($row->city->name ?? '—') : '');
        $table->addColumn('assigned', static fn (Model $row): string => $row instanceof ProviderProfile ? ($row->assignedAdmin->name ?? '—') : '');

        $this->actionsColumn($table, static fn (ProviderProfile $provider): array => [
            new TableAction(__('admin.verification.actions.review'), route('admin.verification.review', $provider), icon: 'clipboard', ability: 'view', subject: $provider),
            TableAction::confirm(
                __('admin.verification.actions.claim'),
                route('admin.verification.claim', $provider),
                'claim',
                $provider,
                icon: 'user',
                variant: 'primary',
                confirm: ['title' => __('admin.verification.confirm.claim_title'), 'message' => __('admin.verification.confirm.claim_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
