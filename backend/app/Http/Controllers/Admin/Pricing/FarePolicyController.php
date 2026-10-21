<?php

namespace App\Http\Controllers\Admin\Pricing;

use App\DataTables\Admin\Pricing\FarePoliciesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Pricing\ActivateFarePolicyRequest;
use App\Http\Requests\Admin\Pricing\DeactivateFarePolicyRequest;
use App\Http\Requests\Admin\Pricing\DestroyFarePolicyRequest;
use App\Http\Requests\Admin\Pricing\PreviewFarePolicyRequest;
use App\Http\Requests\Admin\Pricing\StoreFarePolicyRequest;
use App\Http\Requests\Admin\Pricing\UpdateFarePolicyRequest;
use App\Models\FarePolicy;
use App\Repositories\Contracts\Pricing\FarePolicyRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Pricing\FarePolicyService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Fare policies under "Pricing" (fare_policies.manage): the reference-price configuration per service category and
 * optionally per zone, with a read-only calculator to see what a policy recommends for a trip.
 */
final class FarePolicyController extends AdminController
{
    public function __construct(private readonly FarePolicyService $service) {}

    public function index(FarePoliciesDataTable $table): mixed
    {
        return $table->render('admin.fare-policies.index');
    }

    public function create(): View
    {
        return view('admin.fare-policies.create');
    }

    public function store(StoreFarePolicyRequest $request): AdminFormResponse
    {
        $policy = $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.fare_policies.created'))->redirect(route('admin.fare-policies.show', $policy));
    }

    public function show(FarePolicy $farePolicy, FarePolicyRepositoryInterface $policies): View
    {
        return view('admin.fare-policies.show', [
            'policy' => $farePolicy->load(['serviceCategory', 'serviceZone']),
            'bookingCount' => $policies->bookingCount($farePolicy),
        ]);
    }

    public function activity(FarePolicy $farePolicy, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($farePolicy)]);
    }

    public function edit(FarePolicy $farePolicy): View
    {
        return view('admin.fare-policies.edit', ['policy' => $farePolicy->load(['serviceCategory', 'serviceZone'])]);
    }

    public function update(UpdateFarePolicyRequest $request, FarePolicy $farePolicy): AdminFormResponse
    {
        $this->service->update($farePolicy, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.fare_policies.updated'))->redirect(route('admin.fare-policies.show', $farePolicy));
    }

    public function preview(PreviewFarePolicyRequest $request, FarePolicy $farePolicy): AdminFormResponse
    {
        $guidance = $this->service->preview(
            $farePolicy,
            $request->distanceMeters(),
            $request->durationSeconds(),
            (int) $request->input('stops', 0),
            $request->answers(),
        );

        return $this->formResponse()->replaceHtml('#fare-preview-result', view('admin.fare-policies._preview-result', ['guidance' => $guidance, 'policy' => $farePolicy])->render());
    }

    public function activate(ActivateFarePolicyRequest $request, FarePolicy $farePolicy): AdminFormResponse
    {
        $this->service->setActive($farePolicy, true, null, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.fare_policies.activated'), route('admin.fare-policies.show', $farePolicy));
    }

    public function deactivate(DeactivateFarePolicyRequest $request, FarePolicy $farePolicy): AdminFormResponse
    {
        $this->service->setActive($farePolicy, false, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.fare_policies.deactivated'), route('admin.fare-policies.show', $farePolicy));
    }

    public function destroy(DestroyFarePolicyRequest $request, FarePolicy $farePolicy): AdminFormResponse
    {
        $this->service->delete($farePolicy, (string) $request->reason(), $this->actor());

        $response = $this->formResponse(__('admin.fare_policies.deleted'))->reloadTable(FarePoliciesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.fare-policies.index')) : $response;
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(FarePoliciesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
