<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Wallet\WalletResource;
use App\Http\Resources\Api\V1\Wallet\WalletTransactionResource;
use App\Http\Requests\Api\V1\Payment\StartPaymentRequest;
use App\Http\Resources\Api\V1\Payment\PaymentResource;
use App\Services\Finance\WalletStatementService;
use App\Services\Payments\PaymentIntentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's wallet (SRS §20.3): balance(s) and the statement, scoped to the signed-in customer. Read-only: the wallet moves
 * only through ledger postings (refunds, approved adjustments, wallet payments).
 */
final class WalletController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly WalletStatementService $statements, private readonly PaymentIntentService $intents) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(data: WalletResource::collection($this->statements->customerAccounts($this->authenticatedUser($request))));
    }

    /**
     * Pays the wallet debt (unpaid cancellation fees) through the gateway; the debt amount is the server's.
     */
    public function settleDebt(StartPaymentRequest $request): JsonResponse
    {
        $result = $this->intents->settleDebt($this->authenticatedUser($request), $request->toDto());

        return $result->replayed
            ? ApiResponse::success(__('api.payment.resumed'), new PaymentResource($result))
            : ApiResponse::created(__('api.payment.started'), new PaymentResource($result));
    }

    public function transactions(Request $request): JsonResponse
    {
        $accounts = $this->statements->customerAccounts($this->authenticatedUser($request));

        return ApiResponse::paginated($this->statements->page($accounts, $request->integer('per_page') ?: 20), WalletTransactionResource::class);
    }
}
