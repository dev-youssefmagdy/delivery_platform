<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
use Yajra\DataTables\Html\Builder;

/**
 * Renders a Yajra HTML builder (AdminDataTable::html()) as <table data-admin-table> with the DataTables options
 * and localized language as JSON; resources/js/admin/core/datatables.js initialises it (no inline scripts).
 */
class Datatable extends Component
{
    public string $tableHtml;

    public string $options;

    public string $language;

    public string $tableId;

    public function __construct(Builder $table)
    {
        $this->tableId = $table->getTableId();
        $this->tableHtml = (string) $table->table([
            'data-admin-table' => 'true',
            'aria-busy' => 'false',
        ]);

        $options = $table->getOptions();
        unset($options['language']);
        $this->options = (string) json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        /** @var array<string, mixed> $language */
        $language = trans('datatables');
        $language['emptyTable'] = Blade::render('<x-admin.empty-state :title="$title" compact />', ['title' => $language['emptyTable'] ?? '']);
        $language['zeroRecords'] = Blade::render('<x-admin.empty-state icon="search" :title="$title" compact />', ['title' => $language['zeroRecords'] ?? '']);
        $this->language = (string) json_encode($language, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function render(): View
    {
        return view('components.admin.datatable');
    }
}
