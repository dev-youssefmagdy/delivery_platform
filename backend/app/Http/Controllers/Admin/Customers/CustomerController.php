<?php

namespace App\Http\Controllers\Admin\Customers;

use App\DataTables\Admin\Customers\CustomerDeletionRequestsDataTable;
use App\DataTables\Admin\Customers\CustomerMessagesDataTable;
use App\DataTables\Admin\Customers\CustomersDataTable;
use App\DTOs\Customers\CustomerFilterData;
use App\DTOs\Customers\HelpMessageData;
use App\DTOs\Customers\SuspendCustomerData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Customers\ApproveAccountDeletionRequest;
use App\Http\Requests\Admin\Customers\ExportCustomerDataRequest;
use App\Http\Requests\Admin\Customers\ReactivateCustomerRequest;
use App\Http\Requests\Admin\Customers\RejectAccountDeletionRequest;
use App\Http\Requests\Admin\Customers\RestrictCustomerRequest;
use App\Http\Requests\Admin\Customers\RevealContactRequest;
use App\Http\Requests\Admin\Customers\SendHelpMessageRequest;
use App\Http\Requests\Admin\Customers\SuspendCustomerRequest;
use App\Models\User;
use App\Services\Admin\AdminExportService;
use App\Services\Customers\CustomerAccountService;
use App\Services\Customers\CustomerDeletionService;
use App\Services\Customers\CustomerMessageService;
use App\Services\Customers\CustomerService;
use App\Support\Http\AdminFormResponse;
use App\Support\Http\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Customers (customers.view and friends): listing, show page, account actions, deletion requests and exports.
 * There is no create/edit/delete: customers register through the apps and are never hard-deleted from the panel.
 */
final class CustomerController extends AdminController
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly CustomerAccountService $accounts,
        private readonly CustomerMessageService $messages,
        private readonly CustomerDeletionService $deletions,
    ) {}

    public function index(CustomersDataTable $table): mixed
    {
        return $table->render('admin.customers.index');
    }

    public function show(User $customer): View
    {
        return view('admin.customers.show', ['details' => $this->customers->details($customer->load('customerProfile'), Gate::allows('viewPii', $customer))]);
    }

    public function reveal(RevealContactRequest $request, User $customer): JsonResponse
    {
        return ApiResponse::success(data: $this->customers->revealContact($customer, $this->actor()));
    }

    public function suspend(SuspendCustomerRequest $request, User $customer): AdminFormResponse
    {
        $this->accounts->suspend($customer, SuspendCustomerData::fromRequest($request), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.customers.suspended'));
    }

    public function reactivate(ReactivateCustomerRequest $request, User $customer): AdminFormResponse
    {
        $this->accounts->reactivate($customer, (string) $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.customers.reactivated'));
    }

    public function restrict(RestrictCustomerRequest $request, User $customer): AdminFormResponse
    {
        $this->accounts->restrict($customer, (string) $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.customers.restricted'));
    }

    public function sendMessage(SendHelpMessageRequest $request, User $customer): AdminFormResponse
    {
        $this->messages->send($customer, HelpMessageData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.customers.message_sent'))->closeModal()->reset()->reloadTable(CustomerMessagesDataTable::TABLE_ID);
    }

    public function approveDeletion(ApproveAccountDeletionRequest $request, User $customer, string $deletionRequest): AdminFormResponse
    {
        $this->deletions->approve($customer, $deletionRequest, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.customers.deletion_approved'))->reloadTable(CustomerDeletionRequestsDataTable::TABLE_ID)->reload();
    }

    public function rejectDeletion(RejectAccountDeletionRequest $request, User $customer, string $deletionRequest): AdminFormResponse
    {
        $this->deletions->reject($customer, $deletionRequest, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.customers.deletion_rejected'))->reloadTable(CustomerDeletionRequestsDataTable::TABLE_ID)->reload();
    }

    public function export(Request $request, CustomersDataTable $table, AdminExportService $exports): AdminFormResponse
    {
        $export = $exports->queue($table, CustomerFilterData::fromRequest($request)->toArray(), $this->actor());

        return $this->formResponse(__('admin.exports.queued'))->with('status_url', route('admin.exports.show', $export));
    }

    public function exportData(ExportCustomerDataRequest $request, User $customer, AdminExportService $exports): AdminFormResponse
    {
        $export = $exports->queuePersonalData($customer, $this->actor());

        return $this->formResponse(__('admin.exports.queued'))->with('status_url', route('admin.exports.show', $export));
    }

    private function afterAction(bool $fromShow, string $message): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(CustomersDataTable::TABLE_ID);

        return $fromShow ? $response->reload() : $response;
    }
}
