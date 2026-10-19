<?php

namespace App\View\Components\Admin\Table;

use App\Support\Admin\Tables\TableAction;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Permission-aware action buttons (row actions in DataTables, page header actions). Each action is filtered
 * through its policy ability; when nothing is allowed nothing is rendered.
 */
class Actions extends Component
{
    /**
     * @var list<TableAction>
     */
    public array $visible;

    /**
     * @param list<TableAction> $actions
     */
    public function __construct(array $actions = [], public bool $labels = false)
    {
        $this->visible = array_values(array_filter($actions, static fn (TableAction $action): bool => $action->isAllowed()));
    }

    public function shouldRender(): bool
    {
        return $this->visible !== [];
    }

    public function render(): View
    {
        return view('components.admin.table.actions');
    }
}
