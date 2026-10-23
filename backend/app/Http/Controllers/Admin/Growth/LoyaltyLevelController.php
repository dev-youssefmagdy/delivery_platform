<?php

namespace App\Http\Controllers\Admin\Growth;

use App\DataTables\Admin\Growth\LoyaltyLevelsDataTable;
use App\DTOs\Growth\LoyaltyLevelData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Growth\SaveLoyaltyLevelRequest;
use App\Http\Requests\Admin\Growth\ToggleLoyaltyLevelRequest;
use App\Models\LoyaltyLevel;
use App\Services\Growth\LoyaltyLevelService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Loyalty levels (loyalty.view / loyalty.manage).
 */
final class LoyaltyLevelController extends AdminController
{
    public function __construct(private readonly LoyaltyLevelService $service) {}

    public function index(LoyaltyLevelsDataTable $table): mixed
    {
        return $table->render('admin.growth.loyalty-levels.index');
    }

    public function create(): View
    {
        return view('admin.growth.loyalty-levels.form', ['level' => null]);
    }

    public function store(SaveLoyaltyLevelRequest $request): AdminFormResponse
    {
        $this->service->create($this->actor(), LoyaltyLevelData::fromRequest($request));

        return $this->formResponse(__('growth.saved'))->redirect(route('admin.loyalty-levels.index'));
    }

    public function edit(LoyaltyLevel $loyaltyLevel): View
    {
        return view('admin.growth.loyalty-levels.form', ['level' => $loyaltyLevel]);
    }

    public function update(SaveLoyaltyLevelRequest $request, LoyaltyLevel $loyaltyLevel): AdminFormResponse
    {
        $this->service->update($loyaltyLevel, $this->actor(), LoyaltyLevelData::fromRequest($request));

        return $this->formResponse(__('growth.saved'))->redirect(route('admin.loyalty-levels.index'));
    }

    public function toggle(ToggleLoyaltyLevelRequest $request, LoyaltyLevel $loyaltyLevel): AdminFormResponse
    {
        $this->service->setActive($loyaltyLevel, $this->actor(), $request->wantsActive(), $request->reason());

        return $this->formResponse(__('growth.moved'))->reloadTable(LoyaltyLevelsDataTable::TABLE_ID);
    }
}
