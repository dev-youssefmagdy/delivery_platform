<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Provider\VehicleAuditDataTable;
use App\DataTables\Admin\Provider\VehicleDocumentsDataTable;
use App\DataTables\Admin\Provider\VehiclesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Provider\ApproveVehicleRequest;
use App\Http\Requests\Admin\Provider\RejectVehicleRequest;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Provider\DocumentChecklistService;
use App\Services\Provider\VehicleService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Vehicles (vehicles.view / approve / reject): listing, show page with photos, documents and checklist, and the
 * approve / reject decisions (reject needs a reason; both are audited).
 */
final class VehicleController extends AdminController
{
    public function __construct(
        private readonly VehicleService $vehicles,
        private readonly VehicleRepositoryInterface $repository,
        private readonly DocumentChecklistService $checklists,
    ) {}

    public function index(VehiclesDataTable $table): mixed
    {
        return $table->render('admin.vehicles.index');
    }

    public function show(Vehicle $vehicle): View
    {
        $loaded = $this->repository->findByUuidWithRelations($vehicle->uuid) ?? $vehicle;

        $photoItems = $loaded->photos->map(static fn ($photo): array => [
            'key' => 'photo-'.$photo->uuid,
            'group' => $loaded->plate_display,
            'label' => $photo->kind->label(),
            'kind' => 'photo',
            'mime' => $photo->mime,
            'status' => null,
            'endpoint' => route('admin.vehicle-photos.url', $photo->uuid),
        ])->values()->all();

        return view('admin.vehicles.show', ['vehicle' => $loaded, 'checklist' => $this->checklists->forVehicle($loaded), 'photoItems' => $photoItems]);
    }

    public function documents(VehicleDocumentsDataTable $table, Vehicle $vehicle): mixed
    {
        return $table->render('admin.vehicles.tabs.table', ['intro' => __('admin.vehicles.documents.intro')]);
    }

    public function audit(VehicleAuditDataTable $table, Vehicle $vehicle): mixed
    {
        return $table->render('admin.vehicles.tabs.table', ['intro' => __('admin.vehicles.audit_intro')]);
    }

    public function approve(ApproveVehicleRequest $request, Vehicle $vehicle): AdminFormResponse
    {
        $this->vehicles->approve($vehicle, $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.vehicles.approved'));
    }

    public function reject(RejectVehicleRequest $request, Vehicle $vehicle): AdminFormResponse
    {
        $this->vehicles->reject($vehicle, (string) $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.vehicles.rejected'));
    }

    private function afterAction(bool $fromShow, string $message): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(VehiclesDataTable::TABLE_ID);

        return $fromShow ? $response->reload() : $response;
    }
}
