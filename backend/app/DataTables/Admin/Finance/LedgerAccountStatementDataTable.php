<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Finance\Concerns\StatementColumns;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Yajra\DataTables\EloquentDataTable;

/**
 * Statement tab of the ledger account page: every line of the account, newest first, with the running balance.
 */
final class LedgerAccountStatementDataTable extends AdminDataTable
{
    use StatementColumns;

    public const TABLE_ID = 'ledger-account-statement-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly JournalLineRepositoryInterface $lines)
    {
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
        $account = $this->request()->route('ledgerAccount');

        return $this->lines->statementQuery([$account instanceof LedgerAccount ? $account->id : throw new LogicException('The statement needs a {ledgerAccount} route parameter.')]);
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
