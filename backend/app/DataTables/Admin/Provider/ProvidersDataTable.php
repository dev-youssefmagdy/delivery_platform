<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Provider\ProviderFilterData;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Providers listing (Appendix C filters: uuid/phone/name, status, provider type, service category, city, online now,
 * document expiring within N days). The global search box is off (the filters card has the search field); phones are
 * always masked and the output is an explicit allowlist.
 */
final class ProvidersDataTable extends AdminDataTable
{
    public const TABLE_ID = 'providers-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly ProviderProfileRepositoryInterface $providers)
    {
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
        return $this->providers->datatableQuery(ProviderFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.providers.fields.name')),
            $this->column('provider_type', __('admin.providers.fields.provider_type'), orderable: true),
            $this->column('status', __('admin.providers.fields.status'), orderable: true),
            $this->column('city', __('admin.providers.fields.city')),
            $this->column('vehicles_count', __('admin.providers.fields.vehicles')),
            $this->column('is_online', __('admin.providers.fields.online'), orderable: true),
            $this->column('completed_jobs', __('admin.providers.fields.completed_jobs'), orderable: true),
            $this->column('created_at', __('admin.providers.fields.applied'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'uuid', 'name', 'provider_type', 'status', 'city', 'vehicles_count', 'is_online', 'completed_jobs', 'created_at', 'actions']);
        $this->userColumn($table, 'name', static fn (ProviderProfile $provider): array => [self::displayName($provider), $provider->user->phone_e164 === null ? null : PhoneNumber::mask($provider->user->phone_e164)]);
        $table->editColumn('provider_type', static fn (Model $row): string => $row instanceof ProviderProfile ? ($row->provider_type?->label() ?? '—') : '');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('city', static fn (Model $row): string => $row instanceof ProviderProfile ? (string) ($row->city->name ?? '—') : '');
        $table->editColumn('is_online', static fn (Model $row): string => Blade::renderComponent(new Badge(
            label: $row->getAttribute('is_online') ? __('admin.providers.online') : __('admin.providers.offline'),
            color: $row->getAttribute('is_online') ? 'success' : 'neutral',
        )));
        $this->rawColumn('is_online');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (ProviderProfile $provider): array => [
            TableAction::view(route('admin.providers.show', $provider), $provider),
        ]);
    }

    public static function displayName(ProviderProfile $provider): string
    {
        $user = $provider->user;

        return $user->name ?? ($user->isAnonymised() ? __('admin.providers.anonymised_name') : __('admin.providers.unnamed'));
    }
}
