<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\CancellationPoliciesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Booking\ActivateCancellationPolicyRequest;
use App\Http\Requests\Admin\Booking\DeactivateCancellationPolicyRequest;
use App\Http\Requests\Admin\Booking\DestroyCancellationPolicyRequest;
use App\Http\Requests\Admin\Booking\PreviewCancellationPolicyRequest;
use App\Http\Requests\Admin\Booking\StoreCancellationPolicyRequest;
use App\Http\Requests\Admin\Booking\UpdateCancellationPolicyRequest;
use App\Models\CancellationPolicy;
use App\Repositories\Contracts\Booking\CancellationPolicyRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Booking\CancellationPolicyPreviewService;
use App\Services\Booking\CancellationPolicyService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Cancellation policies (cancellation_policies.manage): free grace and waiting time, fees per actor and stage, and the
 * reasons each side may use, per service category and optionally per zone. Thin: one service call each.
 */
final class CancellationPolicyController extends AdminController
{
    public function __construct(private readonly CancellationPolicyService $service) {}

    public function index(CancellationPoliciesDataTable $table): mixed
    {
        return $table->render('admin.cancellation-policies.index');
    }

    public function create(): View
    {
        return view('admin.cancellation-policies.create');
    }

    public function store(StoreCancellationPolicyRequest $request): AdminFormResponse
    {
        $policy = $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.cancellation_policies.created'))->redirect(route('admin.cancellation-policies.show', $policy));
    }

    public function show(CancellationPolicy $cancellationPolicy, CancellationPolicyRepositoryInterface $policies): View
    {
        return view('admin.cancellation-policies.show', [
            'policy' => $cancellationPolicy->load(['serviceCategory', 'serviceZone']),
            'bookingCount' => $policies->bookingCount($cancellationPolicy),
        ]);
    }

    public function activity(CancellationPolicy $cancellationPolicy, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($cancellationPolicy)]);
    }

    public function edit(CancellationPolicy $cancellationPolicy): View
    {
        return view('admin.cancellation-policies.edit', ['policy' => $cancellationPolicy->load(['serviceCategory', 'serviceZone'])]);
    }

    public function update(UpdateCancellationPolicyRequest $request, CancellationPolicy $cancellationPolicy): AdminFormResponse
    {
        $this->service->update($cancellationPolicy, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.cancellation_policies.updated'))->redirect(route('admin.cancellation-policies.show', $cancellationPolicy));
    }

    public function preview(PreviewCancellationPolicyRequest $request, CancellationPolicy $cancellationPolicy, CancellationPolicyPreviewService $previews): AdminFormResponse
    {
        $result = $previews->preview($cancellationPolicy, $request->toDto());

        return $this->formResponse()->replaceHtml('#cancellation-preview-result', view('admin.cancellation-policies._preview-result', $result + ['policy' => $cancellationPolicy])->render());
    }

    public function activate(ActivateCancellationPolicyRequest $request, CancellationPolicy $cancellationPolicy): AdminFormResponse
    {
        $this->service->setActive($cancellationPolicy, true, null, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.cancellation_policies.activated'), route('admin.cancellation-policies.show', $cancellationPolicy));
    }

    public function deactivate(DeactivateCancellationPolicyRequest $request, CancellationPolicy $cancellationPolicy): AdminFormResponse
    {
        $this->service->setActive($cancellationPolicy, false, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.cancellation_policies.deactivated'), route('admin.cancellation-policies.show', $cancellationPolicy));
    }

    public function destroy(DestroyCancellationPolicyRequest $request, CancellationPolicy $cancellationPolicy): AdminFormResponse
    {
        $this->service->delete($cancellationPolicy, (string) $request->reason(), $this->actor());

        $response = $this->formResponse(__('admin.cancellation_policies.deleted'))->reloadTable(CancellationPoliciesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.cancellation-policies.index')) : $response;
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(CancellationPoliciesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
