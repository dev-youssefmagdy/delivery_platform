<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Finance\Concerns\FormatsLedgerMoney;
use App\DTOs\Finance\LedgerAccountFilterData;
use App\Enums\Finance\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Ledger accounts listing (filters: type, currency, negative only, balance range). Read-only; balances are the cached
 * ones that `ledger:reconcile` verifies.
 */
final class LedgerAccountsDataTable extends AdminDataTable
{
    use FormatsLedgerMoney;

    public const TABLE_ID = 'ledger-accounts-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly LedgerAccountRepositoryInterface $accounts)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<LedgerAccount>
     */
    public function query(): Builder
    {
        return $this->accounts->datatableQuery(LedgerAccountFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('type', __('admin.ledger.accounts.fields.type'), orderable: true),
            $this->column('owner', __('admin.ledger.accounts.fields.owner')),
            $this->column('balance_minor', __('admin.ledger.accounts.fields.balance'), orderable: true),
            $this->column('currency', __('admin.ledger.accounts.fields.currency')),
            $this->column('policy', __('admin.ledger.accounts.fields.policy')),
            $this->column('updated_at', __('admin.ledger.accounts.fields.updated'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->editColumn('type', static fn (Model $row): string => $row instanceof LedgerAccount ? e($row->type->label()) : '');
        $table->addColumn('owner', fn (LedgerAccount $account): string => $this->ownerCell($account));
        $this->rawColumn('owner');
        $table->editColumn('balance_minor', fn (Model $row): string => $row instanceof LedgerAccount ? $this->balanceCell($row->balance_minor, $row->currency) : '');
        $this->rawColumn('balance_minor');
        $table->addColumn('policy', static fn (LedgerAccount $account): string => e($account->allow_negative
            ? ($account->credit_limit_minor === null ? __('admin.ledger.accounts.policy.negative_allowed') : __('admin.ledger.accounts.policy.negative_limited'))
            : __('admin.ledger.accounts.policy.never_negative')));
        $this->rawColumn('policy');
        $this->dateColumn($table, 'updated_at');

        $this->actionsColumn($table, static fn (LedgerAccount $account): array => [
            TableAction::view(route('admin.ledger-accounts.show', $account), $account),
        ]);
    }

    /**
     * Owner of a wallet as a short reference that links to the person's page when the admin may open it. Names and phone
     * numbers are not shown here: a ledger reader is not necessarily allowed to see them.
     */
    private function ownerCell(LedgerAccount $account): string
    {
        $owner = $account->owner;

        if ($account->type === LedgerAccountType::CustomerWallet && $owner instanceof User) {
            $label = e(__('admin.ledger.accounts.owner_customer', ['ref' => Str::substr($owner->uuid, 0, 8)]));

            return Gate::allows('view', $owner) ? '<a href="'.e(route('admin.customers.show', $owner)).'" class="text-brand-700 hover:underline">'.$label.'</a>' : $label;
        }

        if ($account->type === LedgerAccountType::ProviderWallet && $owner instanceof ProviderProfile) {
            $label = e(__('admin.ledger.accounts.owner_provider', ['ref' => Str::substr($owner->uuid, 0, 8)]));

            return Gate::allows('view', $owner) ? '<a href="'.e(route('admin.providers.show', $owner)).'" class="text-brand-700 hover:underline">'.$label.'</a>' : $label;
        }

        return '<span class="text-slate-500">'.e(__('admin.ledger.accounts.owner_platform')).'</span>';
    }
}
