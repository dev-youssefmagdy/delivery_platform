<?php

namespace App\Http\Controllers\Admin\Payout;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payout\UnverifyPayoutMethodRequest;
use App\Http\Requests\Admin\Payout\VerifyPayoutMethodRequest;
use App\Models\ProviderPayoutMethod;
use App\Services\Payout\PayoutMethodService;
use App\Support\Http\AdminFormResponse;

/**
 * Verification of a provider's payout method (payouts.review): finance confirms the destination before any payout can use it.
 */
final class PayoutMethodController extends AdminController
{
    public function __construct(private readonly PayoutMethodService $methods) {}

    public function verify(VerifyPayoutMethodRequest $request, ProviderPayoutMethod $payoutMethod): AdminFormResponse
    {
        $this->methods->verify($this->actor(), $payoutMethod, (string) $request->reason());

        return $this->formResponse(__('admin.payout_methods.verified'))->reload();
    }

    public function unverify(UnverifyPayoutMethodRequest $request, ProviderPayoutMethod $payoutMethod): AdminFormResponse
    {
        $this->methods->unverify($this->actor(), $payoutMethod, (string) $request->reason());

        return $this->formResponse(__('admin.payout_methods.unverified'))->reload();
    }
}
