<?php

namespace App\DataTables\Admin\Customers;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Customers\CustomerFilterData;
use App\Enums\Admin\AdminPermission;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Customers listing (filters: uuid/phone/name, status, registered range, city, deletion requested). The global
 * search box is off (the filters card has the search field); phones are always masked, also in exports.
 */
final class CustomersDataTable extends AdminDataTable
{
    public const TABLE_ID = 'customers-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly CustomerRepositoryInterface $customers)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return $this->customers->datatableQuery(CustomerFilterData::fromRequest($this->filterRequest()));
    }

    public function exportPermission(): AdminPermission
    {
        return AdminPermission::CustomersExport;
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.customers.fields.name'), orderable: true),
            $this->column('status', __('admin.customers.fields.status'), orderable: true),
            $this->column('has_pending_deletion', __('admin.customers.fields.deletion_requested')),
            $this->column('last_seen_at', __('admin.customers.fields.last_seen'), orderable: true),
            $this->column('created_at', __('admin.customers.fields.registered'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        // Allowlist of output keys: the model also carries the full phone, email and photo path, which never leave the server here.
        $table->only(['id', 'uuid', 'name', 'status', 'has_pending_deletion', 'last_seen_at', 'created_at', 'actions']);
        $this->userColumn($table, 'name', fn (User $customer): array => [self::displayName($customer), $customer->phone_e164 === null ? null : PhoneNumber::mask($customer->phone_e164)]);
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'last_seen_at');
        $this->dateColumn($table, 'created_at');

        $table->editColumn('has_pending_deletion', static fn (Model $row): string => $row->getAttribute('has_pending_deletion')
            ? Blade::renderComponent(new Badge(label: __('admin.common.yes'), color: 'warning'))
            : '—');
        $this->rawColumn('has_pending_deletion');

        $this->actionsColumn($table, fn (User $customer): array => [
            TableAction::view(route('admin.customers.show', $customer), $customer),
            TableAction::confirm(
                __('admin.customers.actions.reactivate'),
                route('admin.customers.reactivate', $customer),
                'reactivate',
                $customer,
                icon: 'play',
                variant: 'success',
                reasonRequired: true,
                confirm: ['title' => __('admin.customers.confirm.reactivate_title'), 'message' => __('admin.customers.confirm.reactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }

    /**
     * Customers are exported without contact data: a masked phone only.
     *
     * @return array<string, string>
     */
    public function exportHeadings(): array
    {
        return [
            'uuid' => __('admin.customers.fields.uuid'),
            'name' => __('admin.customers.fields.name'),
            'phone' => __('admin.customers.fields.phone_masked'),
            'status' => __('admin.customers.fields.status'),
            'created_at' => __('admin.customers.fields.registered'),
            'last_seen_at' => __('admin.customers.fields.last_seen'),
        ];
    }

    public function exportRow(Model $row): array
    {
        if (! $row instanceof User) {
            return [];
        }

        return [
            $row->uuid,
            self::displayName($row),
            $row->phone_e164 === null ? '' : PhoneNumber::mask($row->phone_e164),
            $row->status->label(),
            $this->stringify($row->created_at),
            $this->stringify($row->last_seen_at),
        ];
    }

    public static function displayName(User $customer): string
    {
        return $customer->name ?? ($customer->isAnonymised() ? __('admin.customers.anonymised_name') : __('admin.customers.unnamed'));
    }
}
