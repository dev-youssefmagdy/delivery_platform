<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Provider\ProviderDeletionRequestsDataTable;
use App\DataTables\Admin\Provider\ProvidersDataTable;
use App\DataTables\Admin\Provider\ProviderServicesDataTable;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Provider\ApproveProviderDeletionRequest;
use App\Http\Requests\Admin\Provider\ApproveProviderServiceRequest;
use App\Http\Requests\Admin\Provider\ReactivateProviderRequest;
use App\Http\Requests\Admin\Provider\RejectProviderDeletionRequest;
use App\Http\Requests\Admin\Provider\RejectProviderServiceRequest;
use App\Http\Requests\Admin\Provider\RevealProviderContactRequest;
use App\Http\Requests\Admin\Provider\SuspendProviderRequest;
use App\Models\ProviderProfile;
use App\Services\Provider\ProviderAccountService;
use App\Services\Provider\ProviderDeletionService;
use App\Services\Provider\ProviderService;
use App\Services\Provider\ProviderServiceCategoryService;
use App\Support\Http\AdminFormResponse;
use App\Support\Http\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Providers (drivers.view and friends): listing, show page, suspension, per-service decisions and deletion requests.
 * Applications are decided in the verification queue; providers register through the driver app and are never created
 * or hard-deleted from the panel.
 */
final class ProviderController extends AdminController
{
    public function __construct(
        private readonly ProviderService $providers,
        private readonly ProviderAccountService $accounts,
        private readonly ProviderServiceCategoryService $serviceDecisions,
        private readonly ProviderDeletionService $deletions,
    ) {}

    public function index(ProvidersDataTable $table): mixed
    {
        return $table->render('admin.providers.index');
    }

    public function show(ProviderProfile $provider): View
    {
        $provider->loadMissing(['user' => static fn ($query) => $query->withTrashed(), 'city', 'assignedAdmin']);

        return view('admin.providers.show', [
            'details' => $this->providers->details($provider, Gate::allows('viewPii', $provider)),
            'maskedPhone' => $this->providers->maskedPhone($provider),
        ]);
    }

    public function reveal(RevealProviderContactRequest $request, ProviderProfile $provider): JsonResponse
    {
        return ApiResponse::success(data: $this->providers->revealContact($provider->loadMissing('user'), $this->actor()));
    }

    public function suspend(SuspendProviderRequest $request, ProviderProfile $provider): AdminFormResponse
    {
        $this->accounts->suspend($provider, $request->toDto(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.providers.suspended'));
    }

    public function reactivate(ReactivateProviderRequest $request, ProviderProfile $provider): AdminFormResponse
    {
        $this->accounts->reactivate($provider, (string) $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.providers.reactivated'));
    }

    public function approveService(ApproveProviderServiceRequest $request, ProviderProfile $provider, int $serviceCategory): AdminFormResponse
    {
        $this->serviceDecisions->decide($provider, $serviceCategory, ServiceApprovalStatus::Approved, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.providers.service_approved'))->reloadTable(ProviderServicesDataTable::TABLE_ID);
    }

    public function rejectService(RejectProviderServiceRequest $request, ProviderProfile $provider, int $serviceCategory): AdminFormResponse
    {
        $this->serviceDecisions->decide($provider, $serviceCategory, ServiceApprovalStatus::Rejected, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.providers.service_rejected'))->reloadTable(ProviderServicesDataTable::TABLE_ID);
    }

    public function approveDeletion(ApproveProviderDeletionRequest $request, ProviderProfile $provider, string $deletionRequest): AdminFormResponse
    {
        $this->deletions->approve($provider, $deletionRequest, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.providers.deletion_approved'))->reloadTable(ProviderDeletionRequestsDataTable::TABLE_ID)->reload();
    }

    public function rejectDeletion(RejectProviderDeletionRequest $request, ProviderProfile $provider, string $deletionRequest): AdminFormResponse
    {
        $this->deletions->reject($provider, $deletionRequest, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.providers.deletion_rejected'))->reloadTable(ProviderDeletionRequestsDataTable::TABLE_ID)->reload();
    }

    private function afterAction(bool $fromShow, string $message): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(ProvidersDataTable::TABLE_ID);

        return $fromShow ? $response->reload() : $response;
    }
}
