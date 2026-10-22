<?php

namespace App\Http\Controllers\Admin\Coupon;

use App\DataTables\Admin\Coupon\CouponAttemptsDataTable;
use App\DataTables\Admin\Coupon\CouponRedemptionsDataTable;
use App\DataTables\Admin\Coupon\CouponsDataTable;
use App\DataTables\Admin\Coupon\CustomerCouponsDataTable;
use App\DTOs\Coupon\CouponFilterData;
use App\Enums\Coupon\CouponAttemptContext;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Coupon\ArchiveCouponRequest;
use App\Http\Requests\Admin\Coupon\PauseCouponRequest;
use App\Http\Requests\Admin\Coupon\PublishCouponRequest;
use App\Http\Requests\Admin\Coupon\ResumeCouponRequest;
use App\Http\Requests\Admin\Coupon\StoreCouponRequest;
use App\Http\Requests\Admin\Coupon\TestCouponEligibilityRequest;
use App\Http\Requests\Admin\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use App\Models\User;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Services\Admin\AdminExportService;
use App\Services\Audit\AuditTrailService;
use App\Services\Coupon\CouponLifecycleService;
use App\Services\Coupon\CouponQuoteService;
use App\Services\Coupon\CouponService;
use App\Support\Coupon\CouponPresenter;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Coupons under "Marketing" (coupons.view / coupons.manage / coupons.export): campaigns with an eligibility builder,
 * immutable financial versions, pause / resume / archive with a reason, redemptions, attempts and performance tabs, an
 * "test eligibility" tool and a queued export. Coupons are archived, never deleted.
 */
final class CouponController extends AdminController
{
    public function __construct(
        private readonly CouponService $service,
        private readonly CouponLifecycleService $lifecycle,
    ) {}

    public function index(CouponsDataTable $table): mixed
    {
        return $table->render('admin.coupons.index');
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(StoreCouponRequest $request): AdminFormResponse
    {
        $coupon = $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.coupons.created'))->redirect(route('admin.coupons.show', $coupon));
    }

    public function show(Coupon $coupon, CouponPresenter $presenter): View
    {
        $coupon->load(['scopes', 'versions']);

        return view('admin.coupons.show', ['coupon' => $coupon, 'presenter' => $presenter]);
    }

    public function activity(Coupon $coupon, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($coupon)]);
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', ['coupon' => $coupon->load('scopes')]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): AdminFormResponse
    {
        $this->service->update($coupon, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.coupons.updated'))->redirect(route('admin.coupons.show', $coupon));
    }

    public function publish(PublishCouponRequest $request, Coupon $coupon): AdminFormResponse
    {
        $this->lifecycle->publish($coupon, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.coupons.published'), $coupon);
    }

    public function pause(PauseCouponRequest $request, Coupon $coupon): AdminFormResponse
    {
        $this->lifecycle->pause($coupon, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.coupons.paused'), $coupon);
    }

    public function resume(ResumeCouponRequest $request, Coupon $coupon): AdminFormResponse
    {
        $this->lifecycle->resume($coupon, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.coupons.resumed'), $coupon);
    }

    public function archive(ArchiveCouponRequest $request, Coupon $coupon): AdminFormResponse
    {
        $this->lifecycle->archive($coupon, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.coupons.archived'), $coupon);
    }

    public function redemptions(CouponRedemptionsDataTable $table, Coupon $coupon): mixed
    {
        return $table->render('admin.coupons.tabs.redemptions');
    }

    public function attempts(CouponAttemptsDataTable $table, Coupon $coupon): mixed
    {
        return $table->render('admin.coupons.tabs.attempts');
    }

    public function performance(Coupon $coupon, CouponReservationRepositoryInterface $reservations, CouponAttemptRepositoryInterface $attempts): View
    {
        return view('admin.coupons.tabs.performance', [
            'coupon' => $coupon,
            'stats' => $reservations->performance($coupon->id),
            'daily' => $reservations->dailyRedemptions($coupon->id, 14),
            'attempts' => $attempts->counts($coupon->id),
            'rejections' => $attempts->rejectionsByReason($coupon->id),
        ]);
    }

    /**
     * Evaluates the coupon's rules for a customer / service / fare / time and shows the decision. Read-only (an `admin_test`
     * attempt is logged); the result block is swapped into the page.
     */
    public function test(TestCouponEligibilityRequest $request, Coupon $coupon, CouponQuoteService $quotes, CustomerRepositoryInterface $customers): AdminFormResponse
    {
        $customer = $customers->find($request->customerId());

        if (! $customer instanceof User) {
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        $quote = $quotes->quote($customer, $request->toDto(), CouponAttemptContext::AdminTest, $coupon);

        return $this->formResponse()->replaceHtml('#coupon-test-result', view('admin.coupons._test-result', ['quote' => $quote, 'customer' => $customer])->render());
    }

    public function export(Request $request, CouponsDataTable $table, AdminExportService $exports): AdminFormResponse
    {
        $export = $exports->queue($table, CouponFilterData::fromRequest($request)->toArray(), $this->actor());

        return $this->formResponse(__('admin.exports.queued'))->with('status_url', route('admin.exports.show', $export));
    }

    public function customerTab(CustomerCouponsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.coupons.tabs.customer');
    }

    private function afterStateChange(bool $fromShow, string $message, Coupon $coupon): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(CouponsDataTable::TABLE_ID);

        return $fromShow ? $response->redirect(route('admin.coupons.show', $coupon)) : $response;
    }
}
