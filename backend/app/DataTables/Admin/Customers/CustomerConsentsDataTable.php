<?php

namespace App\DataTables\Admin\Customers;

use App\Models\UserConsent;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Consent and policy history: which policy version the customer accepted, when, in which app and language.
 */
final class CustomerConsentsDataTable extends CustomerTabDataTable
{
    public const TABLE_ID = 'customer-consents-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(private readonly UserConsentRepositoryInterface $consents)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<UserConsent>
     */
    public function query(): Builder
    {
        return $this->consents->datatableQuery($this->customer());
    }

    protected function columns(): array
    {
        return [
            $this->column('accepted_at', __('admin.customers.consents.accepted_at'), orderable: true),
            $this->column('policy', __('admin.customers.consents.policy')),
            $this->column('version', __('admin.customers.consents.version')),
            $this->column('app', __('admin.customers.devices.app')),
            $this->column('locale', __('admin.customers.devices.locale')),
            $this->column('ip', __('admin.customers.consents.ip')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['accepted_at', 'policy', 'version', 'app', 'locale', 'ip']);
        $this->dateColumn($table, 'accepted_at');
        $table->addColumn('policy', static fn (Model $row): string => $row instanceof UserConsent ? e($row->policyVersion->type->label()) : '');
        $table->addColumn('version', static fn (Model $row): string => $row instanceof UserConsent ? 'v'.($row->policyVersion->version ?? '—') : '');
        $table->editColumn('app', static fn (Model $row): string => $row instanceof UserConsent ? e($row->app->label()) : '');
        $table->editColumn('ip', static fn (Model $row): string => '<span class="ltr-only">'.e((string) ($row->getAttribute('ip') ?? '—')).'</span>');
        $this->rawColumn('ip');
    }
}
