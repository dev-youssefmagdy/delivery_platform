<?php

namespace App\Http\Controllers\Admin\Support;

use App\DataTables\Admin\Support\SupportMacrosDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Support\StoreMacroRequest;
use App\Http\Requests\Admin\Support\ToggleMacroRequest;
use App\Http\Requests\Admin\Support\UpdateMacroRequest;
use App\Models\SupportMacro;
use App\Services\Audit\AuditTrailService;
use App\Services\Support\SupportMacroService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Macros module (macros.manage): canned replies in Arabic and English for the ticket screen.
 */
final class SupportMacroController extends AdminController
{
    public function __construct(private readonly SupportMacroService $service) {}

    public function index(SupportMacrosDataTable $table): mixed
    {
        return $table->render('admin.macros.index');
    }

    public function create(): View
    {
        return view('admin.macros.create');
    }

    public function store(StoreMacroRequest $request): AdminFormResponse
    {
        $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.macros.created'))->redirect(route('admin.macros.index'));
    }

    public function show(SupportMacro $macro): View
    {
        return view('admin.macros.show', ['macro' => $macro]);
    }

    public function activity(SupportMacro $macro, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($macro)]);
    }

    public function edit(SupportMacro $macro): View
    {
        return view('admin.macros.edit', ['macro' => $macro]);
    }

    public function update(UpdateMacroRequest $request, SupportMacro $macro): AdminFormResponse
    {
        $this->service->update($macro, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.macros.updated'))->redirect(route('admin.macros.show', $macro));
    }

    public function activate(ToggleMacroRequest $request, SupportMacro $macro): AdminFormResponse
    {
        $this->service->setActive($macro, true, $this->actor(), $request->reason());

        return $this->formResponse(__('admin.macros.activated'))->reloadTable(SupportMacrosDataTable::TABLE_ID);
    }

    public function deactivate(ToggleMacroRequest $request, SupportMacro $macro): AdminFormResponse
    {
        $this->service->setActive($macro, false, $this->actor(), $request->reason());

        return $this->formResponse(__('admin.macros.deactivated'))->reloadTable(SupportMacrosDataTable::TABLE_ID);
    }
}
