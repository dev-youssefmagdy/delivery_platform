<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\Finance\Concerns\StatementColumns;
use App\DataTables\Admin\Provider\ProviderTabDataTable;
use App\Enums\Finance\LedgerAccountType;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Wallet tab of the provider page: the statement of the provider's wallet(s), newest first.
 */
final class ProviderWalletDataTable extends ProviderTabDataTable
{
    use StatementColumns;

    public const TABLE_ID = 'provider-wallet-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(
        private readonly JournalLineRepositoryInterface $lines,
        private readonly LedgerAccountRepositoryInterface $accounts,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<JournalLine>
     */
    public function query(): Builder
    {
        return $this->lines->statementQuery($this->accounts->forOwner(LedgerAccountType::ProviderWallet, $this->provider()->id)
            ->map(static fn (LedgerAccount $account): int => $account->id)->all());
    }

    protected function columns(): array
    {
        return $this->statementColumns();
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->configureStatement($table);
    }
}
