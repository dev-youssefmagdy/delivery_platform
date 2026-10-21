<?php

namespace App\Http\Controllers\Admin\Finance;

use App\DataTables\Admin\Finance\LedgerAccountsDataTable;
use App\DataTables\Admin\Finance\LedgerAccountStatementDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\LedgerAccount;
use Illuminate\Contracts\View\View;

/**
 * Ledger accounts (ledger.view), read-only: the listing, one account with its statement.
 */
final class LedgerAccountController extends AdminController
{
    public function index(LedgerAccountsDataTable $table): mixed
    {
        return $table->render('admin.ledger-accounts.index');
    }

    public function show(LedgerAccount $ledgerAccount): View
    {
        return view('admin.ledger-accounts.show', ['account' => $ledgerAccount->load('owner')]);
    }

    public function statement(LedgerAccountStatementDataTable $table, LedgerAccount $ledgerAccount): mixed
    {
        return $table->render('admin.finance.tab-table', ['intro' => __('admin.ledger.accounts.statement_intro')]);
    }
}
