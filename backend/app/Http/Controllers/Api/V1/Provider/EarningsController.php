<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payout\EarningTransactionResource;
use App\Http\Resources\Api\V1\Wallet\WalletTransactionResource;
use App\Services\Finance\ProviderEarningsService;
use App\Services\Finance\WalletStatementService;
use App\Support\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's money overview (SRS §7.6, §20.4): the earnings summary by day / week / month and by service category, the
 * booking-level breakdown and the wallet statement. Read-only and status-safe, always scoped to the signed-in provider.
 */
final class EarningsController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly ProviderEarningsService $earnings,
        private readonly WalletStatementService $statements,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'string', 'in:'.implode(',', ProviderEarningsService::PERIODS)],
            'date' => ['sometimes', 'date'],
        ]);

        return ApiResponse::success(data: $this->earnings->summary(
            $this->providerProfile($request),
            (string) ($validated['period'] ?? 'month'),
            isset($validated['date']) ? CarbonImmutable::parse((string) $validated['date']) : null,
        ));
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $zone = (string) config('admin.display_timezone', 'UTC');

        return ApiResponse::paginated($this->earnings->transactions(
            $this->providerProfile($request),
            isset($validated['from']) ? CarbonImmutable::parse((string) $validated['from'], $zone)->startOfDay() : null,
            isset($validated['to']) ? CarbonImmutable::parse((string) $validated['to'], $zone)->addDay()->startOfDay() : null,
            (int) ($validated['per_page'] ?? 20),
        ), EarningTransactionResource::class);
    }

    public function wallet(Request $request): JsonResponse
    {
        $accounts = $this->statements->providerAccounts($this->providerProfile($request));

        return ApiResponse::paginated($this->statements->page($accounts, $request->integer('per_page') ?: 20), WalletTransactionResource::class);
    }
}
