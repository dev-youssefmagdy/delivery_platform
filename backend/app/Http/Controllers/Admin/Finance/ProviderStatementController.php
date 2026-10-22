<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Finance\ExportProviderStatementRequest;
use App\Models\ProviderProfile;
use App\Services\Finance\ProviderStatementService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provider wallet statement as CSV (finance.view + reports.export). Built from the ledger lines, streamed (never stored) and
 * audited with the period and the opening / closing balance.
 */
final class ProviderStatementController extends AdminController
{
    public function __construct(private readonly ProviderStatementService $statements) {}

    public function export(ExportProviderStatementRequest $request, ProviderProfile $provider): StreamedResponse
    {
        $statement = $this->statements->build($this->actor(), $provider, $request->from(), $request->to());

        return response()->streamDownload(static function () use ($statement): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($statement['rows'] as $row) {
                fputcsv($out, array_map(static fn (string $cell): string => $cell !== '' && ! is_numeric($cell) && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$cell : $cell, $row), escape: '');
            }

            fclose($out);
        }, 'provider-statement-'.substr($provider->uuid, 0, 8).'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
