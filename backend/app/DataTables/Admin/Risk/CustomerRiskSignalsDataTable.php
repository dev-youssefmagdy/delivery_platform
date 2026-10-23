<?php

namespace App\DataTables\Admin\Risk;

use App\DataTables\Admin\Customers\CustomerTabDataTable;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Risk tab of the customer page (risk.view): every signal ever raised about the customer, newest first. Internal only.
 */
final class CustomerRiskSignalsDataTable extends CustomerTabDataTable
{
    use RendersRiskSignalColumns;

    public const TABLE_ID = 'customer-risk-table';

    protected array $defaultOrder = [[3, 'desc']];

    public function __construct(private readonly RiskSignalRepositoryInterface $signals)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    public function query(): Builder
    {
        return $this->signals->forSubjectQuery(RiskSubjectType::Customer, $this->customer()->id);
    }

    protected function columns(): array
    {
        return $this->riskColumns();
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->configureRisk($table);
    }
}
