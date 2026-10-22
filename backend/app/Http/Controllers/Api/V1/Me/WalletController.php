<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Wallet\WalletResource;
use App\Http\Resources\Api\V1\Wallet\WalletTransactionResource;
use App\Services\Finance\WalletStatementService;
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

    public function __construct(private readonly WalletStatementService $statements) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(data: WalletResource::collection($this->statements->customerAccounts($this->authenticatedUser($request))));
    }

    public function transactions(Request $request): JsonResponse
    {
        $accounts = $this->statements->customerAccounts($this->authenticatedUser($request));

        return ApiResponse::paginated($this->statements->page($accounts, $request->integer('per_page') ?: 20), WalletTransactionResource::class);
    }
}
