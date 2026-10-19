<?php

namespace App\Http\Controllers\Admin\Policy;

use App\DataTables\Admin\Policy\PolicyVersionsDataTable;
use App\DTOs\Policy\PolicyVersionData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Policy\DestroyPolicyVersionRequest;
use App\Http\Requests\Admin\Policy\PublishPolicyVersionRequest;
use App\Http\Requests\Admin\Policy\StorePolicyVersionRequest;
use App\Http\Requests\Admin\Policy\UpdatePolicyVersionRequest;
use App\Models\PolicyVersion;
use App\Services\Audit\AuditTrailService;
use App\Services\Policy\PolicyVersionService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Policy versions (policies.manage / policies.publish): draft CRUD and publishing with a mandatory reason.
 */
final class PolicyVersionController extends AdminController
{
    public function __construct(private readonly PolicyVersionService $service) {}

    public function index(PolicyVersionsDataTable $table): mixed
    {
        return $table->render('admin.policy-versions.index');
    }

    public function create(): View
    {
        return view('admin.policy-versions.create');
    }

    public function store(StorePolicyVersionRequest $request): AdminFormResponse
    {
        $policy = $this->service->create(PolicyVersionData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.policy_versions.created'))->redirect(route('admin.policy-versions.show', $policy));
    }

    public function show(PolicyVersion $policyVersion): View
    {
        return view('admin.policy-versions.show', ['policy' => $this->service->details($policyVersion)]);
    }

    public function activity(PolicyVersion $policyVersion, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($policyVersion)]);
    }

    public function edit(PolicyVersion $policyVersion): View
    {
        return view('admin.policy-versions.edit', ['policy' => $policyVersion]);
    }

    public function update(UpdatePolicyVersionRequest $request, PolicyVersion $policyVersion): AdminFormResponse
    {
        $this->service->update($policyVersion, PolicyVersionData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.policy_versions.updated'))->redirect(route('admin.policy-versions.show', $policyVersion));
    }

    public function publish(PublishPolicyVersionRequest $request, PolicyVersion $policyVersion): AdminFormResponse
    {
        $this->service->publish($policyVersion, (string) $request->reason(), $this->actor());

        $response = $this->formResponse(__('admin.policy_versions.published'))->reloadTable(PolicyVersionsDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->reload() : $response;
    }

    public function destroy(DestroyPolicyVersionRequest $request, PolicyVersion $policyVersion): AdminFormResponse
    {
        $this->service->delete($policyVersion, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.policy_versions.deleted'))->reloadTable(PolicyVersionsDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.policy-versions.index')) : $response;
    }
}
