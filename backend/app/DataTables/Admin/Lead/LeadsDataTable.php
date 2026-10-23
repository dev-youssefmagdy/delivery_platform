<?php

namespace App\DataTables\Admin\Lead;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Lead\LeadFilterData;
use App\Models\Lead;
use App\Repositories\Contracts\Lead\LeadRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Onboarding leads (filters: city, service interest, status, created range). Phones are masked in the list; the full contact
 * is on the lead page for staff with `leads.view`.
 */
final class LeadsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'leads-table';

    protected array $defaultOrder = [[4, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly LeadRepositoryInterface $leads)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Lead>
     */
    public function query(): Builder
    {
        return $this->leads->datatableQuery(LeadFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('reference', __('leads.fields.reference'), orderable: true, name: 'leads.reference'),
            $this->column('contact', __('leads.fields.contact')),
            $this->column('city', __('leads.fields.city')),
            $this->column('service', __('leads.fields.service')),
            $this->column('created_at', __('leads.fields.created'), orderable: true, name: 'leads.created_at'),
            $this->column('status', __('leads.fields.status'), orderable: true, name: 'leads.status'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['reference', 'contact', 'city', 'service', 'created_at', 'status', 'actions']);

        $table->editColumn('reference', static fn (Model $row): string => $row instanceof Lead ? '<a href="'.e(route('admin.leads.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e((string) $row->reference).'</a>' : '');
        $this->rawColumn('reference');
        $table->addColumn('contact', static fn (Lead $lead): string => e($lead->name).' <span dir="ltr" class="text-xs text-slate-500">'.e(PhoneNumber::mask($lead->phone_e164)).'</span>');
        $this->rawColumn('contact');
        $table->addColumn('city', static fn (Lead $lead): string => e($lead->city->name ?? $lead->city_other ?? '—'));
        $table->addColumn('service', static fn (Lead $lead): string => e($lead->serviceCategory->name ?? '—'));
        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'status');

        $this->actionsColumn($table, static fn (Lead $lead): array => [TableAction::view(route('admin.leads.show', $lead), $lead)]);
    }
}
